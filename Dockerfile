# syntax=docker/dockerfile:1

FROM node:24-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build


FROM php:8.2-cli-bookworm AS php-build

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install --yes --no-install-recommends \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

COPY . ./
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction


FROM php:8.2-apache-bookworm AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    PORT=8080

RUN apt-get update \
    && apt-get install --yes --no-install-recommends \
        libicu72 \
        libpq5 \
        libzip4 \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite \
    && a2dissite 000-default \
    && sed -i 's#${APACHE_LOG_DIR}/error.log#/proc/self/fd/2#' /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/tasks-servername.conf \
    && a2enconf tasks-servername

WORKDIR /var/www/html

COPY --from=php-build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --chown=www-data:www-data --from=php-build /var/www/html /var/www/html
COPY --chown=www-data:www-data --from=frontend /app/public/build /var/www/html/public/build
COPY docker/apache/tasks.conf /etc/apache2/sites-available/tasks.conf
COPY docker/entrypoint.sh /usr/local/bin/tasks-entrypoint

RUN a2ensite tasks \
    && chmod 0555 /usr/local/bin/tasks-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["tasks-entrypoint"]
CMD ["apache2-foreground"]
