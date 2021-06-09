FROM ubuntu:20.04

ARG PHP=7.4
ARG DEBIAN_FRONTEND=noninteractive

# Install development tooling
RUN apt-get update -yqq && \
	apt-get install -yqq \
	software-properties-common build-essential git unzip

# Install PHP and extensions including PostgreSQL support
RUN apt-get install -yqq \
	php-pear pkg-config libevent-dev \
	php${PHP}-dev php${PHP}-mbstring php${PHP}-curl php${PHP}-pgsql

# Install libevent to speed up the framework
RUN printf "\n\n /usr/lib/x86_64-linux-gnu/\n\n\nno\n\n\n" | \
    pecl install event && \
    echo "extension=event.so" > /etc/php/${PHP}/cli/conf.d/event.ini

# Install Composer 2.0
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer

COPY ./ /var/www/
WORKDIR /var/www

RUN composer install --optimize-autoloader --classmap-authoritative --no-dev 

CMD php app.php start