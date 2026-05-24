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
FROM dunglas/frankenphp:latest

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends curl && rm -rf /var/lib/apt/lists/*

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

# Bedrock serves from web/ — tell FrankenPHP where the document root is
ENV SERVER_NAME=":80"
ENV SERVER_ROOT="/var/www/html/web"

EXPOSE 80

# Health check required by Coolify — curl runs inside the container
HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

# Copy our custom Caddyfile to FrankenPHP's expected location
COPY frankenphp.Caddyfile /etc/frankenphp/Caddyfile
