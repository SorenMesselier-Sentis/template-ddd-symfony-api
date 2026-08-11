# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Symfony 8 / PHP 8.4 REST API template structured around Domain-Driven Design with bounded contexts. `User`, `Document`, and `Project` are the reference contexts; forks scaffold new ones with the maker commands. Base API path is `/api/v1`.

- **User** — auth, users, refresh tokens.
- **Document** — S3-compatible object storage; `OwnerId` is a UUID reference to `User` with **no** Doctrine relation (cross-BC references are always stable UUIDs, never FKs).
- **Project** — `Project`/`Task`; the counter-example to Document's rule: `Task.project` is a real Doctrine `<many-to-one>` because both entities live in the **same** BC (see "Real relations vs. cross-BC UUIDs" below), while `Task.assigneeId`/`attachmentId` (→ `User`/`Document`) stay plain UUIDs.

## Everything runs in Docker

There is no local PHP toolchain assumption: every command goes through the `php` container via the Makefile, which uses `docker compose -f docker/compose.yaml --env-file .env.local`. `.env.local` must exist (CI does `cp .env .env.local`).

```bash
make init          # build + up + composer install + db reset + fixtures (first run)
make up / down     # start / stop containers
make bash          # shell inside the PHP container (working dir /app)
```

## Commands

```bash
make ci            # full quality gate: cs-check, phpstan, deptrac, unit, integration, http
make cs-fix        # apply PHP CS Fixer (@Symfony ruleset)
make cs-check      # dry-run, fails on drift
make phpstan       # level 9, includes symfony/doctrine/strict-rules extensions
make deptrac       # architecture layer boundaries — run after every structural change

make test / test-unit / test-integration / test-http
make test-coverage # HTML report in var/coverage/
```

`test-integration` and `test-http` create + migrate the test database first. To run a single test or filter, go through the container with `APP_ENV=test`:

```bash
docker compose -f docker/compose.yaml --env-file .env.local exec -w /app -e APP_ENV=test php \
  vendor/bin/phpunit --filter=CreateUserCommandHandlerTest
```

PHPUnit is strict: `failOnDeprecation`, `failOnNotice`, `failOnWarning` are all enabled — a deprecation is a test failure.

PHPStan reads the dev container XML (`var/cache/dev/App_KernelDevDebugContainer.xml`), so run `make warmup` (or `make clear`) if `make phpstan` complains about a missing container.

```bash
make db-diff / db-migrate / db-reset / db-fresh / db-validate
make db-fixtures   # doctrine:fixtures:load
make consume       # event consumer (RabbitMQ events.* queues)
make scheduler     # Scheduler worker — drives outbox relay + daily cleanups
make outbox-relay  # one-shot manual outbox flush
make er-diagram    # regenerate docs/er-diagram.md from migrations (CI commits this on main)
```

## Architecture

Each bounded context lives in `src/<Name>/` split into `Domain/` (pure PHP: entities, value objects, repository ports, domain events, exceptions), `Application/` (one folder per use case under `Command/` or `Query/`, no framework imports), `Infrastructure/` (Symfony, Doctrine, HTTP, messaging, fixtures). Cross-cutting code is `src/Shared/` with a Domain/Infrastructure split.

Deptrac enforces the dependency direction — read `deptrac.yaml` before moving code. The two rules that bite most often:

