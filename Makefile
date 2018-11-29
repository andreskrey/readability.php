.PHONY: test-all start test-7.2 test-7.1 test-7.0 stop

test-all: start test-7.2 test-7.1 test-7.0 stop

test-7.2:
	docker-compose exec php-7.2 php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml

test-7.1:
	docker-compose exec php-7.1 php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml

test-7.0:
	docker-compose exec php-7.0 php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml

start:
	docker-compose up -d

stop:
	docker-compose stop
