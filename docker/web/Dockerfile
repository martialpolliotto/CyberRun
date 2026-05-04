FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        git \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" intl mbstring mysqli pdo_mysql gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY vhost.conf /etc/apache2/sites-available/000-default.conf

RUN { \
        echo "memory_limit = 256M"; \
        echo "upload_max_filesize = 32M"; \
        echo "post_max_size = 32M"; \
        echo "date.timezone = Europe/Paris"; \
    } > /usr/local/etc/php/conf.d/zz-cyberrun.ini

WORKDIR /var/www/html
