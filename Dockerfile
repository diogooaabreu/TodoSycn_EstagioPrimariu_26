FROM dunglas/frankenphp:php8.4-bookworm

WORKDIR /app

ENV APP_ENV=production
ENV SERVER_NAME=:8080

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    xml \
    ctype \
    curl \
    dom \
    fileinfo \
    filter \
    hash \
    openssl \
    pcntl \
    session \
    tokenizer

COPY . /app
COPY --from=vendor /app/vendor /app/vendor
COPY --from=frontend /app/public/build /app/public/build

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R ug+rwX /app/storage /app/bootstrap/cache

EXPOSE 8080

CMD ["sh", "-lc", "php artisan config:clear && php artisan migrate --force && frankenphp run"]