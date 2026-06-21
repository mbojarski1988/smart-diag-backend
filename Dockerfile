FROM php:8.3-cli-alpine AS app

WORKDIR /app

RUN apk add --no-cache bash git unzip icu-dev libzip-dev postgresql-dev \
    && docker-php-ext-install intl opcache pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
