# ─────────────────────────────────────────────────────────────────────────────
# Stage 1 — Node: compile frontend assets (Vite + Tailwind)
# ─────────────────────────────────────────────────────────────────────────────
FROM node:22-alpine AS node_builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
# Stage 2 — Composer: install PHP dependencies (production only)
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2.8 AS composer_builder

WORKDIR /app

COPY composer.json composer.lock ./

# Install without scripts first (no artisan available yet)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts

# Copy full source so post-autoload-dump scripts can run
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ─────────────────────────────────────────────────────────────────────────────
# Stage 3 — Runtime: PHP 8.4-FPM + Nginx + Supervisor
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine

LABEL maintainer="GetCash Capital <dev@getcash.co.ke>"

# ── System packages ───────────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    bash \
    netcat-openbsd \ 
    postgresql-dev \
    postgresql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    fontconfig \
    ttf-freefont

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl \
        xml \
        opcache

# ── PHP production ini ────────────────────────────────────────────────────────
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-app.ini"

# ── PHP-FPM config: listen on TCP port 9000 ───────────────────────────────────
# Remove default pool config and create our own
RUN rm -f /usr/local/etc/php-fpm.d/*.conf \
    && { \
    echo '[global]'; \
    echo 'daemonize = no'; \
    echo 'error_log = /proc/self/fd/2'; \
    echo ''; \
    echo '[www]'; \
    echo 'user = www-data'; \
    echo 'group = www-data'; \
    echo 'listen = 127.0.0.1:9000'; \
    echo 'listen.owner = www-data'; \
    echo 'listen.group = www-data'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 5'; \
    echo 'pm.start_servers = 2'; \
    echo 'pm.min_spare_servers = 1'; \
    echo 'pm.max_spare_servers = 3'; \
    echo 'catch_workers_output = yes'; \
    echo 'decorate_workers_output = no'; \
    echo 'clear_env = no'; \
    echo 'access.log = /proc/self/fd/2'; \
    echo 'slowlog = /proc/self/fd/2'; \
} > /usr/local/etc/php-fpm.d/www.conf

# ── Supervisor & Nginx directories ────────────────────────────────────────────
RUN mkdir -p /var/log/supervisor /run/nginx /var/run

# ── Nginx config ──────────────────────────────────────────────────────────────
COPY docker/nginx.conf      /etc/nginx/nginx.conf
COPY docker/default.conf    /etc/nginx/http.d/default.conf

# ── Supervisor config ─────────────────────────────────────────────────────────
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Application ───────────────────────────────────────────────────────────────
WORKDIR /var/www/html

# Copy source (respects .dockerignore)
COPY . .

# Overlay compiled frontend assets from Stage 1
COPY --from=node_builder /app/public/build ./public/build

# Overlay vendor from Stage 2
COPY --from=composer_builder /app/vendor ./vendor

# Ensure storage & cache dirs exist with correct ownership
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/healthz || exit 1

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]