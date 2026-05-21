# Symfony DDD API Template

A production-ready REST API template built with Symfony 8 and Domain-Driven Design principles.

## TODO

- Set up Prometheus
- Set up Grafana
- Add the soft delete possibility
- Set up Shared\Services for the email for example
- Set up Scheduler

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Symfony 8.0 |
| ORM | Doctrine ORM |
| Database | PostgreSQL 16 |
| Message Bus | Symfony Messenger |
| Queue | RabbitMQ |
| Logging | Monolog |
| Monitoring | Prometheus + Grafana |
| API documentation | NelmioApiDocBundle, OpenAPI 3, Swagger UI (Twig + Asset) |

## Architecture

This template follows Domain-Driven Design (DDD) principles with a clear separation of concerns across three layers per Bounded Context.

```
src/
├── Shared/                         # Cross-cutting concerns
│   ├── Domain/
│   │   ├── Bus/                    # Command, Query, Event bus interfaces
│   │   ├── Filter/                 # Filter, Filters, Order, Pagination value objects
│   │   ├── ValueObject/            # Uuid, Email
│   │   ├── Exception/              # Base domain exceptions
│   │   └── Logging/                # Logger interface
│   └── Infrastructure/
│       ├── Bus/                    # Symfony Messenger implementations
│       ├── Http/
│       │   ├── Filter/             # FiltersBuilder — parses query string into Filters
│       │   ├── Listener/           # ExceptionListener, ApiHeadersListener
│       │   ├── Request/            # JsonRequest base class
│       │   └── Response/           # ApiResponse (success, created, paginated, noContent)
│       ├── Logging/                # Monolog implementation
│       ├── Monitoring/             # Prometheus, OpenTelemetry
│       ├── Messaging/              # Dead letter handler
│       └── Persistence/
│           ├── Migrations/         # All migrations centralized here
│           └── Doctrine/
│               ├── Type/           # Custom Doctrine types
│               └── DoctrineFilterApplier.php  # Applies Filters to a QueryBuilder
│
└── <BoundedContext>/               # e.g. User, Product, Order
    ├── Domain/                     # Pure PHP — no framework dependency
    │   ├── Entity/
    │   ├── ValueObject/
    │   ├── Repository/             # Interfaces only
    │   ├── Event/                  # Domain events
    │   └── Exception/
    ├── Application/                # Use cases
    │   ├── Command/
    │   ├── Query/
    │   └── EventHandler/
    └── Infrastructure/             # Framework & persistence
        ├── Fixture/                # Doctrine fixtures (dev & test)
        ├── Persistence/
        │   └── Doctrine/
        │       ├── Mapping/        # XML mapping files
        │       └── Repository/
        ├── Messaging/              # RabbitMQ consumers
        ├── Http/
        │   ├── Controller/
        │   └── Request/
        └── EventSubscriber/
```

### Key design decisions

**Migrations are centralized** in `Shared/Infrastructure/Persistence/Migrations/`. Doctrine mappings stay in each Bounded Context — migrations are a global infrastructure concern.

**Doctrine mapping lives in XML** under each bounded context's `Infrastructure/Persistence/Doctrine/Mapping/` folder. The domain layer stays free of ORM attributes; only infrastructure owns mapping files.

**Three separate Messenger buses** — commands and queries are handled synchronously, domain events are dispatched asynchronously through RabbitMQ.

**Domain exceptions map to HTTP status codes** via a single `ExceptionListener` in `Shared/Infrastructure/Http/Listener/`, keeping HTTP concerns out of the domain. Messenger's `HandlerFailedException` is automatically unwrapped so domain exceptions propagate correctly.

**Uniform API response format** — all responses go through `ApiResponse` which wraps data under a `data` key, errors under an `error` key, and paginated results include a `meta` block. Property names are serialized to `snake_case` automatically via Symfony's `CamelCaseToSnakeCaseNameConverter`.

**Soft delete** — entities are never physically removed. A `status` field tracks their lifecycle (`active`, `inactive`, `deleted`). Repositories automatically exclude deleted records from queries.

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

### RabbitMQ

```bash
make consume          # start the event consumer
make consume-dl       # start the dead letter consumer
make outbox-relay     # publish persisted outbox events to the event bus
make messenger-stop   # gracefully stop all workers
make messenger-stats  # display transport stats
make messenger-failed-show   # list failed messages
make messenger-failed-retry  # retry all failed messages
make messenger-failed-remove # remove all failed messages
```

### Generating a new Bounded Context

Use the built-in maker command to scaffold the full DDD structure:

```bash
make bc name=Product
```
```
This generates the following structure:src/Product/
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
└── Messaging/ProductCreatedMessageHandler.phptests/
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

## REST API

Base path: `/api/v1`.

### Health check (CI/CD)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/health` | None | Liveness/readiness probe for pipelines and orchestrators |

Returns `200` when the API and database are reachable, `503` when the database check fails:

```json
{
  "data": {
    "status": "ok",
    "checks": {
      "api": "ok",
      "database": "ok"
    }
  }
}
```

Example for a deploy smoke test or GitHub Actions:

```bash
curl -sf http://localhost:8080/health
```

`-f` makes curl exit non-zero on HTTP 503, which fails the job if the stack is not ready.

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/auth/logout` | Revoke a refresh token (requires `Authorization: Bearer` access token) |
| `POST` | `/auth/refresh` | Rotate refresh token; returns a new token pair |
| `POST` | `/users` | Create a user |
| `GET` | `/users` | List users (filterable, sortable, paginated) |
| `GET` | `/users/{id}` | Fetch a user by UUID |
| `PATCH` | `/users/{id}` | Partially update a user |
| `PUT` | `/users/{id}` | Fully replace a user |
| `DELETE` | `/users/{id}` | Soft-delete a user |

### Response format

All responses follow a consistent envelope:

```json
// single resource
{ "data": { "id": "...", "first_name": "...", "last_name": "...", "email": "..." } }

// collection
{
    "data": [...],
    "meta": { "total": 100, "page": 1, "per_page": 20, "pages": 5 }
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
| RabbitMQ UI | http://localhost:15672 | app / see .env.local |
| Prometheus | http://localhost:9090 | — |
| Grafana | http://localhost:3000 | admin / see .env.local |
| PostgreSQL | localhost:5432 | app / see .env.local |

## Event flow

```
CommandHandler
  → repository->save(aggregate)
  → eventBus->publish(...aggregate->pullDomainEvents())
      → transactional outbox table (same DB transaction)
          → outbox relay command
              → RabbitMQ exchange "events" (topic)
                  → queue "events.<context>" (binding: <context>.#)
                      → MessageHandler

On failure after 3 retries:
  → failure_transport "async.dead_letter"
      → exchange "dead_letter"
          → queue "dead_letter"
              → DeadLetterMessageHandler
```

## Testing

Tests are organized in three suites matching the architecture layers.
```
tests/
├── Unit/           # Domain + Application — no I/O, fast
└── Integration/    # Infrastructure — hits the real database
```

```bash
make test             # run all test suites
make test-unit        # unit tests only
make test-integration # integration tests only
make test-coverage    # generate HTML coverage report in var/coverage/
```

## Code quality

Run static analysis and architecture checks:

```bash
make phpstan   # run PHPStan with phpstan.neon
make deptrac   # run Deptrac with deptrac.yaml
```

If Docker is not available in your environment, run them directly:

```bash
vendor/bin/phpstan analyse
vendor/bin/deptrac analyse --config-file=deptrac.yaml
```

### License

Proprietary
