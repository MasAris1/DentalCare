DOCKER_COMPOSE=docker compose

.PHONY: help env presentation-env presentation-apply presentation-check public-url build up down logs ps shell install bootstrap demo deploy wait-db artisan composer npm migrate migrate-force seed frontend-build cache optimize-clear test audit verify queue-restart

help:
	@echo "DentalCare Docker shortcuts"
	@echo "  make bootstrap      Build image, install deps, migrate/seed, build assets"
	@echo "  make demo           Prepare and run app/nginx/queue for presentation"
	@echo "  make presentation-apply          Apply safe presentation defaults to .env"
	@echo "  make public-url URL=https://...  Update tunnel URL in .env"
	@echo "  make presentation-check          Validate presentation env"
	@echo "  make verify         Run npm audit, frontend build, composer audit, and tests"
	@echo "  make logs           Follow container logs"
	@echo "  make down           Stop and remove containers"

env:
	@test -f .env || cp .env.example .env

presentation-env:
	@test ! -f .env || (echo ".env already exists; not overwriting it. Copy values from .env.presentation.example manually if needed." >&2; exit 1)
	cp .env.presentation.example .env

presentation-apply: env
	php scripts/apply-presentation-env.php

presentation-check:
	./scripts/check-presentation-env.sh .env

public-url: env
	@test -n "$(URL)" || (echo "Usage: make public-url URL=https://your-tunnel-url" >&2; exit 1)
	php scripts/set-public-url.php "$(URL)"
	$(MAKE) optimize-clear cache

build:
	$(DOCKER_COMPOSE) build

up: env
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

logs:
	$(DOCKER_COMPOSE) logs -f

ps:
	$(DOCKER_COMPOSE) ps

shell:
	$(DOCKER_COMPOSE) exec app sh

wait-db:
	@echo "Waiting for MySQL..."
	@for i in $$(seq 1 40); do \
		if $(DOCKER_COMPOSE) exec -T mysql sh -lc 'mysqladmin ping -h127.0.0.1 -uroot -p"$$MYSQL_ROOT_PASSWORD" --silent' >/dev/null 2>&1; then \
			echo "MySQL is ready"; \
			exit 0; \
		fi; \
		sleep 2; \
	done; \
	echo "MySQL did not become ready in time" >&2; \
	exit 1

install: env
	$(DOCKER_COMPOSE) up -d mysql
	$(MAKE) wait-db
	$(DOCKER_COMPOSE) run --rm --no-deps app composer install --no-interaction --prefer-dist --optimize-autoloader
	$(DOCKER_COMPOSE) run --rm --no-deps app npm ci
	$(DOCKER_COMPOSE) run --rm app sh -lc 'grep -Eq "^APP_KEY=base64:.+" .env || php artisan key:generate'
	$(DOCKER_COMPOSE) run --rm app php artisan migrate --seed

frontend-build:
	$(DOCKER_COMPOSE) run --rm --no-deps app npm run build

optimize-clear:
	$(DOCKER_COMPOSE) run --rm app php artisan optimize:clear

cache:
	$(DOCKER_COMPOSE) run --rm app php artisan config:cache
	$(DOCKER_COMPOSE) run --rm app php artisan route:cache
	$(DOCKER_COMPOSE) run --rm app php artisan view:cache

bootstrap: env build install frontend-build optimize-clear cache

demo: bootstrap
	$(DOCKER_COMPOSE) up -d app nginx queue
	$(DOCKER_COMPOSE) exec app php artisan queue:restart || true
	@port=$$(awk -F= '/^APP_PORT=/{print $$2}' .env | tail -n 1 | tr -d '"'); echo "Demo is running at http://localhost:$${port:-8080}"

deploy: demo

artisan:
	$(DOCKER_COMPOSE) exec app php artisan $(ARGS)

composer:
	$(DOCKER_COMPOSE) run --rm --no-deps app composer $(ARGS)

npm:
	$(DOCKER_COMPOSE) run --rm --no-deps app npm $(ARGS)

migrate:
	$(DOCKER_COMPOSE) exec app php artisan migrate

migrate-force:
	$(DOCKER_COMPOSE) exec app php artisan migrate --force

seed:
	$(DOCKER_COMPOSE) exec app php artisan db:seed

test:
	$(DOCKER_COMPOSE) run --rm --no-deps app sh -lc 'rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/events.php && php artisan test'

audit:
	$(DOCKER_COMPOSE) run --rm --no-deps app npm audit --audit-level=moderate
	$(DOCKER_COMPOSE) run --rm --no-deps app composer audit --locked

verify: audit frontend-build test

queue-restart:
	$(DOCKER_COMPOSE) exec app php artisan queue:restart
