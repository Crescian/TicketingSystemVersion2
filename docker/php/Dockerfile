FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev zip unzip git curl \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo pdo_pgsql gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html

COPY docker/php/pool-tuning.conf /usr/local/etc/php-fpm.d/zz-pool-tuning.conf
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

# Verifies the app can actually reach Postgres, not just that php-fpm's
# process is alive — catches "hung but not crashed" states (exhausted DB
# pool, deadlocked worker) that `restart: unless-stopped` can't see on its
# own, since that policy only reacts to the process exiting.
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD php docker/php/healthcheck.php > /dev/null 2>&1 || exit 1

EXPOSE 9000
CMD ["php-fpm"]