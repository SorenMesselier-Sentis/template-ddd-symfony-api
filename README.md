# Symfony DDD API Template

A production-ready REST API template built with Symfony 8 and Domain-Driven Design principles.

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Symfony 8.0 |
| Web server | [FrankenPHP](https://frankenphp.dev/) (Caddy-based) — single container serves HTTP directly, no separate nginx/php-fpm |
| ORM | Doctrine ORM |
| Database | PostgreSQL 16 |
| Message Bus | Symfony Messenger |
| Queue | RabbitMQ |
| Cache / shared state | Redis 7 — cache pool, rate limiter storage, scheduler state, Prometheus metrics |
| Scheduler | Symfony Scheduler (cron + periodic) |
| Mailer | Symfony Mailer + Twig templates, Mailpit for dev |
| Logging | Monolog |
| Monitoring (optional) | Prometheus + Grafana (preconfigured scrape targets + starter dashboard) — `make up-monitoring` |
| Object storage | S3-compatible: [Garage](https://garagehq.deuxfleurs.fr/) `dxflrs/garage:v2.3.0` in Docker (dev/CI), [Cloudflare R2](https://developers.cloudflare.com/r2/) (staging/prod), AWS SDK for PHP (`aws/aws-sdk-php`) |
| API documentation | NelmioApiDocBundle, OpenAPI 3, Swagger UI (Twig + Asset) |

> **Object storage split:** local development, integration and HTTP tests run against a self-hosted Garage container; staging/prod point the same `S3_*` env vars at a Cloudflare R2 bucket instead — both are plain S3-compatible endpoints behind the same adapter, no code branching required.

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
├── Document/                       # Object storage (Garage/R2, S3-compatible) — metadata in DB, files in buckets
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
├── Project/                         # Second reference BC — richer relations than User/Document (see "Key design decisions")
│   ├── Domain/
│   │   ├── Entity/                 # Project, Task
│   │   ├── ValueObject/            # ProjectId, OwnerId, ProjectName, TaskId, TaskTitle, AssigneeId, AttachmentId, …
│   │   ├── Repository/              # ProjectRepositoryInterface, TaskRepositoryInterface
│   │   └── Exception/
│   ├── Application/
│   │   ├── Command/                # CreateProject, CreateTask, DeleteProject (blocks on active tasks), …
│   │   └── Query/                  # GetProjects, GetTasks (scoped to a project), …
│   └── Infrastructure/
│       ├── Fixture/                # ProjectFixture, TaskFixture (DependentFixtureInterface — see below)
│       ├── Persistence/Doctrine/   # Task.orm.xml has the template's only <many-to-one>
│       ├── Http/ProjectExceptionMapper.php
│       └── Http/Controller/        # /projects, /projects/{projectId}/tasks, /tasks/{id}
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

**Object storage (Document BC)** — file bytes live in S3-compatible object storage (Garage in Docker for dev/CI, Cloudflare R2 for staging/prod); the `Document` aggregate stores metadata only (name, size, MIME type, bucket, object path, owner ID, status). Bounded contexts interact through domain ports (`DocumentStorageInterface`, `BucketManagerInterface`); infrastructure adapters use the AWS SDK for PHP against the configured `S3_ENDPOINT`. `OwnerId` is a UUID reference with no foreign key to the User BC, preserving bounded-context isolation. Presigned URLs delegate direct download to object storage without streaming through PHP.

**Cross-BC references vs. real relations (Project BC)** — `User` and `Document` only ever reference each other by stable UUID (`OwnerId`, no Doctrine relation) because they're in different bounded contexts. `Project` is the template's second reference BC, added specifically to show the *other* half of that rule: `Task.project` is a genuine Doctrine `<many-to-one>` to `Project`, because both entities live in the **same** bounded context — real relations are fine (even expected) within a BC, and only become a violation once they'd cross a BC boundary. `Task` also carries `assigneeId` (→ User) and `attachmentId` (→ Document), both plain UUID fields with zero cross-BC validation, exactly like `Document.OwnerId` — Project's `Infrastructure` never imports anything from `User` or `Document`. Two consequences worth knowing if you copy this pattern:
- The relation is `fetch="EAGER"`, not the Doctrine default `LAZY`. `Project::$id` is `readonly` (as recommended everywhere else in this template), and Doctrine's lazy ghost-object hydration needs to partially set an identifier before the rest of a proxy initializes — which conflicts with `readonly` and throws `LogicException: Attempting to change readonly property ...::$id`. Loading `Project` eagerly (a JOIN) sidesteps that entirely, and is the right call anyway since almost every `Task` handler needs the parent `Project` for the ownership check (`Task::project()->ownerId()`) — LAZY would just mean a second query on top of the JOIN you'd otherwise write by hand.
- `TaskFixture` depends on `ProjectFixture` via Doctrine's `DependentFixtureInterface`/`getDependencies()` — the only place in this template fixtures need real load ordering, since every other cross-BC fixture link is a stable UUID from `FixtureData` with no persistence-order requirement.

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

### Idempotency-Key

Clients can safely retry a `POST` (e.g. after a timeout with an uncertain outcome) by sending an `Idempotency-Key` header — `IdempotencyKeyListener` (`Shared/Infrastructure/Http/Listener/`) caches the first successful (`2xx`) response in `cache.app` (Redis — see "Cache and scale-out (Redis)", so replays work no matter which `php` replica handles them) for 24h and replays it verbatim on a repeat request with the same key, instead of re-running the command:

- **Same key, same body** → the cached response is replayed as-is (status, `Content-Type`, `Location`), with an `Idempotency-Replayed: true` header added; the command never runs again, so no duplicate resource is created.
- **Same key, different body** → rejected with `409 idempotency_key.conflict` — reusing a key for a different request is always a client bug, never silently served from the wrong cache entry.
- **No header** → unchanged default behavior; this is entirely opt-in per request.
- Failed (`4xx`/`5xx`) responses are never cached, so a failed attempt can simply be retried with the same key.
- The cache key is scoped per authenticated user (or client IP if anonymous) and route, so one client can't collide with or read another's cached response.

Applies to every `POST` under `/api/v1`, generically — no per-command opt-in required (unlike the audit trail's `AuditableMessage`), since replay-safety is a transport-level concern, not a business one. There is no distributed lock: two requests racing with a brand-new key can both miss the cache and both execute — this covers "retry after a timeout," not true concurrent double-submission.

### GDPR data export

`GET /users/me/export` (any authenticated user, their own data only) returns every piece of personal data the application holds about the caller — the right of access / data portability, in one downloadable JSON:

```bash
curl -s http://localhost:8080/api/v1/users/me/export -H "Authorization: Bearer $TOKEN" -o my-data.json
```

```json
{
    "data": {
        "exported_at": "2026-08-06T12:00:00+00:00",
        "profile": { "id": "...", "email": "...", "first_name": "...", "roles": ["ROLE_USER"], "created_at": "..." },
        "documents": [{ "id": "...", "original_name": "invoice.pdf", "bucket": "documents", "size": 1024, "created_at": "..." }]
    }
}
```

Bounded contexts don't know about each other, so the aggregation follows the same "inject via tagged services, not imports" convention already used for exception mapping: each context that holds personal data implements `Shared\Domain\Privacy\PersonalDataExporterInterface` (`key(): string`, `export(string $subjectId): array`) — `User\Application\Privacy\UserPersonalDataExporter` and `Document\Application\Privacy\DocumentPersonalDataExporter` today — auto-tagged `app.gdpr_data_exporter` via `_instanceof` in `config/services.yaml`. `ExportUserDataQueryHandler` collects every tagged exporter generically and nests each one's output under its `key()`; adding personal data to a new bounded context means implementing the interface there, nothing else to wire.

- Self-service only — exports the caller's own data (`RoleRequirement::authenticated()`, subject id from `UserContextInterface`). There's no admin-triggered "export this other user's data" endpoint; add one following the same query if you need to fulfill requests on a user's behalf.
- Only *active* records are included, matching what the user can already see through the regular endpoints (`Document\Application\Privacy\DocumentPersonalDataExporter` calls the same `findByOwnerId()` the documents list uses) — soft-deleted rows kept for audit purposes are not exported.
- `Content-Disposition: attachment` is set so the response downloads as a file rather than rendering inline.

### Feature flags

Toggle a feature on/off at runtime, without a redeploy — kill a broken feature in prod, or roll one out gradually — backed by a `feature_flags` table (centralized migration) so changes persist and are visible to every `php` replica immediately, unlike an env var.

```bash
# list flags (admin)
curl -s http://localhost:8080/api/v1/feature-flags -H "Authorization: Bearer $ADMIN_TOKEN"

# disable one (admin) — full replace (PUT), recorded in the audit trail
curl -s -X PUT http://localhost:8080/api/v1/feature-flags/cursor_pagination \
  -H "Authorization: Bearer $ADMIN_TOKEN" -H 'Content-Type: application/json' \
  -d '{"enabled": false, "description": "Kill switch — rolling back a regression."}'
```

Two ways to consult a flag from code, covering both use cases:

- **Ad-hoc, inside a handler/controller**: inject `Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface` and call `isEnabled(string $key): bool` (an unregistered key is treated as disabled — a flag only takes effect once explicitly created via `PUT`).
- **Declarative, gating a whole command/query**: implement `Shared\Domain\FeatureFlag\FeatureGatedMessage` (`requiredFeatureFlag(): string`) on the message — the same "declared on the message" convention as `AuthorizedMessage`/`AuditableMessage`. `FeatureFlagMessageMiddleware` (registered on both `command.bus` and `query.bus`, right after authorization) rejects the message with `403 feature_flag.disabled` while the flag is off. `GetUsersCursorQuery`/`GetDocumentsCursorQuery` (see "Cursor pagination") are gated by `cursor_pagination` this way as a working example — seeded `enabled = true` by the migration, so the default behavior is unchanged; disable it to fall back to `page`/`limit` only.

Admin-only (`PUT`/`GET /feature-flags`, `RoleRequirement::admin()`) — one deliberate architectural wart worth calling out: these commands/queries live under `User/Application/` purely because `Shared/` has no `Application` layer of its own (see "Architecture" above — cross-cutting code is `Domain`/`Infrastructure` only), and the "auth declared on message" convention requires every command to belong to some bounded context's `Application` layer. Feature flags aren't really a `User` concern; if this grows into something bigger, it's a good candidate to become its own bounded context.

### CORS

`CorsListener` (`Shared/Infrastructure/Http/Listener/`) answers preflight `OPTIONS` requests and adds CORS headers to every response, driven by a single env var:

```bash
# Comma-separated list of allowed origins, or * for all (no credentials/cookies are used, so * is safe here)
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://app.example.com
```

Left empty (the `.env` default), no cross-origin browser request is allowed — same-origin and non-browser clients (curl, server-to-server) are unaffected, since CORS is a browser-enforced mechanism. `Location` (returned on `201 Created`) and `X-Request-Id` are exposed via `Access-Control-Expose-Headers` so frontend JS can read them; a `Vary: Origin` header is added whenever a specific origin (not `*`) is reflected, so intermediary caches don't serve one origin's CORS headers to another.

### Real-time updates (Mercure)

Push data to the browser instead of polling — a [Mercure](https://mercure.rocks/) hub, embedded directly in the `php` container's own FrankenPHP/Caddy process (`docker/frankenphp/Caddyfile`'s `mercure` directive). FrankenPHP ships this module built in, so there's no extra container: `/.well-known/mercure` requests are intercepted by Caddy itself and never reach the PHP/Symfony kernel.

Publishing, from any bounded context, goes through one port:

```php
public function __construct(private RealtimePublisherInterface $publisher) {}

$this->publisher->publish('/users/'.$userId.'/notifications', ['subject' => 'Hi', 'body' => '...']);
```

`Shared\Domain\RealTime\RealtimePublisherInterface`, backed by `MercureRealtimePublisher` — same ports-and-adapters shape as `EmailSenderInterface`. Wired as a working example on the `IN_APP` notification channel (`InAppChannelNotificationHandler`): every in-app notification is now pushed live, not just logged.

Topics are **private** by default, so a frontend needs a subscriber token before it can listen:

```bash
# 1. Mint a subscriber authorization cookie scoped to the caller's own topics
curl -i http://localhost:8080/api/v1/users/me/realtime-token -H "Authorization: Bearer $TOKEN"
# -> Set-Cookie: mercureAuthorization=...; Path=/.well-known/mercure; HttpOnly

# 2. Subscribe (send that cookie along)
curl -N "http://localhost:8080/.well-known/mercure?topic=/users/<id>/notifications" \
  -H "Cookie: mercureAuthorization=..."
```

From a browser, the cookie set in step 1 is sent automatically by `EventSource` as long as the page and the hub are same-origin — the common case, since the hub lives on the same host/port as the API. If your frontend runs on a **different origin** in dev (e.g. a Vite/webpack dev server on another port), two things change: `EventSource` needs `{ withCredentials: true }`, and the Caddy `mercure` block needs `cors_origins` set to that exact origin (not `*` — cookie-based auth requires a real origin with `Access-Control-Allow-Credentials`, unlike the API's own CORS above).

Each hub instance keeps updates in memory only (the default "local" transport) — history/subscriptions don't survive a container restart, which is fine for dev/demo use. Switch to the `bolt` transport (`transport bolt <path>` in the Caddyfile, plus a volume to persist it) if you need updates to survive a restart.

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

`make init` will build the Docker images, start the core containers (**php (FrankenPHP), postgres, rabbitmq, redis, garage, mailpit** — the minimum needed to run and test the app), install Composer dependencies, create the database, run all migrations, and bootstrap Garage. Prometheus, Grafana and postgres_exporter are optional and not started by `make init`/`make up` — see "Monitoring stack (optional)" below.

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

# Mercure (real-time push — see "Real-time updates (Mercure)"). Hub is
# embedded in the "php" container; MERCURE_URL is the internal publish URL,
# MERCURE_PUBLIC_URL is what browsers subscribe to.
MERCURE_JWT_SECRET=your_secret
MERCURE_URL="http://php/.well-known/mercure"
MERCURE_PUBLIC_URL="http://localhost:${HTTP_PORT}/.well-known/mercure"

# S3-compatible object storage — Garage in Docker for dev/CI, Cloudflare R2 for
# staging/prod (Document BC). Application settings consumed by PHP adapters:
S3_ACCESS_KEY=garageadmin
S3_SECRET_KEY=your_password
S3_ENDPOINT=http://garage:3900
S3_REGION=garage
S3_FORCE_PATH_STYLE=true
S3_USE_SSL=false
S3_API_PORT=3900
S3_PRESIGNED_URL_TTL=3600
```

| Variable | Scope | Description |
|---|---|---|
| `S3_ENDPOINT` | PHP app | S3 API URL used by adapters and health check (Docker: `http://garage:3900`; R2: `https://<account_id>.r2.cloudflarestorage.com`) |
| `S3_ACCESS_KEY` | PHP app + Garage | Access key for S3 API authentication |
| `S3_SECRET_KEY` | PHP app + Garage | Secret key for S3 API authentication |
| `S3_REGION` | PHP app | Region passed to the S3 client (`garage` locally; `auto` for Cloudflare R2) |
| `S3_FORCE_PATH_STYLE` | PHP app | Use path-style bucket addressing (`true` for both Garage and R2) |
| `S3_USE_SSL` | PHP app | Enable TLS for the S3 client (`true` / `false`) |
| `S3_API_PORT` | Docker host | Host port mapped to Garage's S3 API (container port `3900`) |
| `S3_PRESIGNED_URL_TTL` | PHP app | Default presigned download URL TTL in seconds (60–604800) |

See `.env` for the full list of available variables. Garage's own config
(`docker/garage/garage.toml`) is not env-driven — it ships a fixed dev-only
RPC secret, same trust model as other committed dev defaults like
`POSTGRES_PASSWORD=change_me`. A fresh Garage node has no cluster layout,
access key, or buckets until bootstrapped: `make garage-bootstrap` (wired
into `make init`) does this idempotently — see the target itself in the
`Makefile` for the exact sequence.

### Production: Cloudflare R2

Point the same `S3_*` variables at a Cloudflare R2 bucket instead of Garage — no code or config-schema change needed, since both are S3-compatible endpoints behind the same adapter:

```bash
S3_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
S3_ACCESS_KEY=<r2-access-key-id>
S3_SECRET_KEY=<r2-secret-access-key>
S3_REGION=auto
S3_FORCE_PATH_STYLE=true
S3_USE_SSL=true
```

Create the bucket and an API token (with the access key/secret pair) from the Cloudflare dashboard or Terraform — this template's `garage-bootstrap` tooling only applies to the local Garage container. R2's S3 API supports `CreateBucket`/`DeleteBucket`/`ListBuckets`, so `BucketManagerInterface` works against R2 unchanged if the app ever needs to provision buckets dynamically.

## Development

```bash
make up             # start the core stack (php/FrankenPHP, postgres, rabbitmq, redis, garage, mailpit)
make up-monitoring  # core stack + Prometheus, Grafana, postgres_exporter
make down           # stop and remove all containers (including monitoring, if running)
make bash           # open a shell in the PHP container
make logs           # tail all container logs
make logs-php       # tail PHP logs only
make restart        # stop then start the core stack (pick up image / config changes)
```

### Monitoring stack (optional)

Prometheus, Grafana and postgres_exporter are tagged with the Docker Compose `monitoring` profile and excluded from `make up`/`make init` by default — the app's own `GET /metrics` endpoint (see [`docs/monitoring.md`](docs/monitoring.md)) works regardless, these three containers only add scraping + dashboards on top. Bring them up with `make up-monitoring`; `make down`/`make down-v` always tear them down too, whether or not they were started.

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

#### Projects and tasks

Second reference bounded context — see "Key design decisions" above for why `Task`↔`Project` is a real Doctrine relation while `assigneeId`/`attachmentId` stay plain cross-BC UUIDs. All endpoints require a valid JWT; a project (and its tasks) can only be read/written by its owner or `ROLE_ADMIN`.

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/projects` | Create a project (name unique per owner) |
| `GET` | `/projects` | List the caller's projects (filterable by `name`, `status`, paginated) |
| `GET` | `/projects/{id}` | Fetch a project |
| `PATCH` | `/projects/{id}` | Rename and/or archive/reactivate (`status: active\|archived`) |
| `PUT` | `/projects/{id}` | Rename (full replace); status untouched |
| `DELETE` | `/projects/{id}` | Soft-delete — `409 project.has_active_tasks` while any task is `todo`/`in_progress` |
| `POST` | `/projects/{projectId}/tasks` | Create a task — `409 project.not_active` if the project is archived/deleted |
| `GET` | `/projects/{projectId}/tasks` | List a project's tasks (filterable by `status`, `assigneeId`, paginated) |
| `GET` | `/tasks/{id}` | Fetch a task |
| `PATCH` | `/tasks/{id}` | Update title/status/assignee (reassign only — no unassign, see `Task::update()`) |
| `PUT` | `/tasks/{id}` | Rename and/or reassign (full replace); status and attachment untouched |
| `DELETE` | `/tasks/{id}` | Soft-delete a task |

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
| `pagination` | pagination | `?pagination=cursor` | Switch to cursor (keyset) pagination — see "Cursor pagination" below |
| `cursor` | pagination | `?cursor=eyJ...` | Opaque position token from `meta.next_cursor` (cursor mode only) |

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

### Cursor pagination

`page`/`limit` pagination re-scans and discards `offset` rows on every request — expensive on large tables, and prone to skipping or repeating rows when the collection changes between pages (a row inserted or deleted ahead of the current offset shifts every subsequent page). Collection endpoints (`GET /users`, `GET /documents`) also support keyset ("cursor") pagination as an opt-in alternative: pass `pagination=cursor` instead of `page`.

```bash
# first page
curl -s "http://localhost:8080/api/v1/users?pagination=cursor&limit=20"

# next page — cursor comes from the previous response's meta.next_cursor
curl -s "http://localhost:8080/api/v1/users?pagination=cursor&limit=20&cursor=eyJjcmVhdGVkX2F0IjoiMjAyNi0wMS0xNSAxMDozMDowMCIsImlkIjoiLi4uIn0="
```

```json
{
    "data": [...],
    "meta": {
        "limit": 20,
        "has_more": true,
        "next_cursor": "eyJjcmVhdGVkX2F0IjoiMjAyNi0wMS0xNSAxMDozMDowMCIsImlkIjoiLi4uIn0="
    },
    "links": {
        "self": "/v1/users?pagination=cursor&limit=20",
        "next": "/v1/users?pagination=cursor&limit=20&cursor=..."
    }
}
```

- The cursor is an opaque, base64-encoded `(createdAt, id)` pair — the position of the last row of the previous page. `id` is the tie-breaker because `createdAt` alone (second-level precision) is not unique. A malformed or tampered cursor is rejected with `400 invalid_filter`.
- Ordering is fixed at `createdAt DESC`; `sort` is ignored in cursor mode. Regular filters (`email`, `status`, `bucketName`, …) still apply and are preserved across `next` links.
- No `total_items`/`total_pages`/`page` — a `COUNT(*)` over the whole filtered set would defeat the purpose (it's the expensive part of offset pagination on a large table). No `previous` link either — walking backward through a keyset series needs a second, reversed cursor scheme, which this template doesn't implement; if you need bidirectional cursor pagination, extend `Cursor`/`CursorPagination` (`Shared/Domain/Filter/`) accordingly.
- Backed by a composite `(created_at, id)` index (`(owner_id, created_at, id)` for documents, since every query is owner-scoped) — see migration `Version20260101000009`. Without it, `WHERE (created_at, id) < (?, ?) ORDER BY created_at DESC, id DESC LIMIT n` falls back to a full sort.
- Implemented for the two reference contexts (`User`, `Document`) as a pattern to copy — `make crud`-scaffolded contexts get `page`/`limit` only. See `DoctrineUserRepository::findByFiltersCursor()` / `DoctrineDocumentRepository::findByOwnerIdAndFiltersCursor()` for the keyset query, and `GetUsersController`/`GetDocumentsController` for the `pagination=cursor` branch.
- Gated behind the `cursor_pagination` feature flag (enabled by default) — see "Feature flags" below; `PUT /feature-flags/cursor_pagination` with `enabled: false` turns it off instantly if it needs to be rolled back, falling back to `page`/`limit`.

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
| Prometheus *(`make up-monitoring`)* | http://localhost:9090 | — |
| Grafana *(`make up-monitoring`)* | http://localhost:3000 | admin / see .env.local |
| Mailpit UI | http://localhost:8025 | — |
| Garage S3 API | http://localhost:3900 (`S3_API_PORT`) | `S3_ACCESS_KEY` / `S3_SECRET_KEY` (see `.env.local`) — no web console; use `docker compose exec garage /garage bucket list` or `make garage-bootstrap` |
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
├── Integration/    # Infrastructure — hits the real database (and Garage for Document)
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

1. Starts Docker services: **PostgreSQL**, **RabbitMQ**, **Garage**, **PHP**
2. Bootstraps Garage (`make garage-bootstrap` — layout, access key, buckets)
3. Runs `composer install`
4. Generates a JWT keypair in `config/jwt/` (not committed — gitignored)
5. Warms the Symfony dev container cache (required by PHPStan)
6. Runs `make ci` — `cs-check`, `phpstan`, `deptrac`, then all PHPUnit suites (`Unit`, `Integration`, `Http`)

Tests run with `APP_ENV=test` (Symfony loads `.env.test` automatically). CI uses the default placeholder passwords from `.env` / `.env.test` — no real secrets.

Run `make ci` locally before opening a PR — it executes the same quality gates as GitHub Actions and stops at the first failure.

**Reproduce the CI pipeline locally:**

```bash
cp .env .env.local                              # skip if .env.local already exists
docker compose -f docker/compose.yaml --env-file .env.local up -d --wait postgres rabbitmq garage php
make garage-bootstrap
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
