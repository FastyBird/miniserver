FROM composer:2.4 AS composer

FROM php:8.2-fpm

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
    phar \
    simplexml \
    sockets \
    xml \
    xmlwriter \
    xsl \
    zip

# Install APCu extension
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# Install xDebug extension
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY docker/dev/php/conf/php.ini            /usr/local/etc/php/php.ini

# Set the working directory
WORKDIR /app

EXPOSE 9000
