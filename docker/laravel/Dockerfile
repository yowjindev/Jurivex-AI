FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    curl \
    git \
    unzip \
    linux-headers \
    autoconf \
    g++ \
    make \
    tesseract-ocr \
    tesseract-ocr-data-eng \
    poppler-utils \
    ghostscript

RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    gd \
    opcache \
    pcntl \
    sockets

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 9000

CMD ["php-fpm"]
