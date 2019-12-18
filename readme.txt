docker run \
    --name librenms_front \
    --hostname librenms_front \
    --network librenms_librenms \
    -p 8200:80 \
    -e TZ=Europe/Paris \
    -v /home/pirate/docker/volumes/front/nginx/html:/var/www/html \
    -v /home/pirate/docker/volumes/librenms/rrd:/var/www/rrd \
    -d front:0.1-bionic
