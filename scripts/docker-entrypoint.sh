#!/bin/bash

set -e
if [ ! -d /var/lib/mysql/mysql ]; then
    mysqld --initialize-insecure
fi
#mysqld_safe &
#memcached -u memcache &

phpini=/etc/php/7.1/fpm/php.ini

# php environment
PHP_ALLOW_URL_FOPEN=${PHP_ALLOW_URL_FOPEN:-On}
PHP_DISPLAY_ERRORS=${PHP_DISPLAY_ERRORS:-Off}
PHP_MAX_EXECUTION_TIME=${PHP_MAX_EXECUTION_TIME:-30}
PHP_MAX_INPUT_TIME=${PHP_MAX_INPUT_TIME:-60}
PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-128}
PHP_POST_MAX_SIZE=${PHP_POST_MAX_SIZE:-8}
PHP_SHORT_OPEN_TAG=${PHP_SHORT_OPEN_TAG:-On}
PHP_TIMEZONE=${PHP_TIMEZONE:-Europe/Moscow}
PHP_UPLOAD_MAX_FILEZIZE=${PHP_UPLOAD_MAX_FILEZIZE:-2}

#PHP_TZ=`echo ${PHP_TIMEZONE} |sed  's|\/|\\\/|g'`

# addition modules
PHP_MODULE_MEMCACHED=${PHP_MODULE_MEMCACHED:-Off}
PHP_MODULE_REDIS=${PHP_MODULE_REDIS:-Off}
PHP_MODULE_MONGO=${PHP_MODULE_MONGO:-Off}
PHP_MODULE_OPCACHE=${PHP_MODULE_OPCACHE:-Off}

# set timezone
#echo ${PHP_TIMEZONE} | tee /etc/timezone
#dpkg-reconfigure --frontend noninteractive tzdata

sed -i "s/;daemonize\s*=\s*yes/daemonize = no/g" /etc/php/7.1/fpm/php-fpm.conf

if [ -f /var/www/html/config/nginx/nginx.conf ]; then
    cp /var/www/html/config/nginx/nginx.conf /etc/nginx/nginx.conf
fi

if [ -f /var/www/html/config/nginx/nginx-vhost.conf ]; then
    cp /var/www/html/config/nginx/nginx-vhost.conf /etc/nginx/conf.d/default.conf
fi

if [ -f /var/www/html/config/nginx/nginx-vhost-ssl.conf ]; then
    cp /var/www/html/config/nginx/nginx-vhost-ssl.conf /etc/nginx/conf.d/default-ssl.conf
fi

if [ -f /var/www/html/config/php/pool.conf ]; then
    cp /var/www/html/config/php/pool.conf /etc/php/7.1/fpm/pool.d/www.conf
fi

if [ -f /var/www/html/config/php/php.ini ]; then
    cp /var/www/html/config/php/php.ini /etc/php/7.1/fpm/php.ini
else

    sed -i \
        -e "s/memory_limit = 128M/memory_limit = ${PHP_MEMORY_LIMIT}M/g" \
        -e "s/short_open_tag = Off/short_open_tag = ${PHP_SHORT_OPEN_TAG}/g" \
        -e "s/upload_max_filesize = 2M/upload_max_filesize = ${PHP_UPLOAD_MAX_FILEZIZE}M/g" \
        -e "s/max_execution_time = 30/max_execution_time = ${PHP_MAX_EXECUTION_TIME}/g" \
        -e "s/max_input_time = 60/max_input_time = ${PHP_MAX_INPUT_TIME}/g" \
        -e "s/display_errors = Off/display_errors = ${PHP_DISPLAY_ERRORS}/g" \
        -e "s/post_max_size = 8M/post_max_size = ${PHP_POST_MAX_SIZE}M/g" \
        -e "s/allow_url_fopen = On/allow_url_fopen = ${PHP_ALLOW_URL_FOPEN}/g" \
        -e "s/;date.timezone =/date.timezone = ${PHP_TZ}/g" \
        ${phpini}

fi

if [ ${PHP_MODULE_MEMCACHED} == 'Off' ]; then
    rm -f /etc/php/7.1/fpm/conf.d/20-memcached.ini
fi

if [ ${PHP_MODULE_REDIS} == 'Off' ]; then
    rm -f /etc/php/7.1/fpm/conf.d/20-redis.ini
fi

if [ ${PHP_MODULE_MONGO} == 'Off' ]; then
    rm -f /etc/php/7.1/fpm/conf.d/20-mongodb.ini
fi

if [ ${PHP_MODULE_OPCACHE} == 'Off' ]; then
    rm -f /etc/php/7.1/fpm/conf.d/10-opcache.ini
    rm -f /etc/php/7.1/fpm/conf.d/20-opcache.ini
fi

/usr/bin/supervisord -n -c /etc/supervisord.conf

exec "$@"