- **`Shared/*` must never import a bounded context** (including fixtures). Cross-BC references use stable UUIDs only (e.g. `Document`'s `OwnerId` is not a Doctrine relation to `User`).
- **`Infrastructure` of BC A must never import anything from BC B.** BC-specific HTTP behaviour is injected into Shared via tagged services, not imports.

Consequences of that, and other conventions that are non-obvious from a single file:

- **Migrations are centralized** in `src/Shared/Infrastructure/Persistence/Migrations/` — never per BC. Doctrine mapping stays per-BC as **XML** in `<BC>/Infrastructure/Persistence/Doctrine/Mapping/*.orm.xml`; the domain layer carries no ORM attributes.
- **Three Messenger buses**: `command.bus` and `query.bus` are synchronous; `event.bus` is async through a transactional outbox → RabbitMQ topic exchange `events` → queue `events.<context>` (binding `<context>.#`) → handlers in `<BC>/Infrastructure/EventHandler/`. Side-effects (email, notifications) belong in event handlers, not command handlers.
- **Exception → HTTP mapping** goes through `Shared/Infrastructure/Http/Listener/ExceptionListener`, which has zero BC imports. Per-context rules live in a `<Context>ExceptionMapper` implementing `ExceptionMapperInterface`, registered in `config/services.yaml` with the `app.exception_mapper` tag. Defaults: `NotFoundException` → 404, unmapped `DomainException` → 422 `domain_error`, unknown throwable → 500. Every domain exception exposes `errorCode(): string` (e.g. `user.not_found`).
- **Authorization is declared on the message, not the URL.** Commands/queries implement `AuthorizedMessage` and return a `RoleRequirement` (`admin()`, `any(...)`, `all(...)`, `authenticated()`); `AuthorizeMessageMiddleware` enforces it on the command and query buses. Only public-vs-authenticated lives in `config/packages/security.yaml`. New roles extend `UserRole` in the User BC — never add path rules in Shared.
- **Uniform response envelope** via `Shared/Infrastructure/Http/Response/ApiResponse` (`data` / `error` / `meta` + `links` for collections). Property names serialize to `snake_case` automatically. Controllers declare `#[Route]` paths *without* the `/api/v1` prefix (applied once in `config/routes.yaml` and nelmio `servers`).
- **Soft delete everywhere**: entities carry a `status` enum with at least `active`/`deleted`; repositories exclude deleted rows rather than issuing DELETEs. The exact case set is per-entity — extend it for a richer lifecycle (e.g. `Project`: `active`/`archived`/`deleted`; `Task`: `todo`/`in_progress`/`done`/`deleted`) as long as `deleted` stays an explicit, distinct case.
- **Email** goes through the `EmailSenderInterface` port; each template is 3 Twig files (`<name>.subject|txt|html.twig`) under `templates/email/<context>/`, referenced through per-BC constant classes like `UserEmailTemplate::WELCOME`. Notifications route through `NotificationSenderInterface` / `NotificationChannel`.
- **Scheduling** is one provider: `DefaultSchedule` (`#[AsSchedule('default')]`) in `Shared/Infrastructure/Scheduler/`, stateful with `processOnlyLastMissedRun(true)`. Outbox relay every 10s, refresh-token cleanup 02:00 UTC, outbox purge 03:00 UTC. Handlers are registered on `command.bus` only and swallow+log failures so a bad tick can't kill the worker.
- **Redis backs every shared/scale-out concern**: `cache.app` (PSR-6), the API rate limiter's storage, the scheduler's stateful-schedule storage, and Prometheus metrics (`METRICS_STORAGE=redis`) all point at the same Redis instance so state is correct across `php` replicas — never assume in-memory/APCu state is visible to other replicas.
- **Real-time push (Mercure)**: the Mercure hub is embedded directly in the `php` container's FrankenPHP/Caddy process (`docker/frankenphp/Caddyfile`'s `mercure` directive) — no separate container, and `/.well-known/mercure` requests never reach the PHP/Symfony kernel. Any BC publishes via the port `Shared\Domain\RealTime\RealtimePublisherInterface` (`publish(string $topic, array $data, bool $private = true)`), backed by `MercureRealtimePublisher`. Topics are private per-user by default (`/users/{id}/...`); a frontend calls `GET /users/me/realtime-token` first (`User\Infrastructure\Http\Controller\GetRealtimeSubscriberTokenController`) to get a `mercureAuthorization` cookie scoped to its own topics, then opens an `EventSource` to the hub. Wired as a working example on the `IN_APP` notification channel (`InAppChannelNotificationHandler`) — every in-app notification is pushed live, not just logged.
- **Global API rate limiting** (`Shared/Infrastructure/Http/Listener/ApiRateLimitListener`) applies to every `/api/*` request via `config/packages/rate_limiter.yaml` (`api_default`), on top of the tighter per-endpoint auth limiters (`auth_login`, `auth_register`, `auth_forgot_password`). `429` → `RateLimitExceededException`, `Retry-After` header set automatically. Forced to `no_limit` in `test` env.
- **HTTP caching (ETag)**: `Shared/Infrastructure/Http/Listener/ConditionalGetListener` computes a strong `ETag` from the full serialized response body on every `GET`; a matching `If-None-Match` short-circuits to `304`. Applies uniformly to single resources and paginated collections — no per-entity `updatedAt` bookkeeping needed.
- **Idempotency-Key**: `Shared/Infrastructure/Http/Listener/IdempotencyKeyListener` makes every `POST` under `/api/v1` safely retryable when the client sends an `Idempotency-Key` header — the first `2xx` response is cached (Redis, 24h) and replayed verbatim on a retry with the same key; a retry with the same key but a different body is rejected with `409 idempotency_key.conflict`. Generic, no per-command opt-in.
- **Audit trail**: sensitive actions (deletions, role/permission changes, auth events) **must** implement `Shared\Domain\Audit\AuditableMessage` (`auditAction()`, `auditTargetId()`, `auditContext()`) on the command — `AuditMessageMiddleware` records to the `audit_log` table (raw DBAL, no ORM entity) after the command succeeds, never on failure. Mirrors the `AuthorizedMessage` pattern. Note the `sync` transport re-enters the whole `command.bus` chain to dispatch, so anything before the implicit `send_message`/`handle_message` pair (this middleware included) runs twice per command unless it deduplicates via a stamp — see `AuditProcessingStamp`.
- **GDPR data export is mandatory for personal data**: any BC that stores personal data about a user **must** implement `Shared\Domain\Privacy\PersonalDataExporterInterface` (`key()`, `export(string $subjectId): array`) so `GET /users/me/export` includes it — auto-tagged `app.gdpr_data_exporter` via `_instanceof` in `config/services.yaml`, no other wiring needed. See `User\Application\Privacy\UserPersonalDataExporter` / `Document\Application\Privacy\DocumentPersonalDataExporter`.
- **Feature flags** (optional, use when a change needs a runtime kill switch or gradual rollout): a `feature_flags` table (raw DBAL) backs `Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface::isEnabled(string $key)`. To gate an entire command/query, implement `FeatureGatedMessage` (`requiredFeatureFlag(): string`) — `FeatureFlagMessageMiddleware` (on both `command.bus` and `query.bus`, right after authorization) rejects with `403 feature_flag.disabled` while off. Admin API: `GET`/`PUT /feature-flags/{key}`. Example: `GetUsersCursorQuery`/`GetDocumentsCursorQuery` are gated by `cursor_pagination`.
- **Cursor (keyset) pagination** (optional, opt-in per request via `?pagination=cursor`, alongside the default `page`/`limit`): for large or fast-changing collections. Pattern lives in `Shared/Domain/Filter/` (`Cursor`, `CursorPagination`, `CursorPage`) — copy `DoctrineUserRepository::findByFiltersCursor()` / `GetUsersController`'s `pagination=cursor` branch when adding it to a new BC; `make crud`-scaffolded contexts get `page`/`limit` only unless you add this by hand.
- **Real relations vs. cross-BC UUIDs**: within a single BC, a genuine Doctrine relation (`<many-to-one>`, etc.) between two of its own entities is correct DDD, not a violation — only *cross-BC* references must stay UUID-only (see `Project.Task` → `Project.Project`). If the related entity's identifier property is `readonly` (the norm here), map the relation `fetch="EAGER"`, not the Doctrine default `LAZY`: lazy ghost-object hydration needs to partially set the identifier before the rest of the proxy initializes, which throws `LogicException: Attempting to change readonly property ...::$id` against a `readonly` id.

