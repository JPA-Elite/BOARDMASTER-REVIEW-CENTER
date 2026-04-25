DEV_ENV ?= .env.docker
DEV_COMPOSE ?= docker-compose.yml

PROD_ENV ?= .env.docker.prod
PROD_COMPOSE ?= docker-compose.prod.yml

APP_SERVICE ?= app
SCHEDULER_SERVICE ?= scheduler
TAIL ?= 200

-include $(DEV_ENV)

DEV_API_BASE ?= $(APP_API_URL)
DEV_API_BASE ?= http://localhost:8080

.PHONY: dev-build dev-up dev-migrate dev-deploy prod-build prod-up prod-migrate prod-deploy
.PHONY: dev-seed prod-seed
.PHONY: dev-down prod-down
.PHONY: dev-login-token
.PHONY: dev-exec-app prod-exec-app
.PHONY: dev-logs-scheduler prod-logs-scheduler
.PHONY: dev-down-exclude

dev-build:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) build

dev-up:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) up -d

dev-down:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) down

# Down all containers except specified services
# Usage: make dev-down-exclude EXCLUDE=db
#        make dev-down-exclude EXCLUDE="db,phpmyadmin"
dev-down-exclude:
	@if [ -z "$(EXCLUDE)" ]; then \
		echo "Usage: make dev-down-exclude EXCLUDE=<service1,service2,...>"; \
		echo "Example: make dev-down-exclude EXCLUDE=db"; \
		exit 1; \
	fi; \
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) config --services | \
	while read -r service; do \
		if echo ",$(EXCLUDE)," | grep -q ",$$service,"; then \
			echo "Keeping $$service running"; \
		else \
			echo "Stopping $$service"; \
			docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) stop $$service 2>/dev/null || true; \
		fi \
	done

# Remove all containers except specified services (use this to keep DB running)
# Usage: make dev-rm-exclude EXCLUDE=db
#        make dev-rm-exclude EXCLUDE="db,phpmyadmin"
dev-rm-exclude:
	@if [ -z "$(EXCLUDE)" ]; then \
		echo "Usage: make dev-rm-exclude EXCLUDE=<service1,service2,...>"; \
		echo "Example: make dev-rm-exclude EXCLUDE=db"; \
		exit 1; \
	fi; \
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) config --services | \
	while read -r service; do \
		if echo ",$(EXCLUDE)," | grep -q ",$$service,"; then \
			echo "Keeping $$service"; \
		else \
			echo "Removing $$service"; \
			docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) rm -f $$service 2>/dev/null || true; \
		fi \
	done

dev-migrate:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) run --rm $(APP_SERVICE) php artisan migrate

dev-seed:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) run --rm $(APP_SERVICE) php artisan db:seed --force

dev-deploy: dev-build dev-up dev-migrate

dev-exec-app:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) exec $(APP_SERVICE) bash

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

prod-exec-app:
	docker compose --env-file $(PROD_ENV) -f $(PROD_COMPOSE) exec $(APP_SERVICE) bash

dev-logs-scheduler:
	docker compose --env-file $(DEV_ENV) -f $(DEV_COMPOSE) logs -f --tail=$(TAIL) $(SCHEDULER_SERVICE)

prod-logs-scheduler:
	docker compose --env-file $(PROD_ENV) -f $(PROD_COMPOSE) logs -f --tail=$(TAIL) $(SCHEDULER_SERVICE)

dev-login-token:
	@EMAIL_INPUT="$(EMAIL)"; \
	PASSWORD_INPUT="$(PASSWORD)"; \
	if [ -z "$$EMAIL_INPUT" ]; then \
		printf "Email: "; \
		read EMAIL_INPUT; \
	fi; \
	if [ -z "$$PASSWORD_INPUT" ]; then \
		printf "Password: "; \
		stty -echo; \
		read PASSWORD_INPUT; \
		stty echo; \
		printf "\n"; \
	fi; \
	if [ -z "$$EMAIL_INPUT" ] || [ -z "$$PASSWORD_INPUT" ]; then \
		echo "Email and password are required."; \
		exit 1; \
	fi; \
	RESPONSE=$$(curl -sS -X POST "$(DEV_API_BASE)/api/mobile/login" \
		-H "Accept: application/json" \
		-H "Content-Type: application/json" \
		-d "{\"email\":\"$$EMAIL_INPUT\",\"password\":\"$$PASSWORD_INPUT\"}"); \
	TOKEN=$$(printf "%s" "$$RESPONSE" | php -r '$$json = json_decode(stream_get_contents(STDIN), true); if (is_array($$json) && isset($$json["token"]) && is_string($$json["token"])) { echo $$json["token"]; }'); \
	if [ -n "$$TOKEN" ]; then \
		echo "$$TOKEN"; \
	else \
		echo "$$RESPONSE"; \
		exit 1; \
	fi
