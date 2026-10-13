# syntax=docker/dockerfile:1.7
# FitFusion Laravel image for Render. The original Node project is outside this image.
FROM composer:2 AS vendor
WORKDIR /build
COPY laravel/composer.json laravel/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.2-fpm-bookworm AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=warning \
    PORT=10000

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx libonig-dev libpq-dev \
    && docker-php-ext-install -j"$(nproc)" mbstring opcache pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /etc/nginx/templates

COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-fitfusion.ini 
COPY docker/ca.pem /etc/ssl/certs/aiven-ca.pem
WORKDIR /var/www/html
COPY laravel/ ./
COPY --from=vendor /build/vendor ./vendor
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/entrypoint.sh /usr/local/bin/fitfusion-entrypoint

RUN chmod 0755 /usr/local/bin/fitfusion-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r '$h=@get_headers("http://127.0.0.1:".(getenv("PORT")?:10000)."/up"); exit($h && str_contains($h[0]," 200 ") ? 0 : 1);'

ENTRYPOINT ["fitfusion-entrypoint"]