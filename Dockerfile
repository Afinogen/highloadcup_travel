FROM ubuntu:17.10

RUN apt-get update && apt-get -y upgrade \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server mysql-client mysql-common  \
    && rm -rf /var/lib/mysql && mkdir -p /var/lib/mysql /var/run/mysqld \
    	&& chown -R mysql:mysql /var/lib/mysql /var/run/mysqld \
    # ensure that /var/run/mysqld (used for socket and lock files) is writable regardless of the UID our mysqld instance ends up having at runtime
    	&& chmod 777 /var/run/mysqld \
    	&& rm /etc/mysql/my.cnf \
    && 	apt-get install -y curl supervisor nginx memcached \
        php7.1-fpm php7.1-json \
        php7.1-mysql php7.1-opcache \
        php7.1-zip
ADD ./config/mysqld.cnf /etc/mysql/my.cnf
COPY config/www.conf /etc/php/7.1/fpm/pool.d/www.conf
COPY config/nginx.conf 			/etc/nginx/nginx.conf
COPY config/nginx-vhost.conf 		/etc/nginx/conf.d/default.conf
COPY config/opcache.ini 		/etc/php/7.1/mods-available/opcache.ini
COPY config/supervisord.conf 		/etc/supervisord.conf
COPY scripts/ 				/usr/local/bin/
COPY src /var/www/html

#Отладка
#RUN mkdir /tmp/data /tmp/db
#COPY data_full.zip /tmp/data/data.zip
ENV PHP_MODULE_OPCACHE on
ENV PHP_DISPLAY_ERRORS on

RUN chmod 755 /usr/local/bin/docker-entrypoint.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/startup.sh

WORKDIR /var/www/html
#VOLUME /var/www/html

RUN service php7.1-fpm start

EXPOSE 80 3306

CMD ["/usr/local/bin/docker-entrypoint.sh"]
#CMD ["/bin/bash"]
