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
| Scheduler | Symfony Scheduler (cron + periodic) |
| Mailer | Symfony Mailer + Twig templates, Mailpit for dev |
| Logging | Monolog |
| Monitoring | Prometheus + Grafana (preconfigured scrape targets + starter dashboard) |
| Object storage | MinIO (S3-compatible), AWS SDK for PHP (`aws/aws-sdk-php`) |
| API documentation | NelmioApiDocBundle, OpenAPI 3, Swagger UI (Twig + Asset) |

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
├── Document/                       # Object storage (MinIO) — metadata in DB, files in buckets
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
│       ├── Storage/                # MinioDocumentStorageAdapter, MinioBucketAdapter
│       ├── Health/MinioHealthCheck.php
│       └── Http/Controller/        # /documents, /buckets
│
└── <BoundedContext>/               # e.g. Product, Order — scaffold with `make bc name=…`
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

**Scheduled recurring tasks** — `DefaultSchedule` (in `Shared/Infrastructure/Scheduler/`) is the single `#[AsSchedule('default')]` provider for the application. It is stateful (`stateful(cache)` + `processOnlyLastMissedRun(true)`), so missed ticks during a worker restart are recovered without flooding. The schedule currently registers three tasks:

| Cadence | Task | Handler |
|---|---|---|
| every 10 seconds | `RelayOutboxMessages` — publishes pending `outbox_messages` rows to RabbitMQ via the existing `OutboxRelay` | `Shared/Infrastructure/Scheduler/Handler/RelayOutboxMessagesHandler` |
| daily at 02:00 UTC | `CleanupExpiredRefreshTokens` — deletes expired refresh tokens via `RefreshTokenRepositoryInterface::deleteExpired()` | `User/Infrastructure/Scheduler/CleanupExpiredRefreshTokensHandler` |
| daily at 03:00 UTC | `CleanupStaleOutboxMessages` — purges published outbox rows older than `OUTBOX_RETENTION_DAYS` (default 30) via `OutboxMessagesCleaner` | `Shared/Infrastructure/Scheduler/Handler/CleanupStaleOutboxMessagesHandler` |

Handlers are registered on `command.bus` only (no auto-broadcast to other buses). Failures are logged via `LoggerInterface` and swallowed so a single bad tick never crashes the scheduler worker. The manual `make outbox-relay` and `app:outbox:relay` console command remain available for on-demand triggering.

**Object storage (Document BC)** — file bytes live in MinIO; the `Document` aggregate stores metadata only (name, size, MIME type, bucket, object path, owner ID, status). Bounded contexts interact through domain ports (`DocumentStorageInterface`, `BucketManagerInterface`); infrastructure adapters use the AWS SDK for PHP against MinIO. `OwnerId` is a UUID reference with no foreign key to the User BC, preserving bounded-context isolation. Presigned URLs delegate direct download to MinIO without streaming through PHP.

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

### Environment variables

Copy `.env` to `.env.local` and fill in your secrets. Never commit `.env.local`.

```bash
# Database
POSTGRES_PASSWORD=your_password

# RabbitMQ
RABBITMQ_PASSWORD=your_password

# Grafana
GRAFANA_ADMIN_PASSWORD=your_password

# Mailer (Mailpit by default in dev — override per environment)
MAILER_DSN=smtp://mailpit:1025
MAILER_FROM=noreply@example.com

# Outbox cleanup retention (days). Values <1 fall back to 30 with a warning log.
OUTBOX_RETENTION_DAYS=30

# MinIO (S3-compatible object storage — Document BC)
MINIO_ROOT_USER=minio
MINIO_ROOT_PASSWORD=your_password
MINIO_ENDPOINT=http://minio:9000
MINIO_ACCESS_KEY=${MINIO_ROOT_USER}
MINIO_SECRET_KEY=${MINIO_ROOT_PASSWORD}
MINIO_USE_SSL=false
MINIO_API_PORT=9000
MINIO_CONSOLE_PORT=9001
MINIO_PRESIGNED_URL_TTL=3600
```

See `.env` for the full list of available variables.

## Development

```bash
make up           # start containers
make down         # stop containers
make bash         # open a shell in the PHP container
make logs         # tail all container logs
make logs-php     # tail PHP logs only
make restart      # stop then start containers (pick up image / config changes)
```

### Cache

```bash
make clear        # Symfony cache:clear
```

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
| `AppFixtures` | `Shared/Infrastructure/Fixture/` | Entry point loaded by `make db-fixtures`; declares fixture load order via `DependentFixtureInterface` |

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

### Generating a new Bounded Context

Use the built-in maker command to scaffold the full DDD structure:

```bash
make bc name=Product
```

This generates the following structure:

