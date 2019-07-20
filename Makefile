.PHONY: test-all

test-all: start test-7.3 test-7.2 test-7.1 test-7.0 stop

test-7.3:
	docker-compose exec php-7.3-libxml-2.9.9 php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml

test-7.2:
	docker-compose exec php-7.2-libxml-2.9.9 php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml

test-7.1:
	docker-compose exec php-7.1-libxml-2.9.9 php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml

test-7.0:
	docker-compose exec php-7.0-libxml-2.9.9 php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml

start:
	docker-compose up -d php-7.3-libxml-2.9.9 php-7.2-libxml-2.9.9 php-7.1-libxml-2.9.9 php-7.0-libxml-2.9.9

stop:
	docker-compose stop
