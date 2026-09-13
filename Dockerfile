# syntax=docker/dockerfile:1
#
# netprovider application image: PHP 8.1 + Apache (mod_php), document root -> site/.
# Pairs with docker-compose.yml, which adds a MySQL 8.0 service.
FROM php:8.1-apache

# --- System libraries: PHP-extension build headers, gettext locales, composer helpers ---
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libonig-dev \
        libzip-dev \
        locales \
        unzip \
        git; \
    rm -rf /var/lib/apt/lists/*

# --- Locales the app selects via setlocale() in Core.php; gettext catalogs break without them ---
RUN set -eux; \
    sed -ri 's/^# *(en_US\.UTF-8)/\1/' /etc/locale.gen; \
    sed -ri 's/^# *(cs_CZ\.UTF-8)/\1/' /etc/locale.gen; \
    locale-gen

# --- PHP extensions: mysqli/mbstring/gettext (app, per composer.json) + zip (code);
#     pdo_mysql is required by Phinx (the migration tool), which uses PDO, not mysqli. ---
RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql mbstring gettext zip

# --- PEAR packages pulled in via require_once (Mail*, Net_*); not Composer-managed ---
RUN set -eux; \
    pear channel-update pear.php.net; \
    pear install --alldeps Mail Mail_Mime Net_POP3 Net_SMTP Net_IPv4

# --- Apache: serve from site/, enable rewrite ---
ENV APACHE_DOCUMENT_ROOT=/var/www/html/site
RUN set -eux; \
    sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf; \
    sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf; \
    a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first for better layer caching. Dev deps are included on
# purpose: Phinx (used by the entrypoint to migrate) lives in composer's require-dev.
COPY composer.json composer.lock ./
RUN set -eux; \
    composer install --no-interaction --no-progress --no-scripts --prefer-dist

# Application source. config/netprovider.ini is excluded via .dockerignore and supplied
# at runtime (compose mounts config/netprovider.docker.ini); dumps/ is excluded too.
COPY . .
RUN chown -R www-data:www-data /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
