up:
	docker compose up --build

test:
	docker compose run --rm app vendor/bin/phpunit

analyse:
	docker compose run --rm app vendor/bin/phpstan analyse --memory-limit=512M

cs:
	docker compose run --rm app vendor/bin/phpcs

check:
	docker compose run --rm app composer check

migrate:
	docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction

shell:
	docker compose run --rm app sh

down:
	docker compose down --remove-orphans
