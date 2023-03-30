# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/compose/compose-file/#target

ARG PHP_VERSION=8.1
ARG NODE_VERSION=16
ARG NGINX_VERSION=1.23
ARG COMPOSER_VERSION=2.4
ARG PHP_EXTENSION_INSTALLER_VERSION=latest

FROM mlocati/php-extension-installer:${PHP_EXTENSION_INSTALLER_VERSION} AS php_extension_installer

FROM php:${PHP_VERSION}-fpm as fastybird_php

# Docker file path
ARG SERVICE_DIR=./.docker/dev
# App folder
ARG APP_PATH=/srv/fastybird

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install basic tools
RUN apt-get update -yqq && apt-get install -yqq \
    software-properties-common \
    curl \
    make \
    git \
    supervisor \
    unzip \
    zip \
    bzip2 \
    python3 \
    zlib1g-dev \
    libicu-dev \
    libgmp-dev \
    g++ \
    wget \
    iputils-ping

COPY --from=php_extension_installer /usr/bin/install-php-extensions /usr/local/bin/

# Default PHP image extensions:
# ctype curl date dom fileinfo filter ftp hash iconv json libxml mbstring mysqlnd openssl pcre PDO pdo_sqlite Phar
# posix readline Reflection session SimpleXML sodium SPL sqlite3 standard tokenizer xml xmlreader xmlwriter zlib
RUN install-php-extensions \
    apcu \
    bcmath \
    calendar \
    exif \
    gd \
    gmp \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    sockets \
    xsl \
    yaml \
    zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename composer

# Cleanup
RUN apt-get remove --purge -y software-properties-common curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* /usr/share/man/*

WORKDIR ${APP_PATH}

# Expose application data dirs
VOLUME ${APP_PATH}/config
VOLUME ${APP_PATH}/var

ENV APP_ENV=dev

# Copy only specifically what we need
COPY assets assets/
COPY bin bin/
COPY config config/
COPY env env/
COPY migrations migrations/
COPY public public/
COPY resources resources/
COPY composer.* ./

# Configure PHP
COPY ${SERVICE_DIR}/php/php.ini         $PHP_INI_DIR/php.ini
COPY ${SERVICE_DIR}/php/opcache.ini     $PHP_INI_DIR/conf.d/opcache.ini

# Install backend
RUN set -eux; \
    mkdir -p var/cache var/logs;

COPY ${SERVICE_DIR}/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

CMD ["php-fpm"]

FROM node:${NODE_VERSION} as fastybird_node

# Docker file path
ARG SERVICE_DIR=./.docker/dev
# App folder
ARG APP_PATH=/srv/fastybird

# Install basic tools
RUN apt-get update && apt-get install -y \
    software-properties-common \
    curl \
    make \
    netcat \
    git \
    unzip \
    zip \
    bzip2

# Cleanup
RUN apt-get remove --purge -y software-properties-common curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* /usr/share/man/*

WORKDIR ${APP_PATH}

# Install app
COPY assets assets/
COPY env env/
COPY .browserslistrc ./
COPY .eslintignore ./
COPY .eslintrc ./
COPY .prettierrc ./
COPY babel.config.js ./
COPY index.html ./
COPY package.json ./
COPY tsconfig.json ./
COPY vite.config.ts ./
COPY yarn.lock ./

# Install & build user interface
RUN yarn cache clean \
    && yarn install --network-timeout 1000000 \
    && yarn build:prod

COPY ${SERVICE_DIR}/node/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

FROM nginx:${NGINX_VERSION} as fastybird_nginx

# Docker file path
ARG SERVICE_DIR=./.docker/dev
# App folder
ARG APP_PATH=/srv/fastybird
# Backend url prefix configuration
ARG BACKEND_PREFIX

WORKDIR ${APP_PATH}

COPY ${SERVICE_DIR}/nginx/nginx.conf /etc/nginx/nginx.conf

# Modify location based on configuration
RUN sed -i -e "s#__API_PREFIX#${BACKEND_PREFIX}#" "/etc/nginx/nginx.conf"

COPY --from=fastybird_php   /srv/fastybird/public /srv/fastybird/public/
COPY --from=fastybird_node  /srv/fastybird/public/dist /srv/fastybird/public/dist/

FROM fastybird_php AS fastybird_migrations

# Docker file path
ARG SERVICE_DIR=./.docker/dev

COPY ${SERVICE_DIR}/migrations/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

FROM fastybird_php AS fastybird_worker

# Docker file path
ARG SERVICE_DIR=./.docker/dev

# Install supervisor
RUN apt-get update -yqq && apt-get install -yqq supervisor

COPY ${SERVICE_DIR}/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

# Supervisord run command
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]

EXPOSE 9001
