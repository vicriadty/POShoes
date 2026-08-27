SHELL := /bin/sh

COMPOSE ?= docker compose
PHP ?= php
ARTISAN ?= $(PHP) artisan
NPM ?= npm

.PHONY: help up down install migrate seed test test-unit test-feature test-cover lint format setup dev build-assets logs shell

help:
	@echo "POShoes Makefile"
	@echo ""
	@echo "make up            # start dev stack (docker compose up -d)"
	@echo "make down          # stop dev stack"
	@echo "make install       # composer install + npm install"
	@echo "make migrate       # run migrations"
	@echo "make seed          # run seeders"
	@echo "make setup         # install + key:generate + migrate + seed"
	@echo "make test          # run all tests (Pest)"
	@echo "make test-unit     # run unit tests"
	@echo "make test-feature  # run feature tests"
	@echo "make lint          # run Pint + static analysis"
	@echo "make dev           # run vite dev server"
	@echo "make build-assets  # build production assets"
	@echo "make logs          # tail app logs"
	@echo "make shell         # shell into app container (or host)"

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

install:
	composer install
	$(NPM) install

migrate:
	$(ARTISAN) migrate

seed:
	$(ARTISAN) db:seed

key:
	$(ARTISAN) key:generate

setup: install key migrate seed

test:
	$(PHP) vendor/bin/pest

test-unit:
	$(PHP) vendor/bin/pest --testsuite=Unit

test-feature:
	$(PHP) vendor/bin/pest --testsuite=Feature

test-cover:
	$(PHP) vendor/bin/pest --coverage

lint:
	./vendor/bin/pint --test
	./vendor/bin/phpstan analyse --no-progress

format:
	./vendor/bin/pint

dev:
	$(NPM) run dev

build-assets:
	$(NPM) run build

logs:
	$(COMPOSE) logs -f app

shell:
	$(COMPOSE) exec app sh

up-prod:
	$(COMPOSE) -f docker-compose.prod.yml up -d
