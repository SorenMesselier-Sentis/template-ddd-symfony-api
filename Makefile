.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMPOSE = docker compose -f docker/compose.yaml --env-file .env.local
DOCKER_COMPOSE_ALL = $(DOCKER_COMPOSE) --profile monitoring
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

up: ## Start the core stack (php, nginx, postgres, rabbitmq, redis, garage, mailpit)
	$(DOCKER_COMPOSE) up -d

up-monitoring: ## Start the core stack plus Prometheus, Grafana and postgres_exporter
	$(DOCKER_COMPOSE_ALL) up -d

down: ## Stop and remove all containers, including monitoring if running
	$(DOCKER_COMPOSE_ALL) down

down-v: ## Like down, but also remove volumes (data loss)
	$(DOCKER_COMPOSE_ALL) down -v

build:
	$(DOCKER_COMPOSE) build --no-cache

restart: down up

logs:
	$(DOCKER_COMPOSE) logs -f

logs-php:
	$(DOCKER_COMPOSE) logs -f php

ps:
	$(DOCKER_COMPOSE) ps

bash:
	$(PHP) sh

install:
	$(COMPOSER) install

update:
	$(COMPOSER) update

clear:
	$(CONSOLE) cache:clear
	$(COMPOSER) dump-autoload -o

warmup:
	$(CONSOLE) cache:warmup

db-create:
	$(CONSOLE) doctrine:database:create --if-not-exists

db-drop:
	$(DOCKER_COMPOSE) exec -T postgres sh -lc 'psql -U "$$POSTGRES_USER" -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '\''$$POSTGRES_DB'\'' AND pid <> pg_backend_pid();"'
	$(CONSOLE) doctrine:database:drop --force --if-exists

db-migrate:
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

db-rollback:
	$(CONSOLE) doctrine:migrations:migrate prev --no-interaction

db-status:
	$(CONSOLE) doctrine:migrations:status

db-diff:
	$(CONSOLE) doctrine:migrations:diff

db-fixtures:
	$(CONSOLE) doctrine:fixtures:load --no-interaction

db-fresh: db-reset db-fixtures

db-reset: db-drop db-create db-migrate

db-validate:
	$(CONSOLE) doctrine:schema:validate

debug-router:
	$(CONSOLE) debug:router

consume:
	$(CONSOLE) messenger:consume async --time-limit=3600 -vv

consume-dl:
	$(CONSOLE) messenger:consume async.dead_letter --time-limit=3600 -vv

messenger-stop:
	$(CONSOLE) messenger:stop-workers

messenger-stats:
	$(CONSOLE) messenger:stats

messenger-failed-show:
	$(CONSOLE) messenger:failed:show

messenger-failed-retry:
	$(CONSOLE) messenger:failed:retry --force

messenger-failed-remove:
	$(CONSOLE) messenger:failed:remove --all --force

outbox-relay:
	$(CONSOLE) app:outbox:relay

scheduler:
	$(CONSOLE) messenger:consume scheduler_default --time-limit=3600 -vv

init: build up install db-fresh garage-bootstrap

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

bc:
	$(CONSOLE) make:bounded-context $(name) $(if $(api-version),--api-version=$(api-version),)

crud:
	$(CONSOLE) make:bc-crud $(context) $(entity)

remove-crud:
	$(CONSOLE) remove:bc-crud $(context) $(entity) $(if $(force),--force,)

remove-bc:
	$(CONSOLE) remove:bounded-context $(name) $(if $(force),--force,)

deptrac:
	$(PHP) vendor/bin/deptrac analyse

phpstan:
	$(PHP) vendor/bin/phpstan analyse

cs-fix:
	$(PHP) vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

cs-check:
	$(PHP) vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff

test:
	$(PHP_TEST) vendor/bin/phpunit

test-unit:
	$(PHP_TEST) vendor/bin/phpunit --testsuite=Unit

test-integration:
	$(CONSOLE_TEST) doctrine:database:create --if-not-exists
	$(CONSOLE_TEST) doctrine:migrations:migrate --no-interaction
	$(PHP_TEST) vendor/bin/phpunit --testsuite=Integration

test-http:
	$(CONSOLE_TEST) doctrine:database:create --if-not-exists
	$(CONSOLE_TEST) doctrine:migrations:migrate --no-interaction
	$(PHP_TEST) vendor/bin/phpunit --testsuite=Http

er-diagram: ## Generate the ER diagram from Doctrine migrations
	@$(CONSOLE) app:generate:er-diagram
	@echo "ER diagram generated at docs/er-diagram.md"

ci: cs-check phpstan deptrac test-unit test-integration test-http ## Run all CI quality gates

test-coverage:
	$(PHP_TEST) php -d pcov.enabled=1 -d pcov.directory=/app/src -d pcov.exclude="#^/app/(vendor|tests)/#" vendor/bin/phpunit --coverage-html var/coverage

mail: ## Open Mailpit UI
	open http://localhost:8025

metrics: ## Open Prometheus UI (requires "make up-monitoring")
	open http://localhost:9090

grafana: ## Open Grafana UI (requires "make up-monitoring")
	open http://localhost:3000

openapi-export-json:
	mkdir -p var/openapi
	$(CONSOLE) nelmio:apidoc:dump --format=json > var/openapi/openapi.json
	@echo "OpenAPI JSON exported to var/openapi/openapi.json"

openapi-export-yaml:
	mkdir -p var/openapi
	$(CONSOLE) nelmio:apidoc:dump --format=yaml > var/openapi/openapi.yaml
	@echo "OpenAPI YAML exported to var/openapi/openapi.yaml"
