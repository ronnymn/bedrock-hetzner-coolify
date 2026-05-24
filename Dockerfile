# ─── Stage 1: Composer dependencies ──────────────────────────────────────────
FROM composer:2 AS deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs \
    --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev

# ─── Stage 2: FrankenPHP production image ────────────────────────────────────
# Official FrankenPHP image — PHP + Caddy in one
# https://frankenphp.dev
FROM dunglas/frankenphp:latest

WORKDIR /var/www/html

# Install extra PHP extensions needed by Bedrock + s3-uploads
RUN install-php-extensions \
    pdo_mysql \
    mysqli \
    redis \
    gd \
    intl \
    exif \
    zip \
    opcache

# PHP runtime settings
RUN { \
    echo 'upload_max_filesize=64M'; \
    echo 'post_max_size=64M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=60'; \
} > /usr/local/etc/php/conf.d/wordpress.ini

# OPcache — aggressive in production, worker mode benefits from this
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Copy Bedrock app from deps stage
COPY --from=deps --chown=www-data:www-data /app /var/www/html

# Tell FrankenPHP that Bedrock's document root is web/
ENV FRANKENPHP_CONFIG="worker ./web/index.php"
ENV SERVER_NAME=":80"

EXPOSE 80

# Override the default Caddyfile with our Bedrock-specific one
COPY Caddyfile /etc/caddy/Caddyfile
