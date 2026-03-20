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
| Logging | Monolog |
| Monitoring | Prometheus + Grafana |

## Architecture

This template follows Domain-Driven Design (DDD) principles with a clear separation of concerns across three layers per Bounded Context.

```
src/
├── Shared/                         # Cross-cutting concerns
│   ├── Domain/
│   │   ├── Bus/                    # Command, Query, Event bus interfaces
│   │   ├── ValueObject/            # Uuid, Email
│   │   ├── Exception/              # Base domain exceptions
│   │   └── Logging/                # Logger interface
│   └── Infrastructure/
│       ├── Bus/                    # Symfony Messenger implementations
│       ├── Logging/                # Monolog implementation
│       ├── Monitoring/             # Prometheus, OpenTelemetry
│       ├── Messaging/              # Dead letter handler
│       └── Persistence/
│           ├── Migrations/         # All migrations centralized here
│           └── Doctrine/
│               └── Type/           # Custom Doctrine types
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
        ├── Persistence/
        │   └── Doctrine/
        │       ├── Mapping/        # PHP mapping files (no XML, no attributes)
        │       └── Repository/
        ├── Messaging/              # RabbitMQ consumers
        ├── Http/
        │   ├── Controller/
        │   └── Request/
        └── EventSubscriber/
```

### Key design decisions

**Migrations are centralized** in `Shared/Infrastructure/Persistence/Migrations/`. Doctrine mappings stay in each Bounded Context — migrations are a global infrastructure concern.

**Doctrine mapping uses PHP files**, not XML or attributes. Attributes would pollute the Domain layer with infrastructure concerns.

**Three separate Messenger buses** — commands and queries are handled synchronously, domain events are dispatched asynchronously through RabbitMQ.

**Domain exceptions map to HTTP status codes** via a single `ExceptionListener` in `Shared/Infrastructure/Http/Listener/`, keeping HTTP concerns out of the domain.

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
```

### Database

```bash
make db-migrate   # run pending migrations
make db-rollback  # rollback last migration
make db-diff      # generate migration from schema diff
make db-reset     # drop, recreate and migrate
make db-validate  # validate Doctrine mapping
```

### RabbitMQ

```bash
make consume      # start the event consumer
make consume-dl   # start the dead letter consumer
make messenger-stop   # gracefully stop all workers
make messenger-stats  # display transport stats
```

## Adding a new Bounded Context

1. Create the directory structure under `src/<ContextName>/`
2. Add the Doctrine mapping in `config/packages/doctrine.yaml`
3. Add the RabbitMQ binding key in `config/packages/messenger.yaml`
4. Register your repository implementation in `config/services.yaml`

## Services

| Service | URL | Credentials |
|---|---|---|
| API | http://localhost:8080 | — |
| RabbitMQ UI | http://localhost:15672 | app / see .env.local |
| Prometheus | http://localhost:9090 | — |
| Grafana | http://localhost:3000 | admin / see .env.local |
| PostgreSQL | localhost:5432 | app / see .env.local |

## Event flow

```
CommandHandler
  → repository->save(aggregate)
  → eventBus->publish(...aggregate->pullDomainEvents())
      → RabbitMQ exchange "events" (topic)
          → queue "events.<context>" (binding: <context>.#)
              → MessageHandler

On failure after 3 retries:
  → exchange "dead_letter"
      → queue "dead_letter"
          → DeadLetterMessageHandler
```
