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
