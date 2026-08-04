# Symfony DDD API Template

A production-ready REST API template built with Symfony 8 and Domain-Driven Design principles.

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Symfony 8.0 |
| ORM | Doctrine ORM |
| Database | PostgreSQL 16 |
| Message Bus | Symfony Messenger |
| Queue | RabbitMQ |
| Cache / shared state | Redis 7 — cache pool, rate limiter storage, scheduler state, Prometheus metrics |
| Scheduler | Symfony Scheduler (cron + periodic) |
| Mailer | Symfony Mailer + Twig templates, Mailpit for dev |
| Logging | Monolog |
| Monitoring | Prometheus + Grafana (preconfigured scrape targets + starter dashboard) |
| Object storage | RustFS (S3-compatible) `rustfs/rustfs:1.0.0-beta.8`, AWS SDK for PHP (`aws/aws-sdk-php`) |
| API documentation | NelmioApiDocBundle, OpenAPI 3, Swagger UI (Twig + Asset) |

> **RustFS beta status:** RustFS is currently in beta (`1.0.0-beta.x`). This template uses it for local development and integration testing. Production adopters should independently evaluate maturity, licensing (Apache 2.0), and operational fit before relying on it in production.

## Architecture

This template follows Domain-Driven Design (DDD) principles with a clear separation of concerns across three layers per Bounded Context.

```
src/
├── Shared/                         # Cross-cutting concerns
│   ├── Domain/
│   │   ├── Bus/                    # Command, Query, Event bus interfaces
│   │   ├── Email/                  # EmailSenderInterface, EmailMessage, EmailTemplateRendererInterface, RenderedEmailContent
│   │   ├── Notification/           # Notification (interface), EmailNotification, InAppNotification, NotificationChannel, NotificationSenderInterface
│   │   ├── Filter/                 # Filter, Filters, Order, Pagination value objects
│   │   ├── ValueObject/            # Uuid, Email
│   │   ├── Exception/              # Base domain exceptions (incl. EmailDeliveryException, UnsupportedChannelException)
│   │   └── Logging/                # Logger interface
│   └── Infrastructure/
│       ├── Bus/                    # Symfony Messenger implementations
│       ├── Email/                  # SymfonyMailerEmailSender, TwigEmailTemplateRenderer
│       ├── Notification/           # ChannelNotificationSender + per-channel Handler/
│       ├── Scheduler/              # DefaultSchedule (#[AsSchedule('default')]) + Message/ + Handler/
│       ├── Http/
│       │   ├── Filter/             # FiltersBuilder — parses query string into Filters
│       │   ├── Listener/           # ExceptionListener, ApiHeadersListener
│       │   ├── Request/            # JsonRequest base class
│       │   └── Response/           # ApiResponse (success, created, paginated, noContent)
│       ├── Logging/                # Monolog implementation
│       ├── Monitoring/             # Prometheus, OpenTelemetry
│       ├── Messaging/
│       │   └── Outbox/             # OutboxEventBus, OutboxRelay, OutboxMessagesCleaner
│       └── Persistence/
│           ├── Migrations/         # All migrations centralized here
│           └── Doctrine/
│               ├── Type/           # Custom Doctrine types
│               └── DoctrineFilterApplier.php  # Applies Filters to a QueryBuilder
│
├── User/                           # Authentication, users, refresh tokens
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
│       ├── Fixture/UserFixture.php
│       ├── EventHandler/           # e.g. SendWelcomeEmailOnUserCreated
│       ├── Scheduler/              # e.g. CleanupExpiredRefreshTokensHandler
│       └── Http/Controller/        # /users, /auth/*
│
├── Document/                       # Object storage (RustFS) — metadata in DB, files in buckets
│   ├── Domain/
│   │   ├── Entity/Document.php
│   │   ├── ValueObject/            # DocumentId, OwnerId, BucketName, MimeType, PresignedUrl, …
│   │   ├── Repository/DocumentRepositoryInterface.php
│   │   ├── Storage/                # DocumentStorageInterface, BucketManagerInterface (ports)
│   │   └── Exception/
│   ├── Application/
│   │   ├── Command/                # UploadDocument, DeleteDocument, CreateBucket, …
│   │   └── Query/                  # GetDocuments, GetDocumentPresignedUrl, ListBuckets, …
│   └── Infrastructure/
│       ├── Fixture/DocumentFixture.php
│       ├── Persistence/Doctrine/   # XML mapping + DoctrineDocumentRepository
│       ├── Storage/                # S3DocumentStorageAdapter, S3BucketManager, S3BucketExistenceChecker
│       ├── Health/ObjectStorageHealthCheck.php
│       └── Http/Controller/        # /documents, /buckets
│
└── <BoundedContext>/               # e.g. Product, Order — scaffold with `make bc`, entities with `make crud`
    ├── Domain/                     # Pure PHP — no framework dependency
    │   ├── Entity/
    │   ├── ValueObject/
    │   ├── Repository/             # Interfaces only
    │   ├── Event/                  # Domain events
    │   └── Exception/
    ├── Application/                # Use cases
    │   ├── Command/
    │   └── Query/
    └── Infrastructure/             # Framework & persistence
        ├── Fixture/                # Doctrine fixtures (dev & test)
        ├── Persistence/
        │   └── Doctrine/
        │       ├── Mapping/        # XML mapping files
        │       └── Repository/
        ├── Messaging/              # RabbitMQ consumers
        ├── EventHandler/           # Async handlers reacting to domain events
        ├── Email/                  # Per-BC template constants (e.g. UserEmailTemplate)
        ├── Scheduler/              # Per-BC Scheduler handlers
        └── Http/
            ├── Controller/
            └── Request/
```

Twig email templates live outside `src/`:

```
templates/email/
├── layout.html.twig                # Shared HTML layout extended by every transactional email
└── <context>/
    └── <template>.{subject,txt,html}.twig
```

### Key design decisions

**Migrations are centralized** in `Shared/Infrastructure/Persistence/Migrations/`. Doctrine mappings stay in each Bounded Context — migrations are a global infrastructure concern.

