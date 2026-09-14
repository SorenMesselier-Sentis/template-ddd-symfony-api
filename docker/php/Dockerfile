# =========================================================
# Base PHP (FrankenPHP — replaces nginx + php-fpm with a single
# Caddy-based server; still a standard PHP Alpine image under
# the hood, docker-php-ext-*/pecl work exactly as before)
# =========================================================
FROM dunglas/frankenphp:1-php8.4-alpine AS base

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
# Composer
# =========================================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================================================
# PHP Config
# =========================================================
COPY docker/php/php.ini     $PHP_INI_DIR/conf.d/app.ini
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# =========================================================
# FrankenPHP (Caddy) config — classic (non-worker) mode: each
# request boots a fresh kernel, same request-isolation model as
# php-fpm had. Worker mode (persistent kernel between requests)
# is available but not enabled by default — see README.
# =========================================================
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app
EXPOSE 80 443

# =========================================================
# dev — local/CI image. Source is bind-mounted by docker/compose.yaml
# (target: dev), not copied; this stage only adds dev-only tooling on
# top of "base". This is the default target if none is specified, to
# match the Dockerfile's behavior before this stage split.
# =========================================================
FROM base AS dev

# PCOV (code coverage driver for PHPUnit) — dev/CI only, never shipped
# in the prod image below.
RUN pecl install pcov \
    && docker-php-ext-enable pcov

RUN apk del autoconf g++ make linux-headers \
    && rm -rf /tmp/pear /var/cache/apk/*

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# =========================================================
# prod — self-contained deployable image: app source is COPYed in
# (respecting .dockerignore — notably vendor/, .env.local and
# config/jwt/*.pem are never baked in; JWT keys must be injected at
# deploy time, e.g. a mounted secret) and dependencies are installed
# without dev packages. Built and pushed by
# .github/workflows/ci.yml's "publish-image" job — see README
# "Building & publishing the production image".
# =========================================================
FROM base AS prod

# Baked in at build time (not left to be injected per deploy target) so the
# image is self-identifying for Sentry release tracking — see sentry.yaml.
ARG APP_VERSION=unknown
ENV APP_VERSION=$APP_VERSION

RUN apk del autoconf g++ make linux-headers \
    && rm -rf /tmp/pear /var/cache/apk/*

# Unprivileged runtime user. FrankenPHP normally needs root to bind :80;
# instead we grant the "app" user just the cap_net_bind_service capability
# on the frankenphp binary itself (libcap is only needed to set it, so it's
# removed again right after) and own every dir the process writes to —
# Caddy's own state (XDG_DATA_HOME/XDG_CONFIG_HOME, baked into the base
# image as /data/caddy, /config/caddy) plus /app, where Symfony creates
# var/cache and var/log on first boot (excluded from the image by
# .dockerignore, so nothing to chown there yet).
RUN addgroup -g 1000 app \
    && adduser -D -u 1000 -G app app \
    && apk add --no-cache libcap \
    && setcap cap_net_bind_service=+ep /usr/local/bin/frankenphp \
    && apk del libcap \
    && mkdir -p /data/caddy /config/caddy \
    && chown -R app:app /app /data/caddy /config/caddy

COPY --chown=app:app . .

USER app

RUN composer install --no-dev --optimize-autoloader --classmap-authoritative --no-scripts --no-interaction

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
