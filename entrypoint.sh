#!/bin/bash

echo "Adding host.docker.internal to /etc/hosts ..."
ip -4 route list match 0/0 | awk '{print $3 "      host.docker.internal"}' >> /etc/hosts

echo "Starting Hipsta on ${PORT}: Workerman + Slim + Eloquent ..."
php /var/www/server.php start
