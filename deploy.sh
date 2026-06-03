#!/bin/bash
# Mweela Cash Capital - Production Deployment Script for Truehost cPanel
# Run this from your LOCAL machine

set -e

echo "═══════════════════════════════════════════════"
echo "  Mweela Cash Capital - Production Deploy"
echo "═══════════════════════════════════════════════"

# Configuration
DOMAIN="mweelacredit.co.ke"
CPANEL_USER="YOUR_CPANEL_USERNAME"
SSH_HOST="$CPANEL_USER@$DOMAIN"
REMOTE_DIR="/home/$CPANEL_USER/$DOMAIN"
DB_NAME="mweelac_main"
DB_USER="mweelac_user"
DB_PASS="$(openssl rand -base64 24 | tr -d '=+/' | cut -c1-20)"

echo ""
echo "Step 1: Building production assets..."
npm run build

echo ""
echo "Step 2: Preparing database dump..."
mysqldump -u root -p'Pozee@5268' microfinance > mweela_prod_dump.sql

echo ""
echo "Step 3: Creating production .env..."
cat > .env.production << ENVEOF
APP_NAME="Mweela Cash Capital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://$DOMAIN

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mail.$DOMAIN
MAIL_PORT=587
MAIL_USERNAME=noreply@$DOMAIN
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@$DOMAIN
MAIL_FROM_NAME="Mweela Cash Capital"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="Mweela Cash Capital"
ENVEOF

echo ""
echo "Step 4: Preparing deployment package..."
rm -rf deploy_package
mkdir deploy_package
rsync -av --exclude='.git' --exclude='node_modules' --exclude='deploy_package' \
  --exclude='.env' --exclude='.env.example' --exclude='mweela_prod_dump.sql' \
  ./ deploy_package/
cp .env.production deploy_package/.env
cd deploy_package

echo ""
echo "Step 5: Compressing package..."
tar -czf ../mweela_deploy.tar.gz .
cd ..

echo ""
echo "═══════════════════════════════════════════════"
echo "  Manual Steps Required on Truehost cPanel:"
echo "═══════════════════════════════════════════════"
echo ""
echo "1. Create MySQL Database:"
echo "   - cPanel → MySQL Database Wizard"
echo "   - DB: $DB_NAME"
echo "   - User: $DB_USER"
echo "   - Pass: $DB_PASS"
echo ""
echo "2. Upload mweela_deploy.tar.gz to: $REMOTE_DIR"
echo "   - cPanel File Manager → Upload"
echo "   - Extract in $REMOTE_DIR"
echo ""
echo "3. Import database via phpMyAdmin:"
echo "   - Select $DB_NAME"
echo "   - Import → mweela_prod_dump.sql"
echo ""
echo "4. Run these commands in cPanel Terminal:"
echo "   cd $REMOTE_DIR"
echo "   php artisan key:generate"
echo "   php artisan storage:link"
echo "   php artisan migrate --force"
echo "   php artisan db:seed --force"
echo "   php artisan config:cache"
echo "   php artisan route:cache"
echo "   php artisan view:cache"
echo "   php artisan optimize"
echo ""
echo "5. Set Document Root to: $REMOTE_DIR/public"
echo "   cPanel → Domains → Manage → Document Root"
echo ""
echo "6. Install SSL Certificate:"
echo "   cPanel → SSL/TLS → Let's Encrypt"
echo ""
echo "7. Add Cron Jobs:"
echo "   * * * * * cd $REMOTE_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "═══════════════════════════════════════════════"
echo "  Generated Files:"
echo "═══════════════════════════════════════════════"
echo "  - mweela_deploy.tar.gz (upload this)"
echo "  - mweela_prod_dump.sql (import this)"
echo "  - .env.production (reference)"
echo ""
echo "Database Password: $DB_PASS"
echo ""
