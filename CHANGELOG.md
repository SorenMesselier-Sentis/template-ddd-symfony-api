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

### Added
- `CONTRIBUTING.md` and a GitHub pull request template (`.github/pull_request_template.md`).
- Warning when a migration column looks like a cross-BC UUID foreign key but resolves to no known table
  (`ForeignKeyRelationInferrer`), instead of silently dropping it from the ER diagram.
- Full `make help` coverage — every Makefile target now has a one-line description (was 11 of 58).
- `CLAUDE.md` now instructs Claude to add a `CHANGELOG.md` entry alongside any user-facing change.

### Fixed
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
