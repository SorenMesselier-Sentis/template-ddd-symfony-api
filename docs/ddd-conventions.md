# DDD Conventions

This guide describes how bounded contexts are structured in this template, which Deptrac rules apply, and the checklist to follow when adding a new context to a forked project.

For stack setup and day-to-day commands, see the [README](../README.md).

---

## Layer model

Each bounded context under `src/<Name>/` is split into three layers:

```
src/<Name>/
├── Domain/           # Pure PHP — entities, value objects, repository ports, domain events, exceptions
├── Application/      # Use cases — commands, queries, handlers (no framework imports)
└── Infrastructure/   # Symfony, Doctrine, HTTP, messaging, fixtures
```

Cross-cutting code lives in `src/Shared/` with the same Domain / Infrastructure split. **Shared must never import a specific bounded context.**

### Messenger buses

| Bus | Purpose | Transport |
|---|---|---|
| `command.bus` | Write use cases | Synchronous |
| `query.bus` | Read use cases | Synchronous |
| `event.bus` | Domain events | Async via outbox → RabbitMQ |

Command and query handlers live in `Application/`. Domain events are published from aggregates and relayed asynchronously.

---

## Deptrac rules

Run `make deptrac` (or `vendor/bin/deptrac analyse`) after every structural change.

| Layer | May depend on |
|---|---|
| **Domain** (BC) | `Shared/Domain`, its own `Domain` |
| **Application** (BC) | `Shared/Domain`, its own `Domain`, its own `Application` |
| **Infrastructure** (BC) | `Shared/*`, its own `Domain`, `Application`, `Infrastructure` |
| **Shared/Infrastructure** | `Shared/Domain`, `Shared/Infrastructure` only |

**Forbidden:** `Infrastructure` of BC A importing any class from BC B (including fixtures, repositories, HTTP controllers).

