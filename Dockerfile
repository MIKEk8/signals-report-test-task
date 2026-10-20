FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libzip-dev libpng-dev unzip \
 && docker-php-ext-install mysqli zip gd \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { \
      echo 'display_errors=On'; \
      echo 'error_reporting=E_ALL'; \
      echo 'date.timezone=Europe/Moscow'; \
      echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html
