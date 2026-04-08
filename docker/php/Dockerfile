# =========================================================
# Base PHP
# =========================================================
FROM php:8.4-fpm-alpine

# =========================================================
# Install necessary deps
# =========================================================
RUN apk add --no-cache \
    postgresql-dev \
    rabbitmq-c-dev \
    linux-headers \
    autoconf \
    g++ \
    make \
    bash \
    git \
    unzip \
    libtool \
    pkgconfig \
    curl \
    && docker-php-ext-install pdo pdo_pgsql sockets opcache

# =========================================================
# Install AMQP
# =========================================================
RUN pecl install amqp \
    && docker-php-ext-enable amqp

# =========================================================
# Install Redis
# =========================================================
RUN pecl install redis \
    && docker-php-ext-enable redis

# =========================================================
# Cleanup unecessary files
# =========================================================
RUN apk del autoconf g++ make linux-headers \
    && rm -rf /tmp/pear /var/cache/apk/*

# =========================================================
# Composer
# =========================================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================================================
# PHP Config
# =========================================================
COPY php/php.ini     $PHP_INI_DIR/conf.d/app.ini
COPY php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

WORKDIR /app
EXPOSE 9000
