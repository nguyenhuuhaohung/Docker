FROM php:8.3.9-apache

# Declare composer version
ENV COMPOSER_VERSION=2.7.7

RUN apt-get update \
    && apt-get install -y \
        curl \
        zip \
        unzip \
        git \
        supervisor \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        libexif-dev \
        gnupg \
        tzdata \
    && docker-php-ext-install \
        bcmath \
        gd \
        opcache \
        sockets \
        zip \
        exif \
        pdo_mysql \
        mysqli \
    && docker-php-ext-enable pdo_mysql mysqli

# Xdebug (cập nhật channel trước khi cài)
RUN apt-get update && apt-get install -y wget ca-certificates \
    && pecl channel-update pecl.php.net \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=$COMPOSER_VERSION

RUN a2enmod rewrite

# Tạo user app
RUN useradd -ms /bin/bash -u 1000 app
