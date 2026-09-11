FROM composer:2.4 AS composer

FROM php:8.4-fpm

RUN apt-get update

RUN apt-get install -y  \
    bzip2 \
    python3 \
    g++ \
    make \
    zlib1g-dev \
    libicu-dev \
    libgmp-dev \
    libzip-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    wget \
    openssl  \
    libssl-dev \
    libpq-dev \
    git \
    cmake \
    autoconf \
    libtool \
    pkg-config \
    build-essential \
    libcurl4 \
    libcurl4-openssl-dev \
    libxslt1-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
# Note: `phar` is deliberately NOT in this list. It is compiled into php:8.4-fpm by
# default, and asking docker-php-ext-install to build it fails with
# "config.status: error: cannot find input file: '/phar.1.in'". Nine other entries
# below (ctype, curl, dom, fileinfo, mbstring, pdo, simplexml, xml, xmlwriter) are
# also already built in and are redundant, but harmless -- verified with `php -m`
# against php:8.4-fpm.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
    bcmath \
    calendar \
    ctype \
    curl \
    dom \
    exif \
    fileinfo \
    gd \
    gmp \
    intl \
    mbstring \
    mysqli \
    opcache \
    pcntl \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    simplexml \
    sockets \
    xml \
    xmlwriter \
    xsl \
    zip

# Refresh the PECL channel before installing from it. Without this, PHP 8.4 builds
# fail with `Package "xdebug" Version "3.5.3" does not have REST dependency
# information available` -- stale channel metadata, not an incompatibility.
RUN pecl channel-update pecl.php.net

# Install APCu extension
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Install xDebug extension
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install pcov extension
#
# The Makefile's coverage and mutation targets both run
# `php -d pcov.enabled=1 -d pcov.directory=./src`, but pcov was installed
# nowhere, and PRE_PHP sets XDEBUG_MODE=off for every target, so the only other
# driver was disabled too. Every coverage run therefore had no driver at all.
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY docker/dev/php/conf/php.ini            /usr/local/etc/php/php.ini

# Set the working directory
WORKDIR /app

EXPOSE 9000
