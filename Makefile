.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMPOSE = docker compose -f docker/compose.yaml --env-file .env.local
PHP = $(DOCKER_COMPOSE) exec php
PHP_TEST = $(DOCKER_COMPOSE) exec -e APP_ENV=test php
CONSOLE = $(PHP) bin/console
CONSOLE_TEST = $(PHP_TEST) bin/console
COMPOSER = $(PHP) composer

GREEN = \033[0;32m
RESET = \033[0m

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-30s$(RESET) %s\n", $$1, $$2}'

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

down-v:
	$(DOCKER_COMPOSE) down -v

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

init: build up install db-fresh

bc:
	$(CONSOLE) make:bounded-context $(name)

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

er-diagram: ## Generate the ER diagram from Doctrine XML mappings
	@$(CONSOLE) app:generate:er-diagram
	@echo "ER diagram generated at docs/er-diagram.md"

ci: cs-check phpstan deptrac test-unit test-integration test-http ## Run all CI quality gates

test-coverage:
	$(PHP_TEST) php -d pcov.enabled=1 -d pcov.directory=/app/src -d pcov.exclude="#^/app/(vendor|tests)/#" vendor/bin/phpunit --coverage-html var/coverage

mail:
	open http://localhost:8025

metrics:
	open http://localhost:9090

grafana:
	open http://localhost:3000

openapi-export-json:
	mkdir -p var/openapi
	$(CONSOLE) nelmio:apidoc:dump --format=json > var/openapi/openapi.json
	@echo "OpenAPI JSON exported to var/openapi/openapi.json"

openapi-export-yaml:
	mkdir -p var/openapi
	$(CONSOLE) nelmio:apidoc:dump --format=yaml > var/openapi/openapi.yaml
	@echo "OpenAPI YAML exported to var/openapi/openapi.yaml"
