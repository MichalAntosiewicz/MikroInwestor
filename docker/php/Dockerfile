FROM php:8.3-fpm-alpine

# Pakiety potrzebne do działania (zostają w kontenerze)
ENV RUN_DEPS \
    zlib \
    libzip \
    libpng \
    libjpeg-turbo \
    postgresql-libs \
    curl \
    libcurl

# Pakiety potrzebne tylko do kompilacji (zostaną usunięte)
ENV BUILD_DEPS \
    zlib-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    postgresql-dev \
    curl-dev

ENV PHP_EXTENSIONS \
    opcache \
    zip \
    gd \
    bcmath \
    pgsql \
    pdo_pgsql \
    curl

# 1. Update i instalacja zależności systemowych
RUN apk update && apk add --no-cache $RUN_DEPS

# 2. Instalacja zależności do kompilacji, instalacja rozszerzeń i sprzątanie
RUN apk add --no-cache --virtual .build-deps $BUILD_DEPS \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j "$(nproc)" $PHP_EXTENSIONS \
    && apk del .build-deps

RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

WORKDIR /app
COPY . /app