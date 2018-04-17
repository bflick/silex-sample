all: install run

install:
	docker-compose up -d php-fpm database
	docker exec -ti sandstone-php sh -c "composer install"
	docker exec -ti sandstone-database sh -c "mysql -u root -pQ8TgXIdh4ix2pWUFcve2 -e 'create database if not exists sample;'"
	docker exec -ti sandstone-php sh -c "php application/console.php migrations:migrate"

update:
	docker-compose up --build --force-recreate --no-deps -d
	docker exec -ti sandstone-php sh -c "composer update"
	docker-compose up --build --force-recreate -d

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

front: rebuild-front
	docker exec -ti housing-node sh -c "rm -r ../../build.old"
	docker exec -ti housing-node sh -c "mv ../../build ../../build.old"
	docker exec -ti housing-node sh -c "mv build ../../build"

rebuild-front:
	docker exec -ti housing-node sh -c "npm run build"
