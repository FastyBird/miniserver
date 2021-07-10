# Define PHP version
ARG TARGET_PHP_VERSION=7.4
ARG TARGET_NODE_VERSION=12

# Define NodeJS docker image
FROM node:${TARGET_NODE_VERSION} AS builder

MAINTAINER Adam Kadlec <adam.kadlec@fastybird.com>

################################
# CONTAINER REQUIRED ARGUMENTS #
################################

# App instalation folder
ARG APP_CODE_PATH=/usr/src/app

RUN apt-get update -yqq \
 && apt-get install -yqq \
 build-essential \
 autoconf \
 curl \
 git \
 wget \
;

RUN git clone https://github.com/FastyBird/miniserver-manager.git ${APP_CODE_PATH}

RUN cd ${APP_CODE_PATH} \
 && git submodule init \
 && git submodule update

# Install web app
RUN cd ${APP_CODE_PATH}/web-ui \
 && yarn cache clean \
 && yarn install --network-timeout 1000000 \
 && yarn generate \
;

# Define PHP docker image
FROM php:${TARGET_PHP_VERSION}-cli

MAINTAINER Adam Kadlec <adam.kadlec@fastybird.com>

################################
# CONTAINER REQUIRED ARGUMENTS #
################################

# App instalation folder
ARG APP_CODE_PATH=/usr/src/app
# Container default timezone
ARG APP_TZ=UTC

###########################
# CONTAINER CONFIGURATION #
###########################

# Set server timezone
RUN ln -snf /usr/share/zoneinfo/${APP_TZ} /etc/localtime && echo ${APP_TZ} > /etc/timezone

RUN apt-get update -yqq \
 && apt-get install -yqq \
 build-essential \
 autoconf \
 curl \
 dnsutils \
 git \
 wget \
 nano \
 unzip \
 zip \
 bzip2 \
 zlib1g-dev \
 libicu-dev \
 g++ \
;

RUN docker-php-ext-install \
 mysqli \
 pdo \
 pdo_mysql \
 intl \
 ctype \
 json \
 bcmath \
;

###########################
# SUPERVISOR INSTALLATION #
###########################

# Install supervisor
RUN apt-get update -yqq && apt-get install -yqq supervisor

COPY ./resources/supervisor/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

######################################
# MINISERVER SERVER APP INSTALLATION #
######################################

RUN mkdir ${APP_CODE_PATH}

ADD ./config ${APP_CODE_PATH}/config
ADD ./patches ${APP_CODE_PATH}/patches
ADD ./resources ${APP_CODE_PATH}/resources
ADD ./src ${APP_CODE_PATH}/src
ADD ./var ${APP_CODE_PATH}/var
COPY ./composer.json ${APP_CODE_PATH}/

COPY --from=builder ${APP_CODE_PATH}/web-ui/dist ${APP_CODE_PATH}/public

# Install composer installer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Checkout & install server app
RUN cd ${APP_CODE_PATH} \
 && composer clearcache \
 && composer install --no-dev \
;

#####################################
# FINISHING CONTAINER CONFIGURATION #
#####################################

WORKDIR "${APP_CODE_PATH}"

####################
# SERVICES WAITING #
####################

ENV WAIT_VERSION=2.7.3

## Add the wait script to the image
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/${WAIT_VERSION}/wait /wait
RUN chmod +x /wait

################
# MAIN COMMAND #
################

# Supervisord run command
CMD /wait && /usr/bin/supervisord
