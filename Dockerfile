FROM php:7.3-cli

MAINTAINER Alexey Lozovyagin <oleksih@gmail.com>

RUN apt-get update
# Install Postgre PDO
RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

RUN pecl install inotify \
	&& pecl install swoole \
	&& pecl install redis \
	&& docker-php-ext-enable inotify \
	&& docker-php-ext-enable swoole \
	&& docker-php-ext-enable redis

CMD ["/usr/local/bin/php", "bin/laravels", "start"]

WORKDIR /var/www
ADD . /var/www