**Cross-BC references** use stable identifiers only (e.g. `OwnerId` UUID in Document, not a Doctrine relation to `User`). Shared fixture data uses `FixtureData` / `FixtureReference` — see [README — Fixtures](../README.md#fixtures-and-test-data).

---

## Checklist: add a bounded context

### 1. Scaffold the bounded context

```bash
make bc name=Product
# optional API version: bin/console make:bounded-context Product --api-version=v1
```

The command creates the DDD folder structure and auto-registers:

- HTTP routes in `config/routes.yaml` (`api_<version>_<context>`)
- Doctrine XML mapping entry in `config/packages/doctrine.yaml` (BC-level)

### 2. Generate CRUD entities

```bash
make crud context=Product entity=Product
# additional entities in the same context:
make crud context=Order entity=OrderLine
```

`make crud` generates Domain, Application, Infrastructure, tests, and auto-registers per entity:

- Doctrine custom type in `config/packages/doctrine.yaml`
- Repository alias in `config/services.yaml`
- RabbitMQ queue binding `events.<entity>` in `config/packages/messenger.yaml`

### 3. Domain layer

After `make crud`, review and extend the generated domain code:

- [ ] Entity with `status` enum (`active`, `inactive`, `deleted`) for soft-delete consistency
- [ ] Value objects for identifiers and invariants
- [ ] `*RepositoryInterface` in `Domain/Repository/`
- [ ] Domain events (`*Created`, `*Updated`, `*Deleted`) with `pullDomainEvents()` on the aggregate
- [ ] Exceptions extending `Shared/Domain/Exception/` bases (`NotFoundException`, `AlreadyExistsException`, …)
- [ ] Each exception exposes `errorCode(): string` (e.g. `product.not_found`)

### 4. Application layer

- [ ] One folder per use case: `Command/<Action>/` or `Query/<Action>/`
- [ ] Command/query DTO + handler
- [ ] Handlers depend on domain ports only (repositories, domain services)
- [ ] Authorization via `AuthorizedMessage` + `RoleRequirement` when the use case is protected (see User BC)

### 5. Infrastructure — persistence

- [ ] XML mapping in `Infrastructure/Persistence/Doctrine/Mapping/<Entity>.orm.xml` (no ORM attributes in Domain)
- [ ] Custom Doctrine types for value object IDs (`Infrastructure/Persistence/Doctrine/Type/`)
- [ ] `Doctrine<Entity>Repository` implementing the domain interface
- [ ] **Migration** generated into `Shared/Infrastructure/Persistence/Migrations/` (centralized — never per BC):

```bash
make db-diff
make db-migrate
```

### 6. Infrastructure — HTTP

- [ ] Controllers in `Infrastructure/Http/Controller/` with `#[Route]` paths **without** the `/api/v1` prefix
- [ ] Request DTOs extending `Shared/Infrastructure/Http/Request/JsonRequest` when JSON body validation is needed
- [ ] Responses via `ApiResponse` (envelope `data` / `error`)
- [ ] OpenAPI attributes on controllers (`OpenApi\Attributes`)
- [ ] **Exception mapper** — see below

### 7. Infrastructure — messaging

- [ ] RabbitMQ binding in `config/packages/messenger.yaml` (auto-added by `make crud`):

```yaml
events.product:
    binding_keys: ['product.#']
```

- [ ] Async event handlers in `Infrastructure/EventHandler/` with `#[AsMessageHandler(bus: 'event.bus')]`
- [ ] Side-effects (email, notifications) in event handlers, not in command handlers

### 8. Fixtures

- [ ] `<Name>Fixture.php` in `Infrastructure/Fixture/`
- [ ] Use `FixtureData` / `FixtureReference` from Shared for stable IDs and cross-test values
- [ ] No `Shared/Infrastructure` orchestrator importing other BC fixtures (Deptrac)
- [ ] Cross-BC links via UUID constants only, or [fixture groups](../README.md#fixtures-and-test-data) when a real entity dependency exists

### 9. Tests

| Suite | Location | Scope |
|---|---|---|
| **Unit** | `tests/Unit/<Name>/` | Domain + Application handlers (no I/O) |
| **Integration** | `tests/Integration/<Name>/` | Doctrine repositories, adapters (real DB / Garage) |
| **Http** | `tests/Http/<Name>/` | Full stack via `KernelBrowser`, extend `HttpTestCase` |

`make crud` generates Unit and Integration tests. Add Http tests manually when needed.

```bash
make test-unit
make test-integration
make test-http
make ci    # all quality gates
```

### 10. Post-generation steps

1. Add business fields to the entity, repository interface, and XML mapping
2. Implement request DTOs and controller logic for writable endpoints
3. Create `<Name>ExceptionMapper` if any exception needs a non-default HTTP code
4. Register the exception mapper in `config/services.yaml` with tag `app.exception_mapper`
5. Extend `FixtureData` / `FixtureReference` if needed
6. Write Http tests for new endpoints
7. If the entity holds personal data about a user, implement `Shared\Domain\Privacy\PersonalDataExporterInterface` (mandatory — see README "GDPR data export")
8. If any command exposes a sensitive action (delete, role/permission change, auth event), implement `Shared\Domain\Audit\AuditableMessage` on it (mandatory for that action — see README "Audit trail")
9. If an entity relates to another entity in the *same* BC, use a real Doctrine relation (`fetch="EAGER"` if the target's id is `readonly`); keep cross-BC references UUID-only (see the `Project`/`Task` reference BC)
10. `make db-diff` → `make db-migrate`
11. `make ci` before opening a PR

### 11. Remove scaffolding

```bash
make remove-crud context=Product entity=Product   # remove one CRUD entity
make remove-bc name=Product                       # remove the entire bounded context
```

Both commands ask for confirmation unless `force=1` is passed. `User`, `Document`, and `Shared` are protected.

| Make target | Console command |
|---|---|
| `make bc name=…` | `make:bounded-context` |
| `make crud context=… entity=…` | `make:bc-crud` |
| `make remove-crud context=… entity=…` | `remove:bc-crud` |
| `make remove-bc name=…` | `remove:bounded-context` |

---

## Exception mappers

`Shared/Infrastructure/Http/Listener/ExceptionListener` maps exceptions to HTTP responses. It has **no imports from bounded contexts**. BC-specific mappings live in `<Context>ExceptionMapper` classes.

**Interface:** `Shared/Infrastructure/Http/ExceptionMapperInterface`

```php
public function supports(\Throwable $exception): bool;
/** @return array{0: int, 1: string} status code + error code */
public function resolve(\Throwable $exception): array;
```

**Examples:** `UserExceptionMapper`, `DocumentExceptionMapper`

**Registration** in `config/services.yaml`:

```yaml
App\Product\Infrastructure\Http\ProductExceptionMapper:
    tags: ['app.exception_mapper']
```

Create a mapper when a domain exception needs a specific HTTP status or error code beyond the defaults (`404` for `NotFoundException`, `422` for `DomainException`, etc.).

Unmapped `DomainException` subclasses fall back to `422 / domain_error`. Unknown throwables return `500 / internal_server_error`.

---

## Reference bounded contexts

| BC | Purpose | Notable patterns |
|---|---|---|
| **User** | Auth, users, refresh tokens | JWT, `AuthorizedMessage`, welcome email event handlers |
| **Document** | S3-compatible object storage (Garage/R2) | Storage ports, `OwnerId` without FK, `DocumentExceptionMapper` |
| **Project** | Projects with tasks | Real `<many-to-one>` between `Task`→`Project` (same BC — contrast with `Document`'s FK-less `OwnerId`), cross-BC `assigneeId`/`attachmentId` UUIDs, `DependentFixtureInterface` for fixture ordering |
| **Shared** | Buses, outbox, email, health, HTTP envelope | No BC imports |

---

## Quality gates

Before every PR:

```bash
make ci
```

Runs `cs-check`, `phpstan` (level 9), `deptrac`, and all PHPUnit suites.

Optional local hooks: [README — Pre-commit](../README.md#pre-commit-hooks-recommended).
