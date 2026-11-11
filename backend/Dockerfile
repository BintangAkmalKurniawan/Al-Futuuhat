# Image backend Laravel. Dipakai dua kali oleh docker-compose:
# layanan `backend` (Apache + PHP) dan `reverb` (perintah artisan) — PRD §13.2.
FROM php:8.4-apache

# Ekstensi yang dibutuhkan Laravel dan PostgreSQL
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip git \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Batas unggahan PHP. Bawaan image hanya 2 MB, padahal video dokumentasi
# program boleh sampai 50 MB per berkas (KF-21, KP-21.3).
RUN printf "upload_max_filesize=55M\npost_max_size=60M\n" > /usr/local/etc/php/conf.d/unggahan.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Salin manifest lebih dulu supaya lapisan dependency tidak dibangun ulang
# setiap kali kode berubah.
COPY composer.json composer.lock ./
# --no-dev: image Docker tidak memuat factory/phpunit (PRD §13.2)
RUN composer install --no-dev --no-autoloader --no-scripts --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && php artisan storage:link

# Document root diarahkan ke folder public/ Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Izin tulis untuk unggahan dan cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 80