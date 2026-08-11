# =========================================================
# Base PHP (FrankenPHP — replaces nginx + php-fpm with a single
# Caddy-based server; still a standard PHP Alpine image under
# the hood, docker-php-ext-*/pecl work exactly as before)
# =========================================================
FROM dunglas/frankenphp:1-php8.4-alpine

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
# Install APCu (Prometheus client default storage: METRICS_STORAGE=apcu)
# =========================================================
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# =========================================================
# Install PCOV (code coverage driver for PHPUnit)
# =========================================================
RUN pecl install pcov \
    && docker-php-ext-enable pcov

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

# =========================================================
# FrankenPHP (Caddy) config — classic (non-worker) mode: each
# request boots a fresh kernel, same request-isolation model as
# php-fpm had. Worker mode (persistent kernel between requests)
# is available but not enabled by default — see README.
# =========================================================
COPY frankenphp/Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app
EXPOSE 80 443

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
