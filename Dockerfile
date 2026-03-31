# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:php8.4-bookworm AS vendor

WORKDIR /app

RUN apt-get update && apt-get install -y git unzip && install-php-extensions pdo_mysql mbstring xml curl dom zip && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

COPY . .

RUN composer dump-autoload --optimize --no-dev



FROM node:20-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN npm run build



FROM dunglas/frankenphp:php8.4-bookworm

WORKDIR /app

RUN install-php-extensions pdo_mysql mbstring xml curl dom zip

COPY . /app
COPY --from=vendor /app/vendor /app/vendor
COPY --from=frontend /app/public/build /app/public/build

RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache && chown -R www-data:www-data /app/storage /app/bootstrap/cache && chmod -R ug+rwX /app/storage /app/bootstrap/cache

ENV APP_ENV=production
ENV SERVER_NAME=:8080
ENV CADDY_GLOBAL_OPTIONS="auto_https off"

EXPOSE 8080

#CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
CMD ["sh", "-lc", "php artisan config:clear && frankenphp run"]