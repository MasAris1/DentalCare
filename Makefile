DOCKER_COMPOSE=docker compose

.PHONY: build up down logs shell install artisan composer npm migrate migrate-force deploy seed test queue-restart

build:
	$(DOCKER_COMPOSE) build

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

logs:
	$(DOCKER_COMPOSE) logs -f

shell:
	$(DOCKER_COMPOSE) exec app sh

install:
	$(DOCKER_COMPOSE) exec app composer install
	$(DOCKER_COMPOSE) exec app npm install
	$(DOCKER_COMPOSE) exec app php artisan key:generate
	$(DOCKER_COMPOSE) exec app php artisan migrate --seed

artisan:
	$(DOCKER_COMPOSE) exec app php artisan $(ARGS)

composer:
	$(DOCKER_COMPOSE) exec app composer $(ARGS)

npm:
	$(DOCKER_COMPOSE) exec app npm $(ARGS)

migrate:
	$(DOCKER_COMPOSE) exec app php artisan migrate

migrate-force:
	$(DOCKER_COMPOSE) exec app php artisan migrate --force

deploy:
	$(DOCKER_COMPOSE) up -d --build mysql app nginx
	$(DOCKER_COMPOSE) exec app php artisan migrate --force
	$(DOCKER_COMPOSE) exec app php artisan optimize:clear
	$(DOCKER_COMPOSE) exec app php artisan config:cache
	$(DOCKER_COMPOSE) exec app php artisan route:cache
	$(DOCKER_COMPOSE) exec app php artisan queue:restart
	$(DOCKER_COMPOSE) up -d --build queue

seed:
	$(DOCKER_COMPOSE) exec app php artisan db:seed

test:
	$(DOCKER_COMPOSE) exec app php artisan test

queue-restart:
	$(DOCKER_COMPOSE) exec app php artisan queue:restart
