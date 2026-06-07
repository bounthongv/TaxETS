# =====================================================================
# Tax-ETS — PHP 8.2 + Apache Docker Image
# ---------------------------------------------------------------------
# This Dockerfile packages the Tax Expenditure Estimation System
# (Tax-ETS) so it can run anywhere Docker is installed, without
# needing XAMPP, WAMP, or a manual PHP/MySQL setup.
#
# Layered approach (smaller final image, better caching):
#   1. Base image: php:8.2-apache  (PHP + Apache, official)
#   2. System deps: gd, zip, intl, mysqli, pdo_mysql (for PhpSpreadsheet)
#   3. Composer: copy binary from official composer image
#   4. PHP code:  copy our app into /var/www/html
#   5. Composer:  install PHP packages (PhpSpreadsheet)
#   6. Writable:  create data/logs + session tmp + set ownership
# =====================================================================

# ---- 1. Base image ----
FROM php:8.2-apache

# Re-declare inside the build (ARG is build-time only, good hygiene)
ARG DEBIAN_FRONTEND=noninteractive

# ---- 2. System dependencies + PHP extensions ----
# Why each one:
#   - libicu-dev / libzip-dev / libpng-dev / libjpeg-dev: build deps for PHP extensions
#   - unzip / git: needed by Composer and many tools
#   - default-mysql-client: gives us `mysql` CLI for debugging inside container
#   - pdo_mysql: required by PHP to talk to MySQL (the app uses PDO)
#   - mysqli: another MySQL driver (kept for compatibility, harmless)
#   - gd: PhpSpreadsheet image/chart support
#   - zip:  PhpSpreadsheet + Composer
#   - intl: number/currency formatting
#   - opcache: preloaded extension, big perf win (configured below)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        unzip \
        git \
        default-mysql-client \
        nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        intl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ---- 3. Composer (copy from official image) ----
# We use the multi-stage copy pattern so we always get an up-to-date
# Composer without needing to install it via apt (which lags behind).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---- 4. Apache configuration ----
# Enable mod_rewrite (in case we add clean URLs later) and .htaccess
# support. DocumentRoot defaults to /var/www/html which is where we
# copy our app code.
RUN a2enmod rewrite headers

# Custom PHP config — uploads up to 50 MB, opcache tuned
RUN { \
        echo 'upload_max_filesize = 50M'; \
        echo 'post_max_size = 50M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 300'; \
        echo 'date.timezone = Asia/Vientiane'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.memory_consumption = 128'; \
        echo 'opcache.max_accelerated_files = 10000'; \
        echo 'opcache.revalidate_freq = 2'; \
    } > /usr/local/etc/php/conf.d/tax-ets.ini

# ---- 5. Application code ----
WORKDIR /var/www/html

# Copy composer files first to leverage Docker layer cache:
# if only PHP code changes, we don't re-run `composer install`.
COPY composer.json ./
COPY composer.lock* ./

# Install dependencies WITHOUT dev packages, optimized for prod
# `--no-scripts` skips post-install scripts (none here, but safe)
# `--no-interaction` so it never asks questions
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts || \
    (echo "composer.lock not found, running install without lock" && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts)

# Now copy the rest of the application code
COPY . .

# ---- 6. Writable directories ----
# The app writes import log files into data/logs/
# PHP sessions are stored in /tmp by default (works inside container)
RUN mkdir -p data/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 data/logs

# ---- 7. Health check ----
# Docker will run this every 30s. If the homepage doesn't return
# 200 OK, the container is marked unhealthy.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://localhost/login.php > /dev/null || exit 1

# ---- 8. Expose port 80 and start Apache ----
EXPOSE 80
CMD ["apache2-foreground"]
