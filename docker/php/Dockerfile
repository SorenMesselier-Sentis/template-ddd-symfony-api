FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    rabbitmq-c-dev \
    linux-headers \
    autoconf \
    g++ \
    make

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    sockets

RUN pecl install amqp \
    && docker-php-ext-enable amqp

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN docker-php-ext-install opcache

RUN apk del \
    autoconf \
    g++ \
    make \
    linux-headers \
    && rm -rf /tmp/pear /var/cache/apk/*

COPY php/php.ini     $PHP_INI_DIR/conf.d/app.ini
COPY php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

EXPOSE 9000
