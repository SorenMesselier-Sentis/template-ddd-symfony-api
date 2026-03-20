.DEFAULT_GOAL := help
.PHONY: help

DOCKER_COMPOSE = docker compose -f docker/compose.yaml --env-file .env.local
PHP = $(DOCKER_COMPOSE) exec php
CONSOLE = $(PHP) bin/console
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

warmup:
	$(CONSOLE) cache:warmup

db-create:
	$(CONSOLE) doctrine:database:create --if-not-exists

db-drop:
	$(CONSOLE) doctrine:database:drop --force --if-exists

db-migrate:
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

db-rollback:
	$(CONSOLE) doctrine:migrations:migrate prev --no-interaction

db-status:
	$(CONSOLE) doctrine:migrations:status

db-diff:
	$(CONSOLE) doctrine:migrations:diff

db-reset: db-drop db-create db-migrate

db-validate:
	$(CONSOLE) doctrine:schema:validate

consume:
	$(CONSOLE) messenger:consume async --time-limit=3600 -vv

consume-dl:
	$(CONSOLE) messenger:consume async.dead_letter --time-limit=3600 -vv

messenger-stop:
	$(CONSOLE) messenger:stop-workers

messenger-stats:
	$(CONSOLE) messenger:stats

init: build up install db-create db-migrate