**Doctrine mapping lives in XML** under each bounded context's `Infrastructure/Persistence/Doctrine/Mapping/` folder. The domain layer stays free of ORM attributes; only infrastructure owns mapping files.

**Three separate Messenger buses** — commands and queries are handled synchronously, domain events are dispatched asynchronously through RabbitMQ.

**Domain exceptions map to HTTP status codes** via a single `ExceptionListener` in `Shared/Infrastructure/Http/Listener/`, keeping HTTP concerns out of the domain. Messenger's `HandlerFailedException` is automatically unwrapped so domain exceptions propagate correctly.

**Uniform API response format** — all responses go through `ApiResponse` which wraps data under a `data` key, errors under an `error` key, and paginated results include a `meta` block. Property names are serialized to `snake_case` automatically via Symfony's `CamelCaseToSnakeCaseNameConverter`.

**Soft delete** — entities are never physically removed. A `status` field tracks their lifecycle (`active`, `inactive`, `deleted`). Repositories automatically exclude deleted records from queries.

**Transactional email** — bounded contexts depend only on the domain port `EmailSenderInterface` (in `Shared/Domain/Email/`). The infrastructure implementation `SymfonyMailerEmailSender` wraps Symfony Mailer and uses the `MAILER_DSN` / `MAILER_FROM` environment variables. Twig templates follow a 3-file convention per template name — `<name>.subject.twig`, `<name>.txt.twig`, `<name>.html.twig` — rendered by `TwigEmailTemplateRenderer` and returned as an immutable `RenderedEmailContent`. Each bounded context references templates through a named-constant class (e.g. `UserEmailTemplate::WELCOME`) rather than raw strings.

**Channel-agnostic notifications** — `NotificationSenderInterface` (in `Shared/Domain/Notification/`) lets the domain dispatch a `Notification` without knowing its delivery channel. `ChannelNotificationSender` resolves the right `NotificationChannelHandler` from the `NotificationChannel` enum (`email`, `in_app`). For the `email` channel, the handler delegates to `EmailSenderInterface` so the mailer stays a single integration point. This is the path used by `SendWelcomeEmailOnUserCreated` and `SendAccountDeletionEmailOnUserDeleted`, both registered as async `event.bus` handlers in `User/Infrastructure/EventHandler/`.

**Scheduled recurring tasks** — `DefaultSchedule` (in `Shared/Infrastructure/Scheduler/`) is the single `#[AsSchedule('default')]` provider for the application. It is stateful (`stateful(cache)` + `processOnlyLastMissedRun(true)`), so missed ticks during a worker restart are recovered without flooding. It is also `lock()`-guarded with a PostgreSQL advisory lock (`symfony/lock`, DSN in `LOCK_DSN`, `postgresql+advisory://...`): if the `scheduler` worker is scaled to more than one replica, only the replica holding the lock generates and dispatches due messages, so recurring tasks never run twice concurrently. The schedule currently registers three tasks:

| Cadence | Task | Handler |
|---|---|---|
| every 10 seconds | `RelayOutboxMessages` — publishes pending `outbox_messages` rows to RabbitMQ via the existing `OutboxRelay` | `Shared/Infrastructure/Scheduler/Handler/RelayOutboxMessagesHandler` |
| daily at 02:00 UTC | `CleanupExpiredRefreshTokens` — deletes expired refresh tokens via `RefreshTokenRepositoryInterface::deleteExpired()` | `User/Infrastructure/Scheduler/CleanupExpiredRefreshTokensHandler` |
| daily at 03:00 UTC | `CleanupStaleOutboxMessages` — purges published outbox rows older than `OUTBOX_RETENTION_DAYS` (default 30) via `OutboxMessagesCleaner` | `Shared/Infrastructure/Scheduler/Handler/CleanupStaleOutboxMessagesHandler` |

Handlers are registered on `command.bus` only (no auto-broadcast to other buses). Failures are logged via `LoggerInterface` and swallowed so a single bad tick never crashes the scheduler worker. The manual `make outbox-relay` and `app:outbox:relay` console command remain available for on-demand triggering.

**Object storage (Document BC)** — file bytes live in S3-compatible object storage (RustFS in Docker); the `Document` aggregate stores metadata only (name, size, MIME type, bucket, object path, owner ID, status). Bounded contexts interact through domain ports (`DocumentStorageInterface`, `BucketManagerInterface`); infrastructure adapters use the AWS SDK for PHP against the configured `S3_ENDPOINT`. `OwnerId` is a UUID reference with no foreign key to the User BC, preserving bounded-context isolation. Presigned URLs delegate direct download to object storage without streaming through PHP.

**OpenAPI** is generated from PHP attributes (`OpenApi\Attributes`) on HTTP controllers. Paths in attributes are relative to the API base (`/users`, `/auth/login`, …); the `/api/v1` prefix is configured once in `config/packages/nelmio_api_doc.yaml` (`servers`) and in `config/routes.yaml`. Swagger UI is served at `/api/doc` and the JSON spec at `/api/doc.json`. The health check (`GET /health`) is documented under the **Infrastructure** tag with the root server.

### Security

Authentication and authorization are split across layers:

| Layer | Responsibility | Location |
|---|---|---|
| Infrastructure (HTTP) | Who is connected? Public vs authenticated routes | `config/packages/security.yaml`, `JwtAuthenticator`, `JsonAuthenticationEntryPoint` |
| Application (User BC) | Which roles can run this use case? | `RoleRequirement`, `UserAuthorizer`, `AuthorizedMessage` on commands/queries |
| Domain (User BC) | Vocabulary (`UserRole`, `UserContextInterface`, exceptions) | `src/User/Domain/` |

**Public routes** (no JWT required) are declared only in `security.yaml`: login, refresh, and API documentation. On these routes, an invalid or stale `Authorization` header from the browser (e.g. Swagger UI) is ignored so the page still loads without a frontend.

**Protected routes** require a valid JWT. The firewall authenticates the request; `HttpUserContext` reads the authenticated user from Symfony Security (roles come from the database via `SecurityUserProvider`).

