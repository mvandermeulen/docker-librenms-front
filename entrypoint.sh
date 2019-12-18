#!/bin/bash

set -e

if [ "$1" = "front" ]; then

    echo -e "set timezone..."
    if [ ! -z $TZ ]; then
        ln -sf /usr/share/zoneinfo/$TZ /etc/localtime
        sed -i -e "s|date\.timezone =.*|date.timezone = \"$TZ\"|" /etc/php/7.2/fpm/php.ini
        sed -i -e "s|date\.timezone =.*|date.timezone = \"$TZ\"|" /etc/php/7.2/cli/php.ini
    fi
    echo -e "start php..."
    service php7.2-fpm start
    echo -e "start nginx..."
    nginx -g "daemon off;"

fi

exec "@"
