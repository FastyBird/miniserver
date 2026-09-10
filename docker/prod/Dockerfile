#
# FastyBird MiniServer - Production Docker Image
#
# Three stages: ui (Vite/Vue frontend), vendor (PHP dependencies), runtime
# (nginx + php-fpm + supervisord serving the application).
#
# NOTE: config still lives at var/config/ at this phase (Phase 3 moves it to
# config/ and switches FB_CONFIG_DIR/FB_LOGS_DIR/FB_TEMP_DIR and the volume
# below to the /data layout).
#
# Build:
#   docker build -t fastybird/miniserver -f docker/prod/Dockerfile .
#

# ============================================================
# Stage 1: build the frontend
# ============================================================
FROM node:20 AS ui

WORKDIR /app

COPY . .

RUN yarn install --frozen-lockfile --ignore-engines \
    && yarn build

# ============================================================
# Stage 2: install PHP dependencies
# ============================================================
FROM composer:2 AS vendor

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MIRROR_PATH_REPOS=1 \
    COMPOSER_ROOT_VERSION=dev-main

COPY . .

# The composer:2 image's bundled PHP (used only to run the composer binary
# here, never to execute application code) does not match the frozen 8.2
# runtime and lacks several extensions this project requires (bcmath, gd,
# gmp, intl, pcntl, sockets). The runtime stage below installs every
# extension composer.json needs, so it is safe to skip composer's own
# platform check rather than duplicate that extension list into this
# install-only stage.
RUN composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative --ignore-platform-reqs

# ============================================================
# Stage 3: runtime
# ============================================================
FROM php:8.2-fpm AS runtime

ARG APP_PATH=/app

#
# php:8.2-fpm already compiles in curl, mbstring, simplexml, sqlite3, xml
# (and date, iconv, json, openssl, session, sodium) — confirmed via `php -m`
# on a clean php:8.2-fpm container. Asking docker-php-ext-install to rebuild
# an already-built-in extension fails ("Cannot find config.m4"), so only the
# genuinely missing composer.json ext-* requirements are installed below,
# plus pdo_mysql (database driver) and opcache (already active by default,
# kept here only to make the dependency explicit).
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        libicu-dev \
        libgmp-dev \
        libzip-dev \
        libpng-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        bcmath \
        gd \
        gmp \
        intl \
        pcntl \
        pdo_mysql \
        sockets \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR ${APP_PATH}

COPY bin bin/
COPY var/config var/config/
COPY src src/
COPY --from=vendor /app/vendor vendor/
COPY --from=ui /app/public public/

# composer.json/composer.lock and the composer binary itself (borrowed from
# the vendor stage's composer:2 base) are copied in so CI can run
# `composer check-platform-reqs` against *this* runtime image's actual PHP
# and installed extensions -- the same check that caught the extension
# class of defects this phase fixed. The vendor stage above installs with
# --ignore-platform-reqs against its own bundled composer PHP, which is not
# the runtime PHP, so this is the only place the real platform gets
# verified.
COPY composer.json composer.lock ./
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer

RUN chmod +x bin/fb-console bin/fb-console.php bin/fb-supervisor bin/fb-supervisor.php \
    && mkdir -p var/logs var/temp \
    && chown -R www-data:www-data ${APP_PATH}

COPY docker/prod/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/prod/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/prod/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/prod/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/prod/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN chmod +x /usr/local/bin/docker-entrypoint

ENV APP_ENV=prod \
    FB_CONFIG_DIR=/app/var/config \
    FB_LOGS_DIR=/app/var/logs \
    FB_TEMP_DIR=/app/var/temp \
    PHP_DATE_TIMEZONE=UTC

VOLUME ${APP_PATH}/var

EXPOSE 80 8888

# "/" cannot be used here: the Nette application has no router registered
# (see spec section 7.1 / progress.md Ruling 8), so it 404s on every
# request and `curl -f` would exit non-zero forever, leaving the container
# permanently "unhealthy". /favicon.ico is served directly by nginx from
# public/ and genuinely returns 200.
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/favicon.ico || exit 1

ENTRYPOINT ["docker-entrypoint"]
