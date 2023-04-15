up:
	docker-compose up -d --build
rr:
	make kill
	make up
rm:
	docker-compose rm
logs:
	docker-compose logs -f
kill:
	docker-compose kill
php:
	docker exec -it karma8_php_1 su --shell=/bin/bash
db:
	docker exec -it karma8_db_1 bash
