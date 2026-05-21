.PHONY: up down build restart logs \
        shell-laravel shell-nextjs shell-postgres \
        migrate seed test-backend \
        install-backend install-frontend

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

restart: down up

logs:
	docker compose logs -f

shell-laravel:
	docker compose exec laravel sh

shell-nextjs:
	docker compose exec nextjs sh

shell-postgres:
	docker compose exec postgres psql -U jurivexai -d jurivexai

migrate:
	docker compose exec laravel php artisan migrate

seed:
	docker compose exec laravel php artisan db:seed

test-backend:
	docker compose exec laravel php artisan test

install-backend:
	docker compose exec laravel composer install

install-frontend:
	docker compose exec nextjs npm install