**Per-use-case authorization** is declared on the message, not on URL paths. Commands and queries that need authorization implement `AuthorizedMessage` and return a `RoleRequirement`:

```php
// Single role (admin only)
public function roleRequirement(): RoleRequirement
{
    return RoleRequirement::admin();
}

// Multiple roles — user needs at least one (ANY)
public function roleRequirement(): RoleRequirement
{
    return RoleRequirement::any(UserRole::ADMIN, UserRole::MANAGER);
}

// Multiple roles — user needs all of them (ALL)
public function roleRequirement(): RoleRequirement
{
    return RoleRequirement::all(UserRole::ADMIN, UserRole::AUDITOR);
}

// Any authenticated user
public function roleRequirement(): RoleRequirement
{
    return RoleRequirement::authenticated();
}
```

`AuthorizeMessageMiddleware` on the command and query buses enforces the requirement before the handler runs.

To add a new role, extend the `UserRole` enum in the User bounded context and use it in `RoleRequirement` on the relevant command or query. Do not add path-based rules in Shared.

### HTTP caching (ETag)

`ConditionalGetListener` (`Shared/Infrastructure/Http/Listener/`) adds a strong `ETag` — an MD5 hash of the raw response body — to every successful (`2xx`) `GET` response under `/api/v1`, and sets `Cache-Control: private, no-cache` so the response is never stored by shared/CDN caches but the client is allowed to keep a local copy that it must revalidate on every reuse.

Because the hash covers the whole response body, this works uniformly for single resources and paginated collections alike (a `links`/`meta` or pagination-state change also changes the ETag) without any per-entity `updatedAt` bookkeeping — this is why the template only implements `ETag`, not `Last-Modified`: per RFC 7232, a validator-aware client already prefers the strong `ETag` over `Last-Modified` when both are present, so adding the latter would be extra bookkeeping for the same outcome.

When a client resends the same `ETag` via `If-None-Match`, the listener short-circuits the response to `304 Not Modified` (empty body, `Content-Type` stripped per spec, `ETag` kept) using `Symfony\Component\HttpFoundation\Response::isNotModified()` — no controller changes required. `/health`, `/metrics` and `/api/doc*` are outside `/api/v1` and are never touched.

### Rate limiting

Two layers of Symfony RateLimiter policies protect the API, both defined in `config/packages/rate_limiter.yaml` (disabled — `no_limit` — in the `test` environment):

| Limiter | Scope | Policy | Keyed by |
|---|---|---|---|
| `auth_login`, `auth_forgot_password`, `auth_register` | The three brute-force-sensitive `POST` endpoints (`AuthRateLimitListener`, User BC) | 5/3/3 requests per 15 minutes | Client IP |
| `api_default` | Every `/api/v1/*` request (`ApiRateLimitListener`, Shared) | 300 requests per minute | Authenticated user id, falling back to client IP for anonymous requests |

`/health`, `/health/live`, `/metrics` and `/api/doc*` are outside `/api/v1` and are never throttled — scraping and liveness checks must stay unaffected. `ApiRateLimitListener` runs on `kernel.controller` (after the security firewall authenticates the request) so it can key by user identity rather than IP whenever possible.

A rejected request raises `Shared\Domain\Exception\RateLimitExceededException`, mapped by `ExceptionListener` to `429` with the standard `{ "error": { "code": "rate_limit.exceeded", "message": "..." } }` body plus a `Retry-After` header (seconds).

### Audit trail

Sensitive actions are recorded to a queryable `audit_log` table (centralized migration, `Shared/Infrastructure/Persistence/Migrations/`) instead of only Monolog — who did what, to what, and when:

Declared on the message, the same way authorization is: a command implements `Shared\Domain\Audit\AuditableMessage` (`auditAction(): string`, `auditTargetId(): string`, `auditContext(): array`). `AuditMessageMiddleware` (`command.bus`) records an `AuditEntry` (actor id from Symfony Security — `null` for unauthenticated actions like login — action, target, context, timestamp) after the command has actually succeeded; nothing is written on failure. Currently wired on `UpdateUserRolesCommand` (`user.roles_updated`), `DeleteUserCommand` (`user.deleted`), `LoginUserCommand` (`user.logged_in`) and `DeleteDocumentCommand` (`document.deleted`) — add `AuditableMessage` to any other command that needs a trail.

> **Sync-transport gotcha:** every command is routed to the `sync` transport (`config/packages/messenger.yaml`), which re-enters `command.bus`'s full middleware chain to actually dispatch the command — so anything positioned before the implicit `send_message`/`handle_message` pair (as `AuditMessageMiddleware` is, right after `AuthorizeMessageMiddleware`) runs twice per command unless it deduplicates. `AuditMessageMiddleware` does so via a marker stamp (`AuditProcessingStamp`) that survives the re-entry; other side-effecting middleware added to `command.bus` in the future needs the same treatment (existing ones — auth checks, the Doctrine transaction — tolerate the double pass only because they're idempotent).

### CORS

`CorsListener` (`Shared/Infrastructure/Http/Listener/`) answers preflight `OPTIONS` requests and adds CORS headers to every response, driven by a single env var:

```bash
# Comma-separated list of allowed origins, or * for all (no credentials/cookies are used, so * is safe here)
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://app.example.com
```

Left empty (the `.env` default), no cross-origin browser request is allowed — same-origin and non-browser clients (curl, server-to-server) are unaffected, since CORS is a browser-enforced mechanism. `Location` (returned on `201 Created`) and `X-Request-Id` are exposed via `Access-Control-Expose-Headers` so frontend JS can read them; a `Vary: Origin` header is added whenever a specific origin (not `*`) is reflected, so intermediary caches don't serve one origin's CORS headers to another.

## Getting started

### Requirements

- Docker
- Docker Compose
- Make

### Installation

```bash
git clone <repository-url>
cd <project-name>
cp .env .env.local        # then edit .env.local with your secrets
make init
```

`make init` will build the Docker images, start the containers, install Composer dependencies, create the database and run all migrations.