```
src/Product/
├── Domain/
│   ├── Entity/Product.php
│   ├── ValueObject/ProductId.php
│   ├── Repository/ProductRepositoryInterface.php
│   ├── Event/ProductCreated.php
│   ├── Event/ProductUpdated.php
│   ├── Event/ProductDeleted.php
│   └── Exception/
│       ├── ProductNotFoundException.php
│       └── ProductAlreadyExistsException.php
├── Application/
│   ├── Command/
│   │   ├── CreateProduct/
│   │   ├── UpdateProduct/
│   │   └── DeleteProduct/
│   └── Query/
│       ├── GetProduct/
│       └── GetProducts/
└── Infrastructure/
    ├── Persistence/Doctrine/
    │   ├── Mapping/Product.orm.xml
    │   ├── Repository/DoctrineProductRepository.php
    │   └── Type/ProductIdType.php
    ├── Http/
    │   ├── Controller/
    │   └── Request/
    ├── Fixture/ProductFixture.php
    └── Messaging/ProductCreatedMessageHandler.php

tests/
├── Unit/Product/
└── Integration/Product/
```

After running the command, follow the printed next steps:

```bash
# 1. Register the Doctrine type in config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            product_id: App\Product\Infrastructure\Persistence\Doctrine\Type\ProductIdType

# 2. Register the Doctrine mapping in config/packages/doctrine.yaml
    orm:
        mappings:
            Product:
                type: xml
                dir: '%kernel.project_dir%/src/Product/Infrastructure/Persistence/Doctrine/Mapping'
                prefix: App\Product\Domain\Entity
                is_bundle: false

# 3. Register the repository in config/services.yaml
App\Product\Domain\Repository\ProductRepositoryInterface:
    alias: App\Product\Infrastructure\Persistence\Doctrine\Repository\DoctrineProductRepository

# 4. Add the RabbitMQ binding key in config/packages/messenger.yaml
queues:
    events.product:
        binding_keys: ['product.#']

# 5. Generate and run the migration
make db-diff
make db-migrate
```

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

This file is an Insomnia native export (not OpenAPI-based) and includes:

- base env vars (`base_url`, `access_token`, `refresh_token`, `user_id`)
- auth requests (`login`, `refresh`, `logout`)
- users CRUD requests
- infrastructure requests (`/health`, `/health/live`, `/metrics`)

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
      "database": "ok"
    },
    "checks_details": {
      "api": {
        "status": "ok",
        "duration_ms": 1
      },
      "database": {
        "status": "ok",
        "duration_ms": 4
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
| `DELETE` | `/documents/{id}` | Soft-delete a document (optional physical purge in MinIO) |

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
| `POST` | `/buckets` | Admin | Create a bucket in MinIO |
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

1. Create the directory structure under `src/<ContextName>/`
2. Add the Doctrine XML mapping under `src/<ContextName>/Infrastructure/Persistence/Doctrine/Mapping/`
3. Register the new mapping in `config/packages/doctrine.yaml`
4. Add the RabbitMQ binding key in `config/packages/messenger.yaml`
5. Register your repository implementation in `config/services.yaml`
6. Define allowed filters in your collection controller via `FiltersBuilder::fromRequest()`

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
| MinIO Console | http://localhost:9001 | `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD` (see `.env.local`) |
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
├── Integration/    # Infrastructure — hits the real database (and MinIO for Document)
└── Http/           # Full HTTP stack — routing, JWT, serialization, error handling
```

```bash
make test             # run all test suites
make test-unit        # unit tests only
make test-integration # integration tests only (migrates test DB first)
make test-http        # HTTP integration tests only (migrates test DB + reloads fixtures)
make test-coverage    # generate HTML coverage report in var/coverage/
make ci               # run all quality gates (phpstan, deptrac, all test suites)
```

`make test-http` exercises controllers end-to-end via `KernelBrowser`. Tests extend `HttpTestCase`, which resets the database, reloads fixtures, and provides `createAuthenticatedClient('admin'|'user')` using credentials from `FixtureData`.

### Continuous Integration

GitHub Actions runs on every **push** and **pull request** to `main` and `master` (workflow: [`.github/workflows/ci.yml`](.github/workflows/ci.yml)).

The pipeline:

1. Starts Docker services: **PostgreSQL**, **RabbitMQ**, **MinIO**, **PHP**
2. Runs `composer install`
3. Generates a JWT keypair in `config/jwt/` (not committed — gitignored)
4. Warms the Symfony dev container cache (required by PHPStan)
5. Runs `make ci` — `phpstan`, `deptrac`, then all PHPUnit suites (`Unit`, `Integration`, `Http`)

Tests run with `APP_ENV=test` (Symfony loads `.env.test` automatically). CI uses the default placeholder passwords from `.env` / `.env.test` — no real secrets.

**Reproduce the CI pipeline locally:**

```bash
cp .env .env.local                              # skip if .env.local already exists
docker compose -f docker/compose.yaml --env-file .env.local up -d --wait postgres rabbitmq minio php
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
make ci        # phpstan + deptrac + all test suites
```

If Docker is not available in your environment, run them directly:

```bash
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff
vendor/bin/phpstan analyse
vendor/bin/deptrac analyse --config-file=deptrac.yaml
```

### License

Proprietary
