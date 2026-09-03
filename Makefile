.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMPOSE = docker compose -f docker/compose.yaml --env-file .env.local
DOCKER_COMPOSE_ALL = $(DOCKER_COMPOSE) --profile monitoring
# Core stack : everything the app needs to run and be exercised manually.
CORE_SERVICES = php postgres rabbitmq redis garage mailpit
# CI stack: what the quality gate actually touches.
CI_SERVICES = php postgres rabbitmq redis garage
PHP = $(DOCKER_COMPOSE) exec -w /app php
PHP_TEST = $(DOCKER_COMPOSE) exec -w /app -e APP_ENV=test php
CONSOLE = $(PHP) bin/console
CONSOLE_TEST = $(PHP_TEST) bin/console
COMPOSER = $(PHP) composer

GREEN = \033[0;32m
RESET = \033[0m

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-30s$(RESET) %s\n", $$1, $$2}'

up: ## Start the core stack (php/FrankenPHP, postgres, rabbitmq, redis, garage, mailpit) and wait until healthy
	$(DOCKER_COMPOSE) up -d --wait $(CORE_SERVICES)

up-monitoring: ## Start the core stack plus Prometheus, Grafana and postgres_exporter
	$(DOCKER_COMPOSE_ALL) up -d --wait

up-ci: ## Start (and wait for) exactly the services CI's quality gate needs, then bootstrap Garage — mirrors .github/workflows/ci.yml, safe to run locally
	$(DOCKER_COMPOSE) up -d --wait $(CI_SERVICES)
	$(MAKE) garage-bootstrap

down: ## Stop and remove all containers, including monitoring and renamed/removed services
	$(DOCKER_COMPOSE_ALL) down --remove-orphans

down-v: ## Like down, but also remove volumes (data loss)
	$(DOCKER_COMPOSE_ALL) down -v --remove-orphans

build: ## Build (or rebuild without cache) all service images
	$(DOCKER_COMPOSE) build --no-cache

restart: down up ## Restart the stack (down + up)

logs: ## Tail logs for all services
	$(DOCKER_COMPOSE) logs -f

logs-php: ## Tail logs for the php service only
	$(DOCKER_COMPOSE) logs -f php

ps: ## List running containers and their status
	$(DOCKER_COMPOSE) ps

bash: ## Open a shell inside the php container (working dir /app)
	$(PHP) sh

install: ## Install Composer dependencies
	$(COMPOSER) install

update: ## Update Composer dependencies
	$(COMPOSER) update

clear: ## Clear the Symfony cache and re-dump the autoloader
	$(CONSOLE) cache:clear
	$(COMPOSER) dump-autoload -o

warmup: ## Warm up the Symfony cache
	$(CONSOLE) cache:warmup

db-create: ## Create the database if it doesn't already exist
	$(CONSOLE) doctrine:database:create --if-not-exists

db-drop: ## Drop the database, terminating active connections first
	$(DOCKER_COMPOSE) exec -T postgres sh -lc 'psql -U "$$POSTGRES_USER" -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '\''$$POSTGRES_DB'\'' AND pid <> pg_backend_pid();"'
	$(CONSOLE) doctrine:database:drop --force --if-exists

db-migrate: ## Run pending Doctrine migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

db-rollback: ## Roll back the last Doctrine migration
	$(CONSOLE) doctrine:migrations:migrate prev --no-interaction

db-status: ## Show the status of Doctrine migrations
	$(CONSOLE) doctrine:migrations:status

db-diff: ## Generate a new migration from entity/mapping changes
	$(CONSOLE) doctrine:migrations:diff

db-fixtures: ## Load fixtures (doctrine:fixtures:load)
	$(CONSOLE) doctrine:fixtures:load --no-interaction

db-fresh: db-reset db-fixtures ## Reset the database and reload fixtures (db-reset + db-fixtures)

db-reset: db-drop db-create db-migrate ## Drop, recreate and re-migrate the database

db-validate: ## Validate the Doctrine schema against mappings
	$(CONSOLE) doctrine:schema:validate

db-backup: ## Dump the database to var/backups/<timestamp>.dump (pg_dump custom format) — local/manual only, see docs/backup-and-restore.md
	@mkdir -p var/backups
	$(DOCKER_COMPOSE) exec -T postgres sh -lc 'pg_dump -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" -Fc' > "var/backups/$$(date +%Y%m%d%H%M%S).dump"
	@echo "Backup written to var/backups/"

db-restore: ## Restore the database from a dump file (file=var/backups/<name>.dump) — DESTRUCTIVE, overwrites existing data
	@test -n "$(file)" || (echo "Usage: make db-restore file=var/backups/<name>.dump" && exit 1)
	@test -f "$(file)" || (echo "File not found: $(file)" && exit 1)
	$(DOCKER_COMPOSE) exec -T postgres sh -lc 'pg_restore -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" --clean --if-exists --no-owner' < "$(file)"

debug-router: ## List all registered routes
	$(CONSOLE) debug:router

consume: ## Consume the async Messenger transport
	$(CONSOLE) messenger:consume async --time-limit=3600 -vv

webhook-consumer: ## Consume the webhook_delivery Messenger transport (outbound webhook HTTP delivery)
	$(CONSOLE) messenger:consume webhook_delivery --time-limit=3600 -vv

consume-dl: ## Consume the async dead-letter transport
	$(CONSOLE) messenger:consume async.dead_letter --time-limit=3600 -vv

messenger-stop: ## Signal Messenger workers to stop gracefully
	$(CONSOLE) messenger:stop-workers

messenger-stats: ## Show Messenger transport queue stats
	$(CONSOLE) messenger:stats

messenger-failed-show: ## Show messages currently in the failure transport
	$(CONSOLE) messenger:failed:show

messenger-failed-retry: ## Retry all messages in the failure transport
	$(CONSOLE) messenger:failed:retry --force

messenger-failed-remove: ## Discard all messages in the failure transport
	$(CONSOLE) messenger:failed:remove --all --force

outbox-relay: ## One-shot manual flush of the transactional outbox
	$(CONSOLE) app:outbox:relay

scheduler: ## Run the Scheduler worker (outbox relay + daily cleanups)
	$(CONSOLE) messenger:consume scheduler_default --time-limit=3600 -vv

init: build up install db-fresh garage-bootstrap ## First-time setup: build + up + install + db-fresh + garage-bootstrap

garage-bootstrap: ## One-time (idempotent) Garage bootstrap: layout, access key, buckets (keep bucket list in sync with document_storage.buckets)
	@set -a; . ./.env.local; set +a; \
	GARAGE="$(DOCKER_COMPOSE) exec -T garage /garage"; \
	NODE_ID=$$($$GARAGE node id -q | cut -d@ -f1); \
	if $$GARAGE status | grep -q "NO ROLE ASSIGNED"; then \
		echo "Assigning single-node layout..."; \
		$$GARAGE layout assign -z dc1 -c 1G "$$NODE_ID"; \
		VERSION=$$($$GARAGE layout show | grep "Current cluster layout version:" | awk '{print $$NF}'); \
		$$GARAGE layout apply --version $$((VERSION + 1)); \
	fi; \
	if ! $$GARAGE key info "$$S3_ACCESS_KEY" >/dev/null 2>&1; then \
		echo "Importing app access key..."; \
		$$GARAGE key import -n app-key "$$S3_ACCESS_KEY" "$$S3_SECRET_KEY" --yes; \
	fi; \
	$$GARAGE key allow --create-bucket "$$S3_ACCESS_KEY"; \
	for BUCKET in documents invoices; do \
		if ! $$GARAGE bucket info "$$BUCKET" >/dev/null 2>&1; then \
			echo "Creating bucket $$BUCKET..."; \
			$$GARAGE bucket create "$$BUCKET"; \
		fi; \
		$$GARAGE bucket allow "$$BUCKET" --read --write --owner --key "$$S3_ACCESS_KEY"; \
	done

bc: ## Scaffold a new bounded context (name=X [api-version=vN])
	$(CONSOLE) make:bounded-context $(name) $(if $(api-version),--api-version=$(api-version),)

crud: ## Scaffold CRUD (Domain+Application+Infra+tests) for an entity (context=X entity=Y)
	$(CONSOLE) make:bc-crud $(context) $(entity)

remove-crud: ## Remove a scaffolded CRUD (context=X entity=Y [force=1])
	$(CONSOLE) remove:bc-crud $(context) $(entity) $(if $(force),--force,)

remove-bc: ## Remove a bounded context (name=X [force=1]) — User, Document, Shared are protected
	$(CONSOLE) remove:bounded-context $(name) $(if $(force),--force,)

deptrac: ## Check architecture layer boundaries (run after every structural change)
	$(PHP) vendor/bin/deptrac analyse

composer-audit: ## Check installed dependencies against known security advisories
	$(COMPOSER) audit

phpstan: ## Run static analysis (level 9)
	$(PHP) vendor/bin/phpstan analyse

cs-fix: ## Apply PHP CS Fixer (@Symfony ruleset)
	$(PHP) vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

cs-check: ## Check code style without modifying files (dry-run, fails on drift)
	$(PHP) vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff

test: ## Run the full PHPUnit suite
	$(PHP_TEST) vendor/bin/phpunit

test-unit: ## Run unit tests only (tests/Unit — no I/O)
	$(PHP_TEST) vendor/bin/phpunit --testsuite=Unit

test-integration: ## Create/migrate the test database, then run integration tests (real Postgres/Garage)
	$(CONSOLE_TEST) doctrine:database:create --if-not-exists
	$(CONSOLE_TEST) doctrine:migrations:migrate --no-interaction
	$(PHP_TEST) vendor/bin/phpunit --testsuite=Integration

test-http: ## Create/migrate the test database, then run full-stack HTTP tests
	$(CONSOLE_TEST) doctrine:database:create --if-not-exists
	$(CONSOLE_TEST) doctrine:migrations:migrate --no-interaction
	$(PHP_TEST) vendor/bin/phpunit --testsuite=Http

er-diagram: ## Generate the ER diagram from Doctrine migrations
	@$(CONSOLE) app:generate:er-diagram
	@echo "ER diagram generated at docs/er-diagram.md"

ci: cs-check phpstan deptrac composer-audit test-unit test-integration test-http ## Run all CI quality gates

test-coverage: ## Generate an HTML coverage report in var/coverage/
	$(PHP_TEST) php -d pcov.enabled=1 -d pcov.directory=/app/src -d pcov.exclude="#^/app/(vendor|tests)/#" vendor/bin/phpunit --coverage-html var/coverage

mail: ## Open Mailpit UI
	open http://localhost:8025

metrics: ## Open Prometheus UI (requires "make up-monitoring")
	open http://localhost:9090

grafana: ## Open Grafana UI (requires "make up-monitoring")
	open http://localhost:3000

openapi-export-json: ## Export the OpenAPI spec as JSON to var/openapi/openapi.json
	mkdir -p var/openapi
	$(CONSOLE) nelmio:apidoc:dump --format=json > var/openapi/openapi.json
	@echo "OpenAPI JSON exported to var/openapi/openapi.json"

openapi-export-yaml: ## Export the OpenAPI spec as YAML to var/openapi/openapi.yaml
	mkdir -p var/openapi
	$(CONSOLE) nelmio:apidoc:dump --format=yaml > var/openapi/openapi.yaml
	@echo "OpenAPI YAML exported to var/openapi/openapi.yaml"

# =========================================================
# Production (single VM) — run on the deployment host, against
# docker/compose.prod.yaml, driven by that host's own .env.local
# (production values, never the dev ones). See docs/deployment.md.
# =========================================================
DOCKER_COMPOSE_PROD = docker compose -f docker/compose.prod.yaml --env-file .env.local

prod-pull: ## Pull the production image (php/scheduler/consumer) at $IMAGE_TAG
	$(DOCKER_COMPOSE_PROD) pull php scheduler consumer

prod-migrate: ## Run pending database migrations against production (one-off container)
	$(DOCKER_COMPOSE_PROD) run --rm php bin/console doctrine:migrations:migrate --no-interaction

prod-up: ## Start (or recreate) the production stack, waiting until healthy
	$(DOCKER_COMPOSE_PROD) up -d --wait

prod-deploy: prod-pull prod-migrate prod-up ## Full deploy: pull the image, migrate, then (re)start the stack

prod-down: ## Stop the production stack (data volumes are preserved)
	$(DOCKER_COMPOSE_PROD) down

prod-logs: ## Follow production logs (all services)
	$(DOCKER_COMPOSE_PROD) logs -f

prod-db-backup: ## Dump the production database to var/backups/<timestamp>.dump
	@mkdir -p var/backups
	$(DOCKER_COMPOSE_PROD) exec -T postgres sh -lc 'pg_dump -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" -Fc' > "var/backups/$$(date +%Y%m%d%H%M%S).dump"
	@echo "Backup written to var/backups/"

prod-db-restore: ## Restore the production database from a dump file (file=var/backups/<name>.dump) — DESTRUCTIVE
	@test -n "$(file)" || (echo "Usage: make prod-db-restore file=var/backups/<name>.dump" && exit 1)
	@test -f "$(file)" || (echo "File not found: $(file)" && exit 1)
	$(DOCKER_COMPOSE_PROD) exec -T postgres sh -lc 'pg_restore -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" --clean --if-exists --no-owner' < "$(file)"
