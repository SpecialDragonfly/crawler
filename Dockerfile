FROM php:7.2-fpm-stretch

RUN apt-get update
RUN apt-get install -y git unzip zlib1g-dev

RUN docker-php-ext-install zip

COPY . /app

WORKDIR /app

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install
RUN php /app/bin/migrations.php 
