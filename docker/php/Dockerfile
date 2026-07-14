FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libzip-dev \
    libyaml-dev \
    libicu-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libgmp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        gmp \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && pecl install redis yaml \
    && docker-php-ext-enable redis yaml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize --no-dev

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-dpanel.ini
COPY docker/entrypoint.sh /usr/local/bin/dpanel-entrypoint.sh
COPY docker/cron-entrypoint.sh /usr/local/bin/dpanel-cron-entrypoint.sh

RUN chmod +x /usr/local/bin/dpanel-entrypoint.sh /usr/local/bin/dpanel-cron-entrypoint.sh \
    && mkdir -p storage/framework/smarty/compile storage/framework/smarty/cache storage/framework/twig/cache storage/logs \
    && chown -R www-data:www-data storage public

ENTRYPOINT ["/usr/local/bin/dpanel-entrypoint.sh"]
CMD ["php-fpm"]
