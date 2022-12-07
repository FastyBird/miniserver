FROM fastybird/standard:1.0-traditional as fastybird_application

# Docker file path
ARG SERVICE_DIR=./.docker/prod
# App folder
ARG APP_PATH=/app
# Backend url prefix configuration
ARG BACKEND_PREFIX

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Set container workdir path
WORKDIR ${APP_PATH}

# Expose application data dirs
VOLUME ${APP_PATH}/config
VOLUME ${APP_PATH}/var

ENV APP_ENV=prod

# Copy only specifically what we need
COPY assets assets/
COPY bin bin/
COPY config config/
COPY env env/
COPY migrations migrations/
COPY public public/
COPY resources resources/
COPY composer.* ./
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

# Install backend
RUN set -eux; \
    mkdir -p var/cache var/logs; \
    composer install --prefer-dist --no-autoloader --no-interaction --no-scripts --no-progress --no-dev; \
    composer clear-cache; \
    composer dump-autoload --classmap-authoritative;

RUN chown www-data:www-data ${APP_PATH} -R

# Configure server
COPY ${SERVICE_DIR}/nginx/nginx.conf                /etc/nginx/nginx.conf
COPY ${SERVICE_DIR}/supervisor/supervisord.conf     /etc/supervisor/supervisord.conf
COPY ${SERVICE_DIR}/php/php.ini                     /etc/php/${PHP_VERSION}/fpm/php.ini
COPY ${SERVICE_DIR}/php/php.ini                     /etc/php/${PHP_VERSION}/cli/php.ini
COPY ${SERVICE_DIR}/php/opcache.ini                 /etc/php/${PHP_VERSION}/fpm/conf.d/opcache.ini
COPY ${SERVICE_DIR}/php/opcache.ini                 /etc/php/${PHP_VERSION}/cli/conf.d/opcache.ini

# Modify location based on configuration
RUN sed -i -e "s#__API_PREFIX#${BACKEND_PREFIX}#" "/etc/nginx/nginx.conf"

COPY ${SERVICE_DIR}/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

EXPOSE 9001
