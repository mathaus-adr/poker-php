FROM php:8.3-fpm

FROM php:8.3-fpm-alpine

ARG NODE_VERSION=20


RUN apk update && apk upgrade --no-cache
RUN apk add  linux-headers

RUN apk add --no-cache openssl bash mysql-client
RUN docker-php-ext-install pdo pdo_mysql

RUN apk add --no-cache $PHPIZE_DEPS \
	&& docker-php-ext-install calendar

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN apk add --update --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        curl \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j"$(getconf _NPROCESSORS_ONLN)" gd \
	&& docker-php-ext-enable gd \
    && docker-php-ext-install bcmath sockets

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=2.7.7 && chmod +x /usr/local/bin/composer

RUN apk add --no-cache nodejs npm autoconf

RUN wget https://github.com/emcrisostomo/fswatch/releases/download/1.17.1/fswatch-1.17.1.tar.gz \
&& tar -xzvf fswatch-1.17.1.tar.gz && cd fswatch-1.17.1 && ./configure && make && make install && rm -rf /fswatch-1.17.1


WORKDIR /var/www

COPY ./php.ini.development "$PHP_INI_DIR/php.ini"

CMD ["php-fpm"]