### Pre-commit hooks (recommended)

Git hooks are **optional** but recommended to catch formatting drift and accidental secret commits before they reach CI.

Install [pre-commit](https://pre-commit.com/) once per machine, then enable the hooks for this repository:

```bash
pip install pre-commit   # or: brew install pre-commit
pre-commit install --install-hooks   # installs pre-commit + commit-msg hooks
```

On every `git commit`, the configured hooks will:

- run **PHP CS Fixer** in dry-run mode on staged PHP files (same rules as `make cs-check`)
- block commits that include sensitive files (`.env.local`, `config/jwt/*.pem`, decrypted Symfony secrets, …)
- run **detect-private-key** on staged content
- validate the **commit message** against [Conventional Commits](https://www.conventionalcommits.org/) (`feat: …`, `fix: …`, `chore: …`, etc.)

Allowed types: `build`, `chore`, `ci`, `docs`, `feat`, `fix`, `perf`, `refactor`, `revert`, `style`, `test`.

Examples:

```bash
git commit -m "feat(document): add multipart upload endpoints"
git commit -m "fix(user): reject expired refresh tokens"
git commit -m "chore: update README CI section"
```

Run file checks manually against the full tree:

```bash
pre-commit run --all-files
```

Test a commit message without committing:

```bash
echo "feat: example message" | pre-commit run conventional-pre-commit --hook-stage commit-msg --commit-msg-filename /dev/stdin
```

When the PHP container is running (`make up`), hooks execute PHP CS Fixer inside Docker (PHP 8.4). Otherwise they fall back to `vendor/bin/php-cs-fixer` on the host. You can also check style directly with Composer dependencies installed locally:

```bash
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff
```

With Docker only, use `make cs-check` instead.

### Environment variables

Copy `.env` to `.env.local` and fill in your secrets. Never commit `.env.local`.

```bash
# Database
POSTGRES_PASSWORD=your_password

# RabbitMQ
RABBITMQ_PASSWORD=your_password

# Redis (cache pool, rate limiter storage, scheduler state, metrics — see
# "Cache and scale-out (Redis)")
REDIS_PASSWORD=your_password

# Grafana
GRAFANA_ADMIN_PASSWORD=your_password

# Mailer (Mailpit by default in dev — override per environment)
MAILER_DSN=smtp://mailpit:1025
MAILER_FROM=noreply@example.com

# Outbox cleanup retention (days). Values <1 fall back to 30 with a warning log.
OUTBOX_RETENTION_DAYS=30

# S3-compatible object storage (RustFS in Docker — Document BC)
# Application settings consumed by PHP adapters:
S3_ACCESS_KEY=rustfsadmin
S3_SECRET_KEY=your_password
S3_ENDPOINT=http://rustfs:9000
S3_USE_SSL=false
S3_API_PORT=9000
S3_CONSOLE_PORT=9001
S3_PRESIGNED_URL_TTL=3600

# RustFS container settings (wired in docker/compose.yaml; credentials mirror S3_*):
# RUSTFS_ACCESS_KEY=${S3_ACCESS_KEY}
# RUSTFS_SECRET_KEY=${S3_SECRET_KEY}
# RUSTFS_ADDRESS=0.0.0.0:9000
# RUSTFS_CONSOLE_ADDRESS=0.0.0.0:9001
# RUSTFS_CONSOLE_ENABLE=true
# RUSTFS_VOLUMES=/data
```

| Variable | Scope | Description |
|---|---|---|
| `S3_ENDPOINT` | PHP app | S3 API URL used by adapters and health check (Docker: `http://rustfs:9000`) |
| `S3_ACCESS_KEY` | PHP app + RustFS | Access key for S3 API authentication |
| `S3_SECRET_KEY` | PHP app + RustFS | Secret key for S3 API authentication |
| `S3_USE_SSL` | PHP app | Enable TLS for the S3 client (`true` / `false`) |
| `S3_API_PORT` | Docker host | Host port mapped to RustFS S3 API (container port `9000`) |
| `S3_CONSOLE_PORT` | Docker host | Host port mapped to RustFS management console (container port `9001`) |
| `S3_PRESIGNED_URL_TTL` | PHP app | Default presigned download URL TTL in seconds (60–604800) |
| `RUSTFS_ACCESS_KEY` | RustFS container | Mirrors `S3_ACCESS_KEY` (set in `docker/compose.yaml`) |
| `RUSTFS_SECRET_KEY` | RustFS container | Mirrors `S3_SECRET_KEY` (set in `docker/compose.yaml`) |
| `RUSTFS_ADDRESS` | RustFS container | S3 API listen address inside the container (`0.0.0.0:9000`) |
| `RUSTFS_CONSOLE_ADDRESS` | RustFS container | Console listen address inside the container (`0.0.0.0:9001`) |
| `RUSTFS_CONSOLE_ENABLE` | RustFS container | Enable the management console (`true`) |
| `RUSTFS_VOLUMES` | RustFS container | Data directory path inside the container (`/data`) |

See `.env` for the full list of available variables.

### Migrating from MinIO

If you run an existing deployment that still uses MinIO, update application configuration and copy object data to RustFS. **Automated Docker volume migration is out of scope for this template** — operators are responsible for moving blobs and updating environment variables.

#### Configuration mapping

| Legacy (MinIO) | New (application) | New (RustFS container) |
|---|---|---|
| `MINIO_ENDPOINT` | `S3_ENDPOINT` | — |
| `MINIO_ACCESS_KEY` / `MINIO_ROOT_USER` | `S3_ACCESS_KEY` | `RUSTFS_ACCESS_KEY` |
| `MINIO_SECRET_KEY` / `MINIO_ROOT_PASSWORD` | `S3_SECRET_KEY` | `RUSTFS_SECRET_KEY` |
| `MINIO_USE_SSL` | `S3_USE_SSL` | — |
| `MINIO_API_PORT` | `S3_API_PORT` | — |
| `MINIO_CONSOLE_PORT` | `S3_CONSOLE_PORT` | — |
| `MINIO_PRESIGNED_URL_TTL` | `S3_PRESIGNED_URL_TTL` | — |

Point `S3_ENDPOINT` at your RustFS S3 API URL (e.g. `http://rustfs:9000` inside Docker, or the external endpoint in production). The RustFS container reads credentials from `RUSTFS_ACCESS_KEY` / `RUSTFS_SECRET_KEY`, which mirror `S3_ACCESS_KEY` / `S3_SECRET_KEY` in `docker/compose.yaml`.

> **Health check rename:** readiness probes that scrape `/health` by check name must look for `object_storage` instead of the legacy `minio` key.

#### Object data migration

Copy buckets and objects from the old MinIO endpoint to RustFS with any S3-compatible tool, for example:

```bash
# AWS CLI (sync via a local staging directory, per bucket)
aws --endpoint-url http://old-minio:9000 s3 sync s3://my-bucket ./staging/my-bucket
aws --endpoint-url http://new-rustfs:9000 s3 sync ./staging/my-bucket s3://my-bucket

# rclone (configure two remotes, then sync)
rclone sync minio:source-bucket rustfs:source-bucket

# MinIO Client (mc) or rustfs-cli
mc mirror old-minio/source-bucket rustfs/source-bucket
```

#### Database metadata

Existing PostgreSQL `documents` rows remain valid after the switch: no schema change is required. Ensure every `bucket` and `object_path` referenced in the database exists in RustFS (sync object data before or during cutover). Soft-deleted documents may still reference objects that were purged from storage — that behaviour is unchanged.

## Development

```bash
make up           # start containers
make down         # stop containers
make bash         # open a shell in the PHP container
make logs         # tail all container logs
make logs-php     # tail PHP logs only
make restart      # stop then start containers (pick up image / config changes)
```

### Cache and scale-out (Redis)

```bash
make clear        # Symfony cache:clear
```

Redis (`docker/compose.yaml`, `redis:7-alpine`, password-protected via `REDIS_PASSWORD`) backs everything that must stay **consistent across `php` replicas** once the service is scaled beyond a single container:

| Consumer | Config | Why it needs Redis |
|---|---|---|
| `cache.app` pool | `config/packages/cache.yaml` (`framework.cache.app: cache.adapter.redis`) | Symfony's default is a filesystem pool, local to one container |
| Rate limiter storage (`api_default`, `auth_login`, …) | `cache.rate_limiter` inherits from `cache.app` | With a local pool, each replica keeps its own counters — the effective limit multiplies by the replica count |
| Scheduler missed-run state | `DefaultSchedule::stateful()`, injected `CacheInterface` resolves to `cache.app` | Keeps "last processed tick" consistent no matter which replica the lock (see `LOCK_DSN`) lets run |
| Prometheus metrics | `METRICS_STORAGE=redis` (`PrometheusCollectorRegistryFactory`) | APCu/in-memory storage is per-process; a load-balanced scrape would otherwise see disjoint, undercounted samples |

`when@test` overrides `cache.app` back to `cache.adapter.filesystem` (see `config/packages/cache.yaml`) — tests don't need cross-replica state and shouldn't depend on a reachable Redis; rate limiter policies are separately forced to `no_limit` in test (`config/packages/rate_limiter.yaml`) and `METRICS_STORAGE=in_memory` in `.env.test`.

Single-container/dev-only deployments can still set `METRICS_STORAGE=apcu` and drop `framework.cache.app` back to its Symfony default — Redis only becomes a correctness requirement once you actually run more than one `php`/`scheduler` replica.

### Database

```bash
make db-migrate   # run pending migrations
make db-rollback  # rollback last migration
make db-diff      # generate migration from schema diff (clears cache first)
make db-reset     # drop, recreate and migrate
make db-validate  # validate Doctrine mapping
make db-fixtures  # load fixtures (dev only)
make db-fresh     # db-reset + db-fixtures in one command
```

### Fixtures and test data

Doctrine fixtures live in each bounded context (`User/Infrastructure/Fixture/`, `Document/Infrastructure/Fixture/`). Shared conventions keep dev and test data in sync:

| Class | Location | Purpose |
|---|---|---|
| `FixtureReference` | `Shared/Infrastructure/Fixture/` | Stable Doctrine reference keys (`user.john`, `document.john.invoice`, …) used with `addReference()` / `getReference()` |
| `FixtureData` | `Shared/Infrastructure/Fixture/` | Stable values (UUIDs, emails, default password) reused by fixtures and HTTP tests |

Each bounded context registers its own fixture class under `<BC>/Infrastructure/Fixture/`. Doctrine auto-discovers every fixture under `src/` — there is **no orchestrator in Shared** (Deptrac forbids `Shared/Infrastructure` from importing other bounded contexts).

Cross-BC fixture data uses shared UUIDs from `FixtureData` only (e.g. `DocumentFixture` sets `ownerId` to `FixtureData::USER_JOHN_ID` without a foreign key or a `getReference()` call to `UserFixture`). Because there is no cross-BC entity dependency, fixtures can load in any order.

If a future bounded context must persist rows that truly depend on another BC's entities, use **fixture groups** (`FixtureGroupInterface::getGroups()`) and load groups explicitly:

```bash
php bin/console doctrine:fixtures:load --group=user --no-interaction
php bin/console doctrine:fixtures:load --group=document --append --no-interaction
```

HTTP integration tests reset the database and reload all fixtures before each test (`HttpTestCase::resetDatabase()`), then authenticate using credentials from `FixtureData` (e.g. `USER_JOHN_EMAIL` / `DEFAULT_PASSWORD` for admin, `USER_JANE_EMAIL` for a regular user).

### Email (local)

Mailpit captures outgoing emails in development (SMTP `1025`, UI `8025`).

```bash
make mail         # open Mailpit UI in the browser
make metrics      # open Prometheus UI
make grafana      # open Grafana UI
```

See [docs/testing-emails.md](docs/testing-emails.md) for the full flow (API → outbox → RabbitMQ → Twig templates → Mailpit).

### Messenger workers

```bash
make consume          # start the event consumer (drains RabbitMQ events.* queues)
make consume-dl       # start the dead letter consumer
make scheduler        # start the Scheduler worker — drives outbox relay + daily cleanups
make outbox-relay     # one-shot: publish persisted outbox events to the event bus
make messenger-stop   # gracefully stop all workers
make messenger-stats  # display transport stats
make messenger-failed-show   # list failed messages
make messenger-failed-retry  # retry all failed messages
make messenger-failed-remove # remove all failed messages
```

In production, `make consume` and `make scheduler` should be supervised as long-running processes (systemd, supervisord, etc.) — `make outbox-relay` is then only kept as an operator escape hatch for manual triggering. The `--time-limit=3600` in both targets is the standard Symfony pattern for safe restarts.

### Scaffolding bounded contexts and CRUD entities

The maker commands are split into two steps: create the bounded context skeleton, then generate CRUD entities inside it.

#### 1. Create a bounded context

```bash
make bc name=Product
# optional API version: bin/console make:bounded-context Product --api-version=v1
```

This creates the DDD folder structure under `src/Product/` and auto-registers:

- HTTP routes in `config/routes.yaml` (`api_<version>_<context>`)
- Doctrine XML mapping entry in `config/packages/doctrine.yaml` (BC-level)

```
src/Product/
├── Domain/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Repository/
│   ├── Event/
│   └── Exception/
├── Application/
│   ├── Command/
│   └── Query/
└── Infrastructure/
    ├── Persistence/Doctrine/
    │   ├── Mapping/
    │   ├── Repository/
    │   └── Type/
    ├── Http/
    │   ├── Controller/
    │   └── Request/
    ├── Fixture/
    └── Messaging/
```

#### 2. Generate a CRUD entity

```bash
make crud context=Product entity=Product
# multiple entities in the same context:
make crud context=Order entity=OrderLine
```

This generates the full CRUD stack for the entity and auto-registers:

- Domain entity, value objects, repository port, events, exceptions
- Application commands and queries (Create, Update, Replace, Delete, Get, List)
- Infrastructure (Doctrine mapping, repository, HTTP controllers, fixture, message handler)
- Unit and integration tests
- Doctrine custom type in `config/packages/doctrine.yaml`
- Repository alias in `config/services.yaml`
- RabbitMQ queue binding `events.<entity>` in `config/packages/messenger.yaml`

```
src/Product/
├── Domain/
│   ├── Entity/Product.php
│   ├── ValueObject/ProductId.php, ProductStatus.php
│   ├── Repository/ProductRepositoryInterface.php
│   ├── Event/ProductCreated.php, …
│   └── Exception/ProductNotFoundException.php, …
├── Application/
│   ├── Command/CreateProduct/, UpdateProduct/, …
│   └── Query/GetProduct/, GetProducts/
└── Infrastructure/
    ├── Persistence/Doctrine/
    │   ├── Mapping/Product.orm.xml
    │   ├── Repository/DoctrineProductRepository.php
    │   └── Type/ProductIdType.php
    ├── Http/Controller/…
    ├── Fixture/ProductFixture.php
    └── Messaging/ProductCreatedMessageHandler.php

tests/
├── Unit/Product/
└── Integration/Product/
```

#### 3. Post-generation steps

```bash
# 1. Add business fields to the entity, repository interface, and XML mapping
# 2. Create <Context>ExceptionMapper if needed (see docs/ddd-conventions.md)
# 3. Generate and run the migration
make db-diff
make db-migrate
make ci
```

Most configuration entries are registered automatically by `make crud`. If a step was skipped, the command prints the YAML blocks to add manually.

#### 4. Remove scaffolding

```bash
make remove-crud context=Product entity=Product       # remove one CRUD entity
make remove-bc name=Product                         # remove the entire bounded context
make remove-crud context=Product entity=Product force=1   # skip confirmation prompt
```

`User`, `Document`, and `Shared` are protected and cannot be removed by these commands.

| Make target | Console command | Description |
|---|---|---|
| `make bc name=…` | `make:bounded-context` | Create BC skeleton + routes + Doctrine mapping |
| `make crud context=… entity=…` | `make:bc-crud` | Generate CRUD entity inside a BC |
| `make remove-crud context=… entity=…` | `remove:bc-crud` | Remove a CRUD entity and its config |
| `make remove-bc name=…` | `remove:bounded-context` | Remove a BC and all its entities |

### API documentation (Swagger UI)

With the stack running, open:

| Resource | URL |
|---|---|
| Swagger UI | http://localhost:8080/api/doc/ |

Configuration lives in `config/packages/nelmio_api_doc.yaml` (title, OpenAPI version, `path_patterns` for documented routes) and `config/routes/nelmio_api_doc.yaml` (UI prefix).

Export the OpenAPI document from the CLI (JSON or YAML):

```bash
php bin/console nelmio:apidoc:dump --format=json
php bin/console nelmio:apidoc:dump --format=yaml
```

To document new HTTP endpoints, add `OpenApi\Attributes` on the controller with the **same path as `#[Route]`** (without the `/api/v1` prefix). See `src/User/Infrastructure/Http/Controller/` and `src/Shared/Infrastructure/Http/Controller/HealthCheckController.php` for examples.

### Insomnia collection (without OpenAPI coupling)

If you want a fully editable HTTP client collection (custom env vars, token placeholders, free-form requests), import:

- `docs/insomnia-collection.yaml`

This file is an Insomnia native export (not OpenAPI-based) and covers **Auth**, **Users**, **Documents**, **Buckets**, and **Infrastructure**:

- base env vars (`base_url`, `access_token`, `refresh_token`, `user_id`, `document_id`, `bucket_name`, `upload_id`, `part_number`)
- auth requests (`login`, `refresh`, `logout`)
- users CRUD requests
- documents requests (single-part upload via `multipart/form-data`, list, presigned URL, delete)
- multipart upload flow (initiate → upload part → complete / abort), chainable via `upload_id` and `part_number`
- bucket requests (create, list, exists, delete)
- infrastructure requests (`/health`, `/health/live`, `/metrics`)

Document and bucket folders inherit `Authorization: Bearer {{ access_token }}` from the folder-level Bearer auth. Run **Login** first, then paste `data.access_token` into the `access_token` environment variable.

## REST API

Base path: `/api/v1`.

### Health check (CI/CD)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/health` | None | Readiness probe with dependency checks |
| `GET` | `/health/live` | None | Liveness probe (process answers HTTP requests) |

`/health` returns `200` when all checks are healthy and `503` when at least one check fails:

```json
{
  "data": {
    "status": "ok",
    "checks": {
      "api": "ok",
      "database": "ok",
      "object_storage": "ok"
    },
    "checks_details": {
      "api": {
        "status": "ok",
        "duration_ms": 1
      },
      "database": {
        "status": "ok",
        "duration_ms": 4
      },
      "object_storage": {
        "status": "ok",
        "duration_ms": 6
      }
    }
  }
}
```

`/health/live` always returns:

```json
{
  "data": {
    "status": "ok"
  }
}
```

Example for deploy smoke tests or GitHub Actions:

```bash
curl -sf http://localhost:8080/health
curl -sf http://localhost:8080/health/live
curl -sf http://localhost:8080/metrics | head -20
```

`-f` makes curl exit non-zero on HTTP 503, which fails the job if the stack is not ready.

See [`docs/monitoring.md`](docs/monitoring.md) for readiness scope, `/metrics` scraping, and why SMTP checks are intentionally excluded.

### Endpoints

#### Authentication

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/auth/login` | None | Authenticate with email/password; returns access + refresh tokens |
| `POST` | `/auth/refresh` | None | Rotate refresh token; returns a new token pair |
| `POST` | `/auth/logout` | Bearer | Revoke a refresh token |

#### Users

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/users` | Admin | Create a user |
| `GET` | `/users` | Admin | List users (filterable, sortable, paginated) |
| `GET` | `/users/{id}` | Admin | Fetch a user by UUID |
| `PATCH` | `/users/{id}` | Admin | Partially update a user |
| `PUT` | `/users/{id}` | Admin | Fully replace a user |
| `DELETE` | `/users/{id}` | Admin | Soft-delete a user |

#### Documents

All document endpoints require a valid JWT. Upload uses `multipart/form-data` (`file`, `bucket`, optional `name`).

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/documents` | Upload a file (single-part, max 100 MB) |
| `GET` | `/documents` | List documents (filterable, sortable, paginated) |
| `GET` | `/documents/{id}/presigned-url` | Get a time-limited download URL |
| `DELETE` | `/documents/{id}` | Soft-delete a document (optional physical purge in object storage) |

#### Multipart upload

For files larger than 100 MB, use the multipart flow:

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/documents/multipart` | Initiate a multipart upload |
| `PUT` | `/documents/multipart/{uploadId}/parts/{partNumber}` | Upload a single part |
| `POST` | `/documents/multipart/{uploadId}/complete` | Complete the upload and persist metadata |
| `DELETE` | `/documents/multipart/{uploadId}` | Abort an in-progress upload |

#### Buckets

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/buckets` | Admin | Create a bucket in object storage |
| `GET` | `/buckets` | User | List buckets |
| `GET` | `/buckets/{name}/exists` | User | Check whether a bucket exists |
| `DELETE` | `/buckets/{name}` | Admin | Delete a bucket |

### Response format

All responses follow a consistent envelope:

```json
// single resource
{ "data": { "id": "...", "first_name": "...", "last_name": "...", "email": "..." } }

// collection
{
    "data": [...],
    "meta": {
        "page": 1,
        "limit": 10,
        "total_items": 245,
        "total_pages": 25,
        "has_next": true,
        "has_previous": false
    },
    "links": {
        "self": "/v1/users?page=1&limit=10",
        "next": "/v1/users?page=2&limit=10",
        "previous": null
    }
}

// error
{ "error": { "code": "user.not_found", "message": "User with id \"...\" was not found." } }
```

### Filtering, sorting and pagination

The collection endpoint supports the following query parameters:

| Parameter | Type | Example | Description |
|---|---|---|---|
| `email` | equal | `?email=john@example.com` | Exact match |
| `status` | equal | `?status=active` | Exact match |
| `age[min]` | range | `?age[min]=18` | Greater than or equal |
| `age[max]` | range | `?age[max]=30` | Less than or equal |
| `roles[]` | in | `?roles[]=admin&roles[]=user` | Included in list |
| `roles` | in | `?roles=admin,user` | Comma-separated list |
| `sort` | order | `?sort=email` | Ascending sort |
| `sort` | order | `?sort=-createdAt` | Descending sort (prefix `-`) |
| `page` | pagination | `?page=2` | Page number (default: 1) |
| `limit` | pagination | `?limit=10` | Items per page (default: 20, max: 100) |

Parameters can be combined freely:

```bash
# create
curl -s -X POST http://localhost:8080/api/v1/users \
  -H 'Content-Type: application/json' \
  -d '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","password":"secret1234"}'

# list with filters
curl -s "http://localhost:8080/api/v1/users?status=active&sort=-createdAt&page=1&limit=20"

# filter by email
curl -s "http://localhost:8080/api/v1/users?email=john.doe@example.com"

# get one
curl -s http://localhost:8080/api/v1/users/<id>

# partial update
curl -s -X PATCH http://localhost:8080/api/v1/users/<id> \
  -H 'Content-Type: application/json' \
  -d '{"email":"new.email@example.com"}'

# full replace
curl -s -X PUT http://localhost:8080/api/v1/users/<id> \
  -H 'Content-Type: application/json' \
  -d '{"firstName":"Jane","lastName":"Doe","email":"jane.doe@example.com","password":"newpassword123"}'

# delete
curl -s -X DELETE http://localhost:8080/api/v1/users/<id>
```

### HTTP status codes

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Created |
| `204` | No content (update, delete) |
| `400` | Bad request (missing or invalid field) |
| `401` | Unauthorized |
| `404` | Resource not found |
| `405` | Method not allowed |
| `409` | Conflict (duplicate resource) |
| `422` | Unprocessable entity (domain rule violation) |
| `500` | Internal server error |

## Adding a new Bounded Context

Start with the makers, then follow the full checklist in [`docs/ddd-conventions.md`](docs/ddd-conventions.md) (Deptrac rules, exception mappers, migrations, fixtures, tests).

```bash
make bc name=Product
make crud context=Product entity=Product
```

`make bc` scaffolds the folder structure and registers routes + Doctrine mapping. `make crud` generates the entity, CRUD use cases, HTTP controllers, tests, and entity-specific configuration (Doctrine type, repository alias, RabbitMQ binding). Remaining steps: business fields, `ProductExceptionMapper`, `make db-diff`, `make db-migrate`, `make ci`.

## Services

| Service | URL | Credentials |
|---|---|---|
| API | http://localhost:8080 | — |
| Swagger UI (OpenAPI) | http://localhost:8080/api/doc/ | — |
| Metrics endpoint | http://localhost:8080/metrics | — |
| RabbitMQ UI | http://localhost:15672 | app / see .env.local |
| Prometheus | http://localhost:9090 | — |
| Grafana | http://localhost:3000 | admin / see .env.local |
| Mailpit UI | http://localhost:8025 | — |
| RustFS Console | http://localhost:9001/rustfs/console/ (`S3_CONSOLE_PORT`) | `S3_ACCESS_KEY` / `S3_SECRET_KEY` (see `.env.local`) |
| PostgreSQL | localhost:5432 | app / see .env.local |

## Event flow

```
CommandHandler
  → repository->save(aggregate)
  → eventBus->publish(...aggregate->pullDomainEvents())
      → transactional outbox table (same DB transaction)
          → Scheduler tick (every 10s) → RelayOutboxMessagesHandler → OutboxRelay::relay()
            (manual fallback: `make outbox-relay` / `app:outbox:relay`)
              → RabbitMQ exchange "events" (topic)
                  → queue "events.<context>" (binding: <context>.#)
                      → MessageHandler (incl. async EventHandler/ side-effects: emails, etc.)

On failure after 3 retries:
  → failure_transport "async.dead_letter"
      → exchange "dead_letter"
          → queue "dead_letter"
              → DeadLetterMessageHandler

Periodic maintenance (same Scheduler worker):
  → daily 02:00 UTC → CleanupExpiredRefreshTokens → RefreshTokenRepositoryInterface::deleteExpired()
  → daily 03:00 UTC → CleanupStaleOutboxMessages   → OutboxMessagesCleaner::purge(OUTBOX_RETENTION_DAYS)
```

## Testing

Tests are organized in three suites matching the architecture layers:

```
tests/
├── Unit/           # Domain + Application — no I/O, fast
├── Integration/    # Infrastructure — hits the real database (and RustFS for Document)
└── Http/           # Full HTTP stack — routing, JWT, serialization, error handling
```

```bash
make test             # run all test suites
make test-unit        # unit tests only
make test-integration # integration tests only (migrates test DB first)
make test-http        # HTTP integration tests only (migrates test DB + reloads fixtures)
make test-coverage    # generate HTML coverage report in var/coverage/
make ci               # run all quality gates (cs-check, phpstan, deptrac, all test suites)
```

`make test-http` exercises controllers end-to-end via `KernelBrowser`. Tests extend `HttpTestCase`, which resets the database, reloads fixtures, and provides `createAuthenticatedClient('admin'|'user')` using credentials from `FixtureData`.

### Continuous Integration

GitHub Actions runs on every **push** and **pull request** to `main` and `master` (workflow: [`.github/workflows/ci.yml`](.github/workflows/ci.yml)).

The pipeline:

1. Starts Docker services: **PostgreSQL**, **RabbitMQ**, **RustFS**, **PHP**
2. Runs `composer install`
3. Generates a JWT keypair in `config/jwt/` (not committed — gitignored)
4. Warms the Symfony dev container cache (required by PHPStan)
5. Runs `make ci` — `cs-check`, `phpstan`, `deptrac`, then all PHPUnit suites (`Unit`, `Integration`, `Http`)

Tests run with `APP_ENV=test` (Symfony loads `.env.test` automatically). CI uses the default placeholder passwords from `.env` / `.env.test` — no real secrets.

Run `make ci` locally before opening a PR — it executes the same quality gates as GitHub Actions and stops at the first failure.

**Reproduce the CI pipeline locally:**

```bash
cp .env .env.local                              # skip if .env.local already exists
docker compose -f docker/compose.yaml --env-file .env.local up -d --wait postgres rabbitmq rustfs php
make install

# Generate JWT keys once (required for auth / HTTP tests):
docker compose -f docker/compose.yaml --env-file .env.local exec php sh -c '
  mkdir -p config/jwt
  openssl genrsa -aes256 -passout pass:change_me -out config/jwt/private.pem 4096
  openssl rsa -pubout -passin pass:change_me -in config/jwt/private.pem -out config/jwt/public.pem
'

docker compose -f docker/compose.yaml --env-file .env.local exec php bin/console cache:warmup --env=dev
make ci
```

If any step fails, PHPUnit output is printed directly in the terminal (same as in GitHub Actions job logs).

## Code quality

Run static analysis, architecture checks, and code style:

```bash
make cs-check  # PHP CS Fixer dry-run (fails if formatting drifts)
make cs-fix    # apply PHP CS Fixer fixes
make phpstan   # run PHPStan with phpstan.neon (level 9)
make deptrac   # run Deptrac with deptrac.yaml
make ci        # cs-check + phpstan + deptrac + all test suites (recommended before every PR)
```

Pre-commit hooks (see [Getting started](#pre-commit-hooks-recommended)) run the same PHP CS Fixer dry-run check automatically on staged `.php` files before each commit.

If Docker is not available in your environment, run them directly:

```bash
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff
vendor/bin/phpstan analyse
vendor/bin/deptrac analyse --config-file=deptrac.yaml
```

### License

Apache License 2.0 — see [LICENSE](LICENSE).
