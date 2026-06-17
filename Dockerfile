FROM php:8.4-fpm

# OS-level packages needed before we can install PHP extensions or Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
# pdo_mysql -> Eloquent / MySQL connection
# zip       -> maatwebsite/excel reads/writes .xlsx as zip archives
# gd        -> maatwebsite/excel image handling in exports
# bcmath    -> required by laravel/framework
# pcntl     -> required by laravel/horizon to manage worker subprocesses
RUN docker-php-ext-install pdo_mysql zip gd bcmath pcntl

# redis extension is not bundled with PHP core - installed via PECL
# REDIS_CLIENT=phpredis in .env expects this extension to exist
RUN pecl install redis && docker-php-ext-enable redis

# Composer binary copied from the official Composer image
# (this is just copying one file, not a multi-stage build of our own app)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# App code copied in last so earlier layers (extensions, composer) cache
# independently of source changes
COPY . .

# Container runs as root but .git was created by host user - git refuses
# to trust it by default as a safety check. This tells git the directory is fine.
RUN git config --global --add safe.directory /var/www/html

RUN composer install --no-interaction --no-progress --prefer-dist

# storage/ and bootstrap/cache/ must be writable by the web server process
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# The volume mount in docker-compose.yml overwrites this chown with host
# ownership at container start. This entrypoint re-applies it every boot.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]