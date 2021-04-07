FROM ubuntu:20.04

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update -yqq && apt-get install -yqq \
	software-properties-common build-essential 

RUN LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php > /dev/null
RUN apt-get update -yqq && apt-get install -yqq \
    php7.4-dev php7.4-curl composer php-pear pkg-config libevent-dev 

RUN printf "\n\n /usr/lib/x86_64-linux-gnu/\n\n\nno\n\n\n" | \
    pecl install event && \
    echo "extension=event.so" > /etc/php/7.4/cli/conf.d/event.ini

COPY ./ /var/www/
WORKDIR /var/www
RUN composer install --optimize-autoloader --classmap-authoritative --no-dev --quiet

CMD php app.php start