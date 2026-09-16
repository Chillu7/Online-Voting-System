FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY . /var/www/html/

RUN mkdir -p "/var/www/html/php files/uploads" \
    && chown -R www-data:www-data /var/www/html

VOLUME ["/var/www/html/php files/uploads"]

EXPOSE 80