## Scaffolding

Never hand-create a bounded context or CRUD entity — the makers also patch config (routes, Doctrine mapping + custom types, repository aliases, RabbitMQ bindings):

```bash
make bc name=Product                        # make:bounded-context
make crud context=Product entity=Product    # make:bc-crud (Domain+Application+Infra+tests)
make remove-crud context=Product entity=Product
make remove-bc name=Product                 # User, Document, Shared are protected
```

Generators live in `src/Shared/Infrastructure/Console/Generator/`. After generating: add business fields to entity + mapping, write the exception mapper if a non-default status is needed, `make db-diff` → `make db-migrate`, then `make ci`. The full checklist is `docs/ddd-conventions.md` — follow it rather than reinventing per-context structure.

Also check, per new entity:
- **Holds personal data about a user?** → implement `PersonalDataExporterInterface` (mandatory, see Architecture above).
- **Exposes a sensitive action** (delete, permission/role change, auth event)? → implement `AuditableMessage` on that command (mandatory for that action).
- **References another entity in the same BC?** → real Doctrine relation, `fetch="EAGER"` if its id is `readonly`. **References an entity in another BC?** → UUID field only, never a relation.

## Tests

`tests/Unit/<BC>/` (domain + application, no I/O), `tests/Integration/<BC>/` (real Postgres / Garage), `tests/Http/<BC>/` (full stack). HTTP tests extend `tests/Http/HttpTestCase.php`, which resets the DB, reloads fixtures, and offers `createAuthenticatedClient('admin'|'user')` using credentials from `FixtureData`.

