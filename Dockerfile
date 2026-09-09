# syntax=docker/dockerfile:1
FROM php:8.4-fpm-bookworm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx gettext-base libonig-dev libzip-dev libxml2-dev unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring dom zip opcache \
    && rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

WORKDIR /var/www/html

FROM php-base AS build
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && composer check-platform-reqs --no-dev

FROM php-base AS production
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    PORT=8080
COPY --from=build /var/www/html /var/www/html
COPY docker/railway/php.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/railway/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf
RUN chmod +x docker/railway/start.sh \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 8080
STOPSIGNAL SIGTERM
ENTRYPOINT ["/var/www/html/docker/railway/start.sh"]
