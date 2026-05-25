#!/bin/sh
set -e

echo "──────────────────────────────────────────"
echo "  MweelaCash Capital — Deploy Entrypoint"
echo "──────────────────────────────────────────"

# ── 1. Write .env from environment variables ──────────────────────────────────
# Render injects env vars directly; Laravel reads them via $_ENV / getenv().
# We still need APP_KEY to be set — Render should have it as an env var.

# ── 2. Laravel bootstrap ──────────────────────────────────────────────────────
echo "[1/6] Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "[2/6] Caching config, routes, views for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[3/6] Running migrations..."
php artisan migrate --force --no-interaction

echo "[4/6] Seeding database (skipped if already seeded)..."
# Only seed if the roles table is empty — idempotent guard
ROLE_COUNT=$(php artisan tinker --no-interaction --execute="echo \Spatie\Permission\Models\Role::count();" 2>/dev/null | tail -1 | tr -d '[:space:]')
if [ "$ROLE_COUNT" = "0" ] || [ -z "$ROLE_COUNT" ]; then
    echo "     Seeding fresh database..."
    php artisan db:seed --force --no-interaction
else
    echo "     Database already seeded (${ROLE_COUNT} roles found). Skipping."
fi

echo "[5/6] Publishing storage link..."
php artisan storage:link --force 2>/dev/null || true

echo "[6/6] Optimising autoloader..."
composer dump-autoload --optimize --no-dev 2>/dev/null || true

echo "──────────────────────────────────────────"
echo "  Bootstrap complete. Starting services..."
echo "──────────────────────────────────────────"

# ── 3. Start Supervisor (manages Nginx + PHP-FPM + Queue worker) ──────────────
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf