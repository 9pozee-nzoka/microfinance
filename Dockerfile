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
# Stage 3 — Runtime: PHP 8.3-FPM + Nginx + Supervisor
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine3.20

LABEL maintainer="GetCash Capital <dev@getcash.co.ke>"

# ── System packages ───────────────────────────────────────────────────────────
RUN apk add --no-cache \
    # Web server & process manager
    nginx \
    supervisor \
    # Utilities
    curl \
    bash \
    # PostgreSQL — both dev headers (for pdo_pgsql) AND client binary (for psql in entrypoint)
    postgresql-dev \
    postgresql-client \
    # Image processing (GD)
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    # Zip
    libzip-dev \
    zip \
    unzip \
    # PHP string handling
    oniguruma-dev \
    # Internationalisation
    icu-dev \
    # XML / DomPDF
    libxml2-dev \
    # DomPDF font rendering
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

# ── Supervisor log directory (Alpine doesn't create it automatically) ─────────
RUN mkdir -p /var/log/supervisor /run/nginx

# ── Nginx config ──────────────────────────────────────────────────────────────
COPY docker/nginx.conf      /etc/nginx/nginx.conf
COPY docker/default.conf    /etc/nginx/http.d/default.conf

# ── Supervisor config ─────────────────────────────────────────────────────────
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Application ───────────────────────────────────────────────────────────────
WORKDIR /var/www/html

# Copy source (respects .dockerignore — no node_modules, no .env, no tests)
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

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
