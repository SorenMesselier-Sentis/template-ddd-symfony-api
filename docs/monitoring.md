# Monitoring and Health Probes

## Readiness scope

The readiness endpoint `GET /health` checks dependencies required for request processing:

- PostgreSQL (`database`)
- RabbitMQ (`rabbitmq`)

If any check fails, readiness returns HTTP `503` and includes a `detail` message for the failing check.

## Why mailer is excluded

`MailerHealthCheck` is intentionally not implemented at this stage. Probing SMTP connectivity on each readiness call creates avoidable load on the relay and usually provides limited orchestration value.

This design keeps the probe fast and focused on the dependencies that directly affect API request handling.
