FROM php:7.3-cli

MAINTAINER Alexey Lozovyagin <oleksih@gmail.com>

RUN apt-get update
# Install Postgre PDO
RUN apt-get install -y libpq-dev git \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

RUN pecl install inotify \
	&& pecl install swoole \
	&& pecl install igbinary \
	&& pecl install redis \
	&& docker-php-ext-enable inotify \
	&& docker-php-ext-enable swoole \
	&& docker-php-ext-enable igbinary \
	&& docker-php-ext-enable redis

#ENV TZ Europe/Kiev
#RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata && date -s "$(wget -qSO- --max-redirect=0 google.com 2>&1 | grep Date: | cut -d' ' -f5-8)Z"

CMD ["/usr/local/bin/php", "bin/laravels", "start"]
#CMD ["/usr/local/bin/php", "artisan", "daily:sendAll"]

WORKDIR /var/www
ADD . /var/www
