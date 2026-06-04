# Truehost cPanel Deployment Fixes

## Problem 1: `allow_url_fopen` is disabled
Composer cannot download packages from GitHub because `allow_url_fopen=0`.

## Fix: Use cPanel PHP Selector

1. **cPanel → Select PHP Version**
2. Select **PHP 8.4** (ea-php84)
3. Click **"Switch to PHP Options"**
4. Set:
   - `allow_url_fopen` → **On**
   - `memory_limit` → **512M**
   - `upload_max_filesize` → **10M**
   - `post_max_size` → **10M**
   - `max_execution_time` → **300**
5. Enable these extensions:
   - ✓ curl
   - ✓ iconv
   - ✓ mbstring
   - ✓ openssl
   - ✓ pdo_mysql
   - ✓ tokenizer
   - ✓ xml
   - ✓ ctype
   - ✓ json
   - ✓ bcmath
   - ✓ fileinfo
   - ✓ zip
   - ✓ gd
   - ✓ intl
6. Click **Save**

## Fix: Alternative - Upload vendor/ folder

If you cannot enable `allow_url_fopen`, upload the `vendor/` folder from your local machine:

```bash
# On your local machine
cd ~/Desktop/projects/microfinance
zip -r vendor.zip vendor/
```

Upload `vendor.zip` via cPanel File Manager and extract it.

## Problem 2: Composer is outdated

```bash
/usr/local/bin/composer self-update
```

Or use the updated composer:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/home/mweelacr --filename=composer
/home/mweelacr/composer install --no-dev --optimize-autoloader
```

## Problem 3: Missing .env file

Create `.env` in your project root:
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your production database credentials.

## Next Steps After Fixing PHP

```bash
cd ~/mweelacredit.co.ke

# 1. Install dependencies
composer install --no-dev --optimize-autoloader

# 2. Generate app key
php artisan key:generate

# 3. Link storage
php artisan storage:link

# 4. Run migrations
php artisan migrate --force

# 5. Seed database
php artisan db:seed --force

# 6. Cache config/routes/views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 7. Fix permissions
chmod -R 755 storage bootstrap/cache
chmod -R 755 public/build
chmod 644 .env
```

## Document Root Setup

Make sure your domain points to the `public/` folder:
- cPanel → Domains → Manage `mweelacredit.co.ke`
- Document Root: `mweelacredit.co.ke/public`

If you cannot change it, create `.htaccess` in project root:
```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```
