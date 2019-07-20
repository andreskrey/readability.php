ARG php_version
ARG libxml_version
FROM andreskrey/php-${php_version}-libxml-${libxml_version}

RUN pecl install xdebug && docker-php-ext-enable xdebug

# Required by coveralls
RUN apt-get install git -y
