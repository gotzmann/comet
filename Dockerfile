# docker build -f Dockerfile -t cent --no-cache .

# TODO Check the real content of latest multistage build to be shure there no any passwords, secrets, etc

FROM centos:8 AS base
#FROM centos:8

ARG PHP=7.4

RUN dnf -qy module disable postgresql
RUN dnf install -y https://download.postgresql.org/pub/repos/yum/reporpms/EL-8-x86_64/pgdg-redhat-repo-latest.noarch.rpm > /dev/null
RUN dnf install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm > /dev/null
RUN dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm > /dev/null
RUN dnf -y install dnf-utils > /dev/null
RUN dnf module enable -y postgresql:12 > /dev/null
RUN dnf module enable -y php:remi-7.4 > /dev/null

# https://forum.remirepo.net/viewtopic.php?id=3911
RUN dnf config-manager --set-enabled PowerTools

# libpq-devel-12.1
RUN dnf install -y \
    postgresql \
    php-cli php-common php-devel php-pgsql php-mysql php-pear php-json php-zip php-curl php-mbstring \
    libevent-devel php-pecl-event \
    mc htop make wget curl unzip > /dev/null

# Setup event lib for Worker speed up
#RUN printf "\n\n /usr/lib/x86_64-linux-gnu/\n\n\nno\n\n\n" | \
#RUN    pecl install event > /dev/null && \
#RUN pecl install libevent
# NB! pecl/event is already installed and is the same as the released version 2.5.4
#RUN pecl install event
#RUN pecl install eio
#    echo "extension=event.so" > /etc/php/${PHP}/cli/conf.d/event.ini
#RUN echo "extension=event.so" > /etc/php/${PHP}/cli/conf.d/event.ini

RUN curl -sS https://getcomposer.org/installer |php
RUN mv composer.phar /usr/local/bin/composer

COPY ./ /var/www/
#RUN rm -rf /var/www/vendor
#COPY php.ini /etc/php/7.4/cli/php.ini

#RUN cd /var/www
WORKDIR /var/www
#RUN composer install --optimize-autoloader --classmap-authoritative --no-dev --quiet
RUN composer install --optimize-autoloader --classmap-authoritative --no-dev

#COPY entrypoint.sh /entrypoint.sh
#TODO Remove ALL temporary and secret files before get final slim image
RUN rm -rf .git .env .env.dev log/sberprime.log

#COPY entrypoint.sh /entrypoint.sh

FROM base

COPY / /
WORKDIR /var/www
RUN chmod +x entrypoint.sh
ENTRYPOINT ["./entrypoint.sh"]
