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

test-all-versions:
	for php_version in 7.0 7.1 7.2 7.3; do \
	    for libxml_version in 2.9.4 2.9.5 2.9.6 2.9.7 2.9.8 2.9.9; do \
			docker-compose up -d php-$$php_version-libxml-$$libxml_version; \
			docker-compose exec php-$$php_version-libxml-$$libxml_version php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml; \
		done \
	done
	docker-compose stop
