ARG PHP_VERSION
ARG LIBXML_VERSION
FROM andreskrey/php-${PHP_VERSION}:libxml-${LIBXML_VERSION}

RUN apt-get update

# Check if there's a pinned version of Xdebug for compatibility reasons
ARG XDEBUG_VERSION
RUN pecl install xdebug$(if [ ! ${XDEBUG_VERSION} = '' ]; then echo -${XDEBUG_VERSION} ; fi) && docker-php-ext-enable xdebug


# Required by coveralls
RUN apt-get install git -y
