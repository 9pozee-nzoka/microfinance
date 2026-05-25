# ─────────────────────────────────────────────────────────────────────────────
# Stage 1 — Node: compile frontend assets
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
# Stage 2 — PHP: install Composer dependencies (no dev)
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2.8 AS composer_builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --prefer-dist \
    --no-scripts

# Copy full app so artisan exists for post-autoload scripts
COPY . .
RUN composer dump-autoload --optimize

# ─────────────────────────────────────────────────────────────────────────────
# Stage 3 — Runtime: PHP 8.3-FPM + Nginx
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine3.20

LABEL maintainer="GetCash Capital <dev@getcash.co.ke>"

# ── System dependencies ───────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    libxml2-dev \
    fontconfig \
    ttf-freefont

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
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

# ── PHP production config ─────────────────────────────────────────────────────
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-app.ini"

# ── App directory ─────────────────────────────────────────────────────────────
WORKDIR /var/www/html

# Copy application source
COPY . .

# Copy compiled assets from node stage
COPY --from=node_builder /app/public/build ./public/build

# Copy vendor from composer stage
COPY --from=composer_builder /app/vendor ./vendor

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ── Nginx config ──────────────────────────────────────────────────────────────
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/http.d/default.conf

# ── Supervisor config ─────────────────────────────────────────────────────────
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]