Fixtures live per-BC in `<BC>/Infrastructure/Fixture/` and are auto-discovered — there is deliberately **no orchestrator in Shared**. Stable IDs/emails come from `Shared/Infrastructure/Fixture/FixtureData`, reference keys from `FixtureReference`; cross-BC links use `FixtureData` UUID constants so load order never matters.

## Conventions

- Commit messages must be Conventional Commits (enforced by `conventional-pre-commit` in `.pre-commit-config.yaml`, which also blocks `.env.local`, JWT keys, and private keys).
- JWT keys in `config/jwt/*.pem` are gitignored and must be generated locally (see README "Reproduce the CI pipeline locally").
- CI (`.github/workflows/ci.yml`) boots the real Docker stack and runs `make ci`; it also regenerates and commits `docs/er-diagram.md` on pushes to `main`.

## API conventions (RESTful + HATEOAS)

### Response envelope
Every response uses `Shared/Infrastructure/Http/Response/ApiResponse`: `data` / `error` / `meta`, plus `links` on collection responses. All properties serialize to `snake_case`.

### HATEOAS links
- Every resource representation includes a `links` object with at minimum a `self` relation.
- Related resources are exposed as links, not embedded objects, unless explicitly required by a use case (avoid over-fetching).
- Link relation names follow IANA-registered rels where one exists (`self`, `next`, `prev`, `first`, `last`); use a project-specific rel (e.g. `owner`, `documents`) only when no standard one fits.
- Collections expose pagination links (`next`/`prev`) computed from the current filter/sort/page state — never hardcode query params, always build from the current request.

### Status codes & idempotency
- `GET` — 200, never mutates.
- `POST` (create) — 201 with `Location` header pointing to the created resource's `self` link.
- `PUT` — full replace, idempotent, 200 (or 204 if no body returned).
- `PATCH` — partial update, idempotent at the resource level, 200.
- `DELETE` — 204, soft delete (see "Soft delete everywhere" above) — never a hard delete via this path.

### Versioning
- URL-based, `/api/v1` prefix applied once in `config/routes.yaml`. A breaking change to a resource's shape means a new version prefix, not a mutation of `/v1`.

### Errors
- Always through `ExceptionListener` + per-context `ExceptionMapper` (see Architecture section above) — never a raw framework exception response.
- Error body shape: `{ "error": { "code": "<context>.<reason>", "message": "..." } }`, `code` always matches the domain exception's `errorCode()`.
