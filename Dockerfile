# docker build -f Dockerfile -t cent --no-cache .

FROM centos:8

ARG PHP=7.4

RUN dnf install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
RUN dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
RUN dnf -y install dnf-utils
RUN dnf module enable -y php:remi-7.4

# https://forum.remirepo.net/viewtopic.php?id=3911
RUN dnf config-manager --set-enabled PowerTools

#RUN dnf install php php-cli php-common
RUN dnf install -y \
#    php${PHP}-dev php${PHP}-mysql composer php-pear libevent-dev iproute2 mysql-client \
    php-devel php-mysql php-pear libevent-devel \
    php-cli php-json php-zip wget curl unzip \
    php-pecl-event php-mbstring \
    mc htop make > /dev/null

# Setup event lib for Worker speed up
#RUN printf "\n\n /usr/lib/x86_64-linux-gnu/\n\n\nno\n\n\n" | \
#RUN    pecl install event > /dev/null && \
#RUN pecl install libevent
# NB! pecl/event is already installed and is the same as the released version 2.5.4
#RUN pecl install event
#RUN pecl install eio
#    echo "extension=event.so" > /etc/php/${PHP}/cli/conf.d/event.ini

RUN curl -sS https://getcomposer.org/installer |php
RUN mv composer.phar /usr/local/bin/composer

COPY ./ /var/www/
RUN rm -rf /var/www/vendor
#COPY php.ini /etc/php/7.4/cli/php.ini

WORKDIR /var/www
#RUN composer install --optimize-autoloader --classmap-authoritative --no-dev --quiet
RUN composer install --optimize-autoloader --classmap-authoritative --no-dev

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
