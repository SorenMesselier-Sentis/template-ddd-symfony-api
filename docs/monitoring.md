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
| `METRICS_STORAGE` | `apcu`, `redis` | `apcu` | Selects the Prometheus client storage adapter |
| `METRICS_REDIS_HOST` | host | `127.0.0.1` | Redis host when `METRICS_STORAGE=redis` |
| `METRICS_REDIS_PORT` | port | `6379` | Redis port when `METRICS_STORAGE=redis` |
| `METRICS_REDIS_PASSWORD` | string | _(empty)_ | Optional Redis password |

### APCu (default)

`\Prometheus\Storage\APC` is the default backend. It fits single-container deployments where the PHP runtime already has APCu available for userland caching. No extra infrastructure is required.

### Redis (scale-out)

For multiple PHP workers or replicas that must share the same metric samples, set `METRICS_STORAGE=redis` and point the `METRICS_REDIS_*` variables at a Redis instance reachable from every worker.

Adding a Redis service to `docker/compose.yaml` is intentionally out of scope for this template phase; wire an external Redis (or add the service in your own fork) before enabling this mode.

An unrecognized `METRICS_STORAGE` value causes a `LogicException` at container boot so misconfiguration fails loudly instead of falling back silently.

## Prometheus scrape (`GET /metrics`)

The application exposes Prometheus text format on `GET /metrics` at the **root** of the HTTP server (same level as `/health` and `/health/live`, not under `/api/v1`). The body is rendered from the shared `CollectorRegistry` via `\Prometheus\RenderTextFormat` with `Content-Type: text/plain; version=0.0.4; charset=utf-8`.

### Public access assumption

`/metrics` is intentionally **without JWT**. Scraping is intended to happen from trusted networks (e.g. Prometheus on the Docker internal network). In production you should rely on firewalling, segmentation, or a reverse-proxy allow-list rather than coupling scrapers to application auth—IP allow-listing or a scraper-specific secret header would be natural follow-ups but are **out of scope** for this phase.

The Docker Compose Prometheus job `symfony` scrapes `http://nginx:80/metrics` so traffic follows the normal Nginx → PHP-FPM path (do not point scrapes at the FPM port).

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
