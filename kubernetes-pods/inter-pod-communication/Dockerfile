FROM nginx:mainline
ENV DEBIAN_FRONTEND noninteractive
RUN mkdir -p /run/php
RUN chmod 777 /run/php
RUN apt-get update && apt-get -y install php-fpm php-mysqlnd 
RUN sed -i 's/listen.owner = www-data/listen.owner = nginx/g' /etc/php/7.0/fpm/pool.d/www.conf
RUN sed -i 's/listen.group = www-data/listen.group = nginx/g' /etc/php/7.0/fpm/pool.d/www.conf
RUN sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/g' /etc/php/7.0/fpm/php.ini
#RUN service php7.0-fpm restart

COPY select.php /usr/share/nginx/html/select.php
COPY index.php /usr/share/nginx/html/index.php
COPY default.conf /etc/nginx/conf.d/default.conf
CMD /etc/init.d/php7.0-fpm restart && nginx -g "daemon off;"
#CMD ["/usr/sbin/php-fpm7.0"]
