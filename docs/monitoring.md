# Monitoring and Health Probes

## Readiness scope

The readiness endpoint `GET /health` checks dependencies required for request processing:

- PostgreSQL (`database`)
- RabbitMQ (`rabbitmq`)

If any check fails, readiness returns HTTP `503` and includes a `detail` message for the failing check.

## Why mailer is excluded

`MailerHealthCheck` is intentionally not implemented at this stage. Probing SMTP connectivity on each readiness call creates avoidable load on the relay and usually provides limited orchestration value.

This design keeps the probe fast and focused on the dependencies that directly affect API request handling.

## Metrics storage

Application metrics are collected through the domain port `MetricsCollectorInterface`, implemented by `PrometheusMetricsCollector` on top of [`promphp/prometheus_client_php`](https://github.com/PromPHP/prometheus_client_php).

| Variable | Values | Default | Purpose |
| --- | --- | --- | --- |
| `METRICS_STORAGE` | `apcu`, `redis` | `redis` | Selects the Prometheus client storage adapter |
| `METRICS_REDIS_HOST` | host | `redis` | Redis host when `METRICS_STORAGE=redis` (Docker service name) |
| `METRICS_REDIS_PORT` | port | `${REDIS_PORT}` | Redis port when `METRICS_STORAGE=redis` |
| `METRICS_REDIS_PASSWORD` | string | `${REDIS_PASSWORD}` | Redis password when `METRICS_STORAGE=redis` |

### Prometheus/Grafana containers are optional

The app exposes `GET /metrics` regardless of whether anything is scraping it. The `prometheus`, `grafana` and `postgres_exporter` containers in `docker/compose.yaml` only add scraping/dashboards on top and are tagged with the `monitoring` Compose profile, so `make up`/`make init` skip them — start them with `make up-monitoring` when you actually want to look at Grafana or query Prometheus locally.

### Redis (default — required for scale-out)

`docker/compose.yaml` ships a password-protected `redis:7-alpine` service, reused across the app for more than just metrics — see "Cache and scale-out (Redis)" in the README for the full picture (cache pool, rate limiter storage, scheduler state). With multiple PHP workers or replicas scraped through a load balancer, each process only ever sees its own subset of requests; without a shared store, Prometheus would read disjoint, undercounted samples depending on which replica answered the scrape.

### APCu (single-container only)

`\Prometheus\Storage\APC` fits a single-container deployment where the PHP runtime already has APCu available for userland caching and no cross-replica consistency is needed. Set `METRICS_STORAGE=apcu` to opt out of Redis for metrics specifically — the cache pool and rate limiter storage (`config/packages/cache.yaml`) are configured independently and stay on Redis unless you also change `framework.cache.app`.

An unrecognized `METRICS_STORAGE` value causes a `LogicException` at container boot so misconfiguration fails loudly instead of falling back silently.

## Prometheus scrape (`GET /metrics`)

The application exposes Prometheus text format on `GET /metrics` at the **root** of the HTTP server (same level as `/health` and `/health/live`, not under `/api/v1`). The body is rendered from the shared `CollectorRegistry` via `\Prometheus\RenderTextFormat` with `Content-Type: text/plain; version=0.0.4; charset=utf-8`.

### Public access assumption

`/metrics` is intentionally **without JWT**. Scraping is intended to happen from trusted networks (e.g. Prometheus on the Docker internal network). In production you should rely on firewalling, segmentation, or a reverse-proxy allow-list rather than coupling scrapers to application auth—IP allow-listing or a scraper-specific secret header would be natural follow-ups but are **out of scope** for this phase.

The Docker Compose Prometheus job `symfony` scrapes `http://php:80/metrics` — FrankenPHP serves HTTP directly (no separate reverse proxy/FPM hop), so this is the same path real traffic takes.

### Automatic HTTP metrics

An `HttpMetricsListener` records `http_requests_total` and `http_request_duration_seconds` for each main request. The `/metrics` and `/health/live` routes are **excluded** so scrapes and liveness checks do not skew HTTP traffic dashboards.

### Build metadata gauge

On the first `/metrics` scrape per worker process, the app emits `app_build_info{version,php_version,env} 1`.

- `version` comes from `APP_VERSION` (default `unknown`)
- `php_version` comes from `PHP_VERSION`
- `env` comes from `APP_ENV`

The gauge is initialized once in-process and is not re-emitted on each HTTP request.

## Domain workload metrics

The outbox/scheduler/email workloads emit business-level metrics through `MetricsCollectorInterface`:

- `outbox_messages_relayed_total`: incremented once per successfully relayed outbox row.
- `outbox_messages_purged_total{retention_days}`: incremented once per cleanup run, labeled with the effective retention value used (including fallback behavior).
- `outbox_unpublished_messages`: gauge refreshed at the start of each relay scheduler tick.
- `scheduler_task_runs_total{task,status}` for `relay_outbox`, `cleanup_refresh_tokens`, and `cleanup_stale_outbox`, with `status` in `ok|error`.
- `emails_sent_total{template,status}` for templates `welcome` and `account_deletion`, with `status` in `sent|failed`.
