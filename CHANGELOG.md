# Changelog

Notable changes to this template, so forks can tell what's worth pulling forward. This isn't a
versioned/released package — there are no tags — so entries are dated rather than numbered.

The history below `## Unreleased` was assembled retrospectively from git log on 2026-08-20, when this
file was introduced; entries there are grouped by merge point, not commit-by-commit. **From this point
forward, add an entry under `Unreleased` in the same pull request as the change** — that's the whole
point of the file for a template that gets forked repeatedly.

## Unreleased

### Changed
- ER diagram columns now render as `name type` (e.g. `owner_id uuid`) instead of `type name`, matching
  the order columns are declared in SQL.
- Every `LoggerInterface::error()` call that logs a caught exception (`ExceptionListener`, the scheduler
  cleanup/outbox handlers, the User BC email event handlers, `SymfonyMailerEmailSender`) now passes the
  actual `\Throwable` under `context['exception']` instead of just `$e->getMessage()`/`$exception::class`.
  This is the standard Monolog convention (JsonFormatter normalizes it into a structured `{class,
  message, file, line, trace}` block) and is what makes the new Sentry integration able to report proper
  stack traces instead of bare strings.

### Added
- `docs/backup-and-restore.md` plus `make db-backup`/`make db-restore` (`pg_dump -Fc`/`pg_restore --clean`). Documents what actually needs backing up (PostgreSQL only — Redis, RabbitMQ, Garage, and JWT keys are all disposable, self-healing, or out of scope by design) versus what's a production-strategy decision per fork (managed-host point-in-time recovery, R2 bucket versioning). Verified with a real backup → mutate → restore round-trip, not just read.
- A `publish-image` job in `ci.yml`: builds `docker/php/Dockerfile`'s new `prod` target (self-contained — source `COPY`d in, `composer install --no-dev`, no dev tooling) and pushes it to GHCR on every push to `main`/a version tag, gated on the quality gate passing first. Uses the repo's built-in `GITHUB_TOKEN`, no registry secrets to configure. Does not include an actual deployment target (Kubernetes/ECS/etc.) — that's fork-specific by nature. See README "Building & publishing the production image".
- Error tracking via Sentry (`sentry/sentry-symfony`), opt-in and a safe no-op until `SENTRY_DSN` is set. Wired as Monolog handlers at `error` level (`config/packages/monolog.yaml`) rather than the bundle's own automatic exception listener, so it reports exactly what `ExceptionListener` already treats as a real error (unmapped/`5xx`) — never the expected `4xx` domain/validation/auth responses. See README "Error tracking (Sentry)".
- `CONTRIBUTING.md` and a GitHub pull request template (`.github/pull_request_template.md`).
- Warning when a migration column looks like a cross-BC UUID foreign key but resolves to no known table
  (`ForeignKeyRelationInferrer`), instead of silently dropping it from the ER diagram.
- Full `make help` coverage — every Makefile target now has a one-line description (was 11 of 58).
- `CLAUDE.md` now instructs Claude to add a `CHANGELOG.md` entry alongside any user-facing change.

### Fixed
- **The app could not boot in `prod` (or any env other than dev/test) at all.** `config/packages/doctrine_fixtures.yaml` had an unconditional top-level `doctrine_fixtures:` key, but `DoctrineFixturesBundle` (which provides that config extension) is only registered for `dev`/`test` — so container compilation threw a fatal `LogicException` in any other environment, on every single request. Invisible until now because nothing had ever actually run `composer install --no-dev` or booted the app outside dev/test — exactly what building the new `prod` Docker image (see "Added" above) finally exercised. Fixed by removing the redundant unconditional key (the correctly-scoped `when@dev`/`when@test` blocks already had the same config).
- `symfony/monolog-bundle` was listed under `require-dev` even though `MonologBundle` is registered unconditionally (`config/bundles.php`, `'all' => true`) and prod logging (JSON to stdout, `monolog.yaml`) depends on it — a `composer install --no-dev` would leave the class missing and crash on the first log call. Moved to `require`. Same root cause as above: no prod-shaped install had ever been exercised before.
- `SYMFONY_TRUSTED_PROXIES`/`SYMFONY_TRUSTED_HEADERS` were never wired through `docker/compose.yaml` or documented, so behind any real reverse proxy/load balancer, `Request::getClientIp()` silently returned the proxy's IP for every request — collapsing the IP-keyed auth and API rate limiters into one shared bucket. Added both env vars (empty by default, matching current behavior) and wired them through; see README "Rate limiting" and "Environment variables".
- ER diagram generator was silently omitting `tasks.assignee_id → users` and `tasks.attachment_id →
  documents`: the FK-inference heuristic only pluralizes the column's base name (`user_id → users`),
  which doesn't hold for `assignee_id`/`attachment_id`. Both are now mapped explicitly.

## 2026-08-11 – 2026-08-20 (Mercure)
### Added
- Mercure real-time push, embedded in the FrankenPHP/Caddy process — `RealtimePublisherInterface`,
  wired into the `IN_APP` notification channel, plus a realtime-token endpoint and a minimal end-to-end
  test endpoint.

## 2026-07-27 – 2026-08-11
### Added
- Global API rate limiting, Redis-backed cache and scheduler locking, an audit log for sensitive
  actions, `Idempotency-Key` support for `POST` requests, GDPR personal-data export, and the
  feature-flags system.
- Switched the runtime from nginx to FrankenPHP/Caddy.
- CORS configuration and the Apache 2.0 license.
### Changed
- PHPStan hardening pass.

## 2026-06-17 – 2026-07-15
### Added
- ER diagram generator wired into CI (`make er-diagram`), including foreign-key inference and pivot
  (many-to-many) table handling.
- `Project`/`Task` added as a second bounded context — the reference example for real same-BC Doctrine
  relations vs. cross-BC UUID references.
### Changed
- Renamed the object-storage backend from rustFS to S3-compatible naming; refactored the document
  seeder and maker-command usage.

## 2026-05-25 – 2026-06-09
### Added
- Scheduler, liveness/health checks, Prometheus monitoring.
- `Document` bounded context: S3-compatible storage (MinIO), multipart upload, presigned URLs, soft
  delete, admin bucket management.
- HATEOAS links on API responses.
- Conventional Commits + pre-commit hooks, CI pipeline.

## 2026-03-20 – 2026-05-23
### Added
- Initial DDD/Symfony scaffold and the `User` bounded context: CRUD endpoints, fixtures, JWT
  auth (login/logout/refresh token), roles and security.
- PHPStan and Deptrac architecture-boundary enforcement, PHPUnit setup.
- Transactional outbox pattern, welcome/deletion emails.
