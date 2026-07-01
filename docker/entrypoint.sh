#!/bin/sh
set -e

echo "══════════════════════════════════════════"
echo "  Mweela Cash Capital — Deploy Entrypoint"
echo "══════════════════════════════════════════"

# ── 0. Bootstrap .env ─────────────────────────────────────────────────────────
echo "[0/8] Bootstrapping environment..."
sleep 2

# Copy .env.production → .env if .env doesn't exist in the container
if [ -f /var/www/html/.env.production ] && [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.production /var/www/html/.env
    echo "     ✓ Copied .env.production → .env"
elif [ -f /var/www/html/.env ]; then
    echo "     ✓ .env already present"
else
    echo "     ⚠ WARNING: No .env file found — app may fail to start!"
fi

# ── 1. Clear file-based caches ────────────────────────────────────────────────
echo "[1/8] Clearing file caches..."
php artisan config:clear --no-interaction 2>/dev/null || true
php artisan route:clear  --no-interaction 2>/dev/null || true
php artisan view:clear   --no-interaction 2>/dev/null || true

# ── 2. Run migrations (non-destructive — never drops data) ───────────────────
echo "[2/8] Running migrations..."
php artisan migrate --force --no-interaction
echo "     ✓ Migrations complete."

# ── Seed only on first deploy (when no users exist) ──────────────────────────
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 | tr -d '[:space:]')
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "     ► First deploy — seeding initial roles, branch and admin..."
    php artisan db:seed --force --no-interaction
    echo "     ✓ Seed complete."
else
    echo "     ✓ Data exists (${USER_COUNT} users). Skipping seed."
fi

# ── 3. Flush DB-backed cache ──────────────────────────────────────────────────
echo "[3/8] Flushing application cache..."
php artisan cache:clear --no-interaction 2>/dev/null || true

# ── 4. Build production caches ────────────────────────────────────────────────
echo "[4/8] Caching config, routes and views..."
php artisan config:cache --no-interaction
php artisan route:cache  --no-interaction
php artisan view:cache   --no-interaction

# ── 5. Storage link ───────────────────────────────────────────────────────────
echo "[5/8] Linking storage..."
php artisan storage:link --force 2>/dev/null || true

# ── 6. Permissions ────────────────────────────────────────────────────────────
echo "[6/8] Setting permissions..."
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# ── 7. Verify PHP-FPM ────────────────────────────────────────────────────────
echo "[7/8] Verifying PHP-FPM..."
for i in 1 2 3 4 5; do
    if nc -z 127.0.0.1 9000 2>/dev/null; then
        echo "     ✓ PHP-FPM is listening on 127.0.0.1:9000"
        break
    fi
    echo "     Waiting for PHP-FPM (attempt $i/5)..."
    sleep 1
done

echo "══════════════════════════════════════════"
echo "  Bootstrap complete. Starting services..."
echo "══════════════════════════════════════════"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
