FROM ubuntu:bionic

RUN apt-get update -y && \
    apt-get install -y software-properties-common && \
    add-apt-repository universe && \
    apt-get update -y && \
    apt-get upgrade -y

RUN apt-get install -y \
        nginx \
        php7.2-fpm \
        php7.2-curl \
        php7.2-mysql \
        php-rrd

RUN mkdir /var/www/rrd && \
    rm /etc/nginx/sites-enabled/default && \
    ln -sf /usr/share/zoneinfo/UTC /etc/localtime && \
    sed -i -e "s|;date\.timezone =.*|date.timezone = \"UTC\"|" /etc/php/7.2/fpm/php.ini && \
    sed -i -e "s|;date\.timezone =.*|date.timezone = \"UTC\"|" /etc/php/7.2/cli/php.ini

COPY default /etc/nginx/sites-enabled/default

COPY entrypoint.sh /

RUN chmod +x entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]

CMD ["front"]
