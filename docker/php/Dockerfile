ARG PHP_VERSION
ARG LIBXML_VERSION
FROM andreskrey/php-${PHP_VERSION}:libxml-${LIBXML_VERSION}

RUN pecl install xdebug && docker-php-ext-enable xdebug

# Required by coveralls
RUN apt-get install git -y
