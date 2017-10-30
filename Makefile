all: install rebuild_front run

install:
	docker-compose up --no-deps -d php-fpm database

	docker exec -ti sandstone-php sh -c "cp -n docker/docker.env .env"
	docker exec -ti sandstone-php sh -c "cp -n docker/front/docker.env www/js/housing-app/.env"

	docker exec -ti sandstone-php sh -c "composer install"

	docker exec -ti sandstone-database sh -c "mysql -u root -proot -e 'create database if not exists sample;'"
	docker exec -ti sandstone-php sh -c "php application/console.php migrations:migrate"

update:
	docker-compose up --build --force-recreate --no-deps -d php-fpm database

	docker exec -ti sandstone-php sh -c "composer update"

	docker-compose up --build --force-recreate -d

rebuild_front:
	docker-compose up --no-deps -d node

	docker exec -ti housing-node sh -c "cd www/js/housing-app && npm install && npm run build && cp -rf build ../.."

run:
	docker-compose up -d

logs:
	docker-compose logs -ft

optimize_autoloader:
	docker exec -ti sandstone-php sh -c "composer install --optimize-autoloader"

bash:
	docker exec -ti sandstone-php bash

restart_websocket_server:
	docker restart sandstone-ws
