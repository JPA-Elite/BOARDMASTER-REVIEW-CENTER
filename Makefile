DEV_ENV ?= .env.docker
DEV_COMPOSE ?= docker-compose.yml

PROD_ENV ?= .env.docker.prod
PROD_COMPOSE ?= docker-compose.prod.yml

APP_SERVICE ?= app

.PHONY: dev-build dev-up dev-migrate dev-deploy prod-build prod-up prod-migrate prod-deploy
.PHONY: dev-seed prod-seed
.PHONY: dev-down prod-down

dev-build:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) build

dev-up:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) up -d

dev-down:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) down

dev-migrate:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) run --rm $(APP_SERVICE) php artisan migrate

dev-seed:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) run --rm $(APP_SERVICE) php artisan db:seed --force

dev-deploy: dev-build dev-up dev-migrate

prod-build:
	docker compose --env-file $(PROD_ENV) -f $(PROD_COMPOSE) build

prod-up:
	docker compose --env-file $(PROD_ENV) -f $(PROD_COMPOSE) up -d

prod-down:
	docker compose --env-file $(PROD_ENV) -f $(PROD_COMPOSE) down

prod-migrate:
	docker compose --env-file $(PROD_ENV) -f $(PROD_COMPOSE) run --rm $(APP_SERVICE) php artisan migrate --force

prod-seed:
	docker compose --env-file $(PROD_ENV) -f $(PROD_COMPOSE) run --rm $(APP_SERVICE) php artisan db:seed --force

prod-deploy: prod-build prod-up prod-migrate
