FROM php:8.2-fpm

RUN apt-get update \ 
    && apt-get install -y --no-install-recommends git unzip \ 
    && docker-php-ext-install pdo_mysql \ 
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

COPY . .

CMD ["php-fpm"]
