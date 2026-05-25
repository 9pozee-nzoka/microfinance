#!/bin/sh
set -e

echo "══════════════════════════════════════════"
echo "  GetCash Capital — Deploy Entrypoint"
echo "══════════════════════════════════════════"

# ── 1. Clear FILE-based caches only (no DB touch) ────────────────────────────
echo "[1/7] Clearing file caches..."
php artisan config:clear --no-interaction 2>/dev/null || true
php artisan route:clear  --no-interaction 2>/dev/null || true
php artisan view:clear   --no-interaction 2>/dev/null || true

# ── 2. Run migrations ─────────────────────────────────────────────────────────
echo "[2/7] Running migrations..."
php artisan migrate --force --no-interaction

# ── 3. Flush DB-backed cache (table now exists) ───────────────────────────────
echo "[3/7] Flushing application cache..."
php artisan cache:clear --no-interaction 2>/dev/null || true

# ── 4. Build production caches ────────────────────────────────────────────────
echo "[4/7] Caching config, routes and views..."
php artisan config:cache --no-interaction
php artisan route:cache  --no-interaction
php artisan view:cache   --no-interaction

# ── 5. Seed database ──────────────────────────────────────────────────────────
# Guard: count rows in the roles table directly via psql.
# Falls back to seeding if the check itself errors (e.g. first deploy).
echo "[5/7] Checking seed state..."

# Build the DSN from individual env vars (Render injects these separately)
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-microfinance}"
DB_USERNAME="${DB_USERNAME:-postgres}"
DB_PASSWORD="${DB_PASSWORD:-}"

ROLE_COUNT=$(PGPASSWORD="$DB_PASSWORD" psql \
    -h "$DB_HOST" \
    -p "$DB_PORT" \
    -U "$DB_USERNAME" \
    -d "$DB_DATABASE" \
    -t -c "SELECT COUNT(*) FROM roles;" 2>/dev/null | tr -d '[:space:]') || ROLE_COUNT=""

echo "     Roles found in DB: '${ROLE_COUNT}'"

if [ -z "$ROLE_COUNT" ] || [ "$ROLE_COUNT" = "0" ]; then
    echo "     ► Seeding database..."
    php artisan db:seed --force --no-interaction
    echo "     ✓ Seed complete."
else
    echo "     ✓ Already seeded (${ROLE_COUNT} roles). Skipping."
fi

# ── 6. Storage link ───────────────────────────────────────────────────────────
echo "[6/7] Linking storage..."
php artisan storage:link --force 2>/dev/null || true

# ── 7. Permissions ────────────────────────────────────────────────────────────
echo "[7/7] Setting permissions..."
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

echo "══════════════════════════════════════════"
echo "  Bootstrap complete. Starting services..."
echo "══════════════════════════════════════════"

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
