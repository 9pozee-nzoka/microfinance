# Mweela Cash Capital — Complete Truehost Deployment Guide

## Overview

This guide covers the complete deployment process for the Mweela Cash Capital Laravel application to Truehost Cloud cPanel hosting.

---

## Your Server Details (From Welcome Email)

| Item | Value |
|------|-------|
| Domain | `mweelacredit.co.ke` |
| Server IP | `102.212.247.90` |
| cPanel URL | `http://102.212.247.90:2082/` |
| Username | `mweelacr` |
| Server Name | `DAS107` |
| Nameservers | `ns1.cloudoon.com`, `ns2.cloudoon.net`, `ns3.cloudoon.org` |
| Temporary URL | `http://102.212.247.90/~mweelacr/` |

---

## Phase 1: DNS Setup (CRITICAL - Do This First)

### 1.1 Update Nameservers

Your domain must point to Truehost's nameservers. Since you purchased hosting from Truehost Cloud but the domain shows "No Domains Registered With Us", contact Truehost support:

**Submit Ticket:** https://truehost.cloud/submitticket.php  
**Email:** support@truehost.cloud  
**WhatsApp:** Usually on their website

**Tell them:**
```
Please update nameservers for mweelacredit.co.ke to:
- ns1.cloudoon.com (57.128.250.247)
- ns2.cloudoon.net (49.12.105.164)
- ns3.cloudoon.org (158.69.211.95)

My hosting server IP is 102.212.247.90.
```

### 1.2 Verify DNS Propagation

Check if domain resolves:
- https://dnschecker.org → Enter `mweelacredit.co.ke` → Select `A`
- Should show `102.212.247.90`

**Propagation time:** 1-48 hours

---

## Phase 2: cPanel PHP Configuration

### 2.1 Select PHP Version

1. Log into cPanel: `http://102.212.247.90:2082/`
2. Go to **Select PHP Version**
3. Select **PHP 8.3** or **PHP 8.4** (ea-php84)
4. Click **Set as current**

### 2.2 Enable PHP Extensions

Click **"Switch to PHP Options"** and ensure these are enabled:

| Extension | Status |
|-----------|--------|
| curl | ✓ |
| iconv | ✓ |
| mbstring | ✓ |
| openssl | ✓ |
| pdo_mysql | ✓ |
| tokenizer | ✓ |
| xml | ✓ |
| ctype | ✓ |
| json | ✓ |
| bcmath | ✓ |
| fileinfo | ✓ |
| zip | ✓ |
| gd | ✓ |
| intl | ✓ |

### 2.3 Update PHP Limits

Set these values:

```ini
memory_limit = 512M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
max_input_time = 300
allow_url_fopen = On
```

Click **Save**

---

## Phase 3: Create Database

### 3.1 Create MySQL Database

1. cPanel → **MySQL® Database Wizard**
2. Step 1 - Create Database:
   - Database Name: `mweelacr_mweelacredit` (or `mweelacr_main`)
   - Click **Next Step**
3. Step 2 - Create User:
   - Username: `mweelacr_pauljohns730` (or create new)
   - Password: Generate strong password
   - Click **Create User**
4. Step 3 - Add User to Database:
   - Check **ALL PRIVILEGES**
   - Click **Next Step**

### 3.2 Import Database Dump

1. cPanel → **phpMyAdmin**
2. Select your database (e.g., `mweelacr_mweelacredit`)
3. Click **Import** tab
4. Click **Choose File**
5. Select `mweela_prod_dump.sql`
6. Click **Go**

**If import fails with "No database selected":** Make sure you clicked on the database name in the left sidebar BEFORE clicking Import.

---

## Phase 4: Upload Project Files

### 4.1 Upload the ZIP File

1. cPanel → **File Manager**
2. Navigate to `public_html/`
3. Click **Upload** (top menu)
4. Select `mweela_production_deploy.zip` from your computer
5. Wait for upload to complete

### 4.2 Extract the ZIP

1. In File Manager, right-click `mweela_production_deploy.zip`
2. Click **Extract**
3. Extract to: `public_html/`
4. Click **Extract File(s)**

### 4.3 Move Files to Correct Location

The ZIP extracts to a folder. You need files in `public_html/`, not `public_html/mweela_production_deploy/`.

1. Open `mweela_production_deploy/` folder
2. Click **Select All** (top menu)
3. Click **Move** (top menu)
4. Change path to: `/home/mweelacr/public_html/`
5. Click **Move File(s)**
6. Delete the now-empty `mweela_production_deploy/` folder

### 4.4 Verify File Structure

Your `public_html/` should now contain:

```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/              ← This is your web root
│   ├── index.php
│   ├── .htaccess
│   └── build/           ← Vite compiled assets
├── resources/
├── routes/
├── storage/
├── vendor/              ← Pre-installed dependencies
├── .env                 ← Will create in Phase 5
├── artisan
├── composer.json
├── composer.lock
├── php.ini
├── .user.ini
└── ...
```

---

## Phase 5: Configure .env File

### 5.1 Create .env File

1. In File Manager, navigate to `public_html/`
2. Click **+ File** (top left)
3. Filename: `.env`
4. Click **Create New File**
5. Right-click `.env` → **Edit**

### 5.2 Paste This Content

```env
APP_NAME="Mweela Cash Capital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mweelacredit.co.ke
APP_KEY=base64:YOUR_APP_KEY_WILL_BE_GENERATED

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
DB_DATABASE=mweelacr_mweelacredit
DB_USERNAME=mweelacr_pauljohns730
DB_PASSWORD=YOUR_ACTUAL_DB_PASSWORD

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
MAIL_HOST=mail.mweelacredit.co.ke
MAIL_PORT=587
MAIL_USERNAME=noreply@mweelacredit.co.ke
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@mweelacredit.co.ke
MAIL_FROM_NAME="Mweela Cash Capital"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="Mweela Cash Capital"
```

### 5.3 Update Database Credentials

Replace these with your ACTUAL cPanel database details:

```env
DB_DATABASE=mweelacr_mweelacredit    ← Your actual database name
DB_USERNAME=mweelacr_pauljohns730    ← Your actual database username
DB_PASSWORD=YOUR_ACTUAL_PASSWORD     ← Your actual database password
```

Click **Save Changes**

---

## Phase 6: Terminal Commands (cPanel Terminal or SSH)

### 6.1 Open Terminal

cPanel → **Terminal** (or use SSH)

### 6.2 Run These Commands

```bash
# Navigate to project
cd /home/mweelacr/public_html

# Generate application key
php artisan key:generate

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Test database connection
php artisan tinker --execute="echo DB::connection()->getPdo() ? 'DB OK' : 'DB FAIL';"
```

**Expected output:** `DB OK`

If you get "Access denied", check your `.env` DB credentials.

### 6.3 Run Migrations and Seeders

```bash
cd /home/mweelacr/public_html

# Run migrations
php artisan migrate --force

# Run seeders
php artisan db:seed --force
```

### 6.4 Create Storage Link

```bash
cd /home/mweelacr/public_html
php artisan storage:link
```

### 6.5 Set Permissions

```bash
cd /home/mweelacr/public_html

# Make storage and cache writable
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Make .env readable but not writable by others
chmod 644 .env

# Ensure public is readable
chmod -R 755 public
```

### 6.6 Cache Configuration (Optional - for performance)

```bash
cd /home/mweelacr/public_html

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**Note:** Only run these AFTER everything is working. If you change `.env` later, run `php artisan config:clear` first.

---

## Phase 7: Configure Domain Document Root

### 7.1 Set Document Root to public/

1. cPanel → **Domains**
2. Find `mweelacredit.co.ke`
3. Click **Manage** (or pencil icon)
4. Set **Document Root** to: `public_html/public`
5. Click **Update**

### 7.2 If You Cannot Change Document Root

Create `.htaccess` in `public_html/` (project root, NOT inside public/):

```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

**But the Document Root method is preferred.**

---

## Phase 8: SSL Certificate (HTTPS)

### 8.1 Install SSL Certificate

1. cPanel → **SSL/TLS** → **SSL/TLS Certificates**
2. Click **"Install and Manage SSL for your site (HTTPS)"**
3. Under **Install an SSL Website**:
   - Select Domain: `mweelacredit.co.ke`
   - Click **Autofill by Domain** (if available)
   - Or use the SSL certificate you purchased (AskSSL™)
4. Click **Install Certificate**

### 8.2 Force HTTPS (Optional)

Add to `public_html/public/.htaccess` (inside the `<IfModule mod_rewrite.c>` block):

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

---

## Phase 9: Verify Deployment

### 9.1 Test Database Connection

```bash
cd /home/mweelacr/public_html
php artisan tinker --execute="echo DB::connection()->getPdo() ? 'DB OK' : 'DB FAIL';"
```

### 9.2 Test Site URLs

| Test | URL | Expected Result |
|------|-----|----------------|
| Homepage | `https://mweelacredit.co.ke` | Login page or dashboard |
| Login | `https://mweelacredit.co.ke/login` | Login form |
| Admin | `https://mweelacredit.co.ke/dashboard` | Dashboard (after login) |

### 9.3 Test Features

| Feature | URL |
|---------|-----|
| Staff Management | `/staff` |
| Loan Products | `/loan-products` |
| Customers | `/customers` |
| Loans | `/loans` |
| Reports | `/reports` |

---

## Phase 10: Post-Deployment Configuration

### 10.1 Update M-Pesa Callback URLs

Log into Safaricom Daraja Portal and update:

| Callback | URL |
|----------|-----|
| STK Push Callback | `https://mweelacredit.co.ke/mpesa/stk/callback` |
| B2C Result | `https://mweelacredit.co.ke/mpesa/b2c/result` |
| B2C Timeout | `https://mweelacredit.co.ke/mpesa/b2c/timeout` |

### 10.2 Set Up Cron Jobs

cPanel → **Cron Jobs**

```bash
# Laravel Scheduler (runs every minute)
* * * * * cd /home/mweelacr/public_html && php artisan schedule:run >> /dev/null 2>&1

# Queue Worker (if using database queue)
* * * * * cd /home/mweelacr/public_html && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

### 10.3 Create Admin User (If Needed)

```bash
cd /home/mweelacr/public_html
php artisan tinker
```

Then in tinker:
```php
$user = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@mweelacredit.co.ke',
    'password' => bcrypt('YourSecurePassword'),
]);
$user->assignRole('admin');
```

---

## Troubleshooting

### 500 Internal Server Error

Check the log:
```bash
tail -n 50 /home/mweelacr/public_html/storage/logs/laravel.log
```

Common causes:
- Missing `.env` file
- Wrong database credentials
- Missing PHP extensions
- Permission issues

### Database Connection Error

```
SQLSTATE[HY000] [1044] Access denied...
```

**Fix:**
1. Check `.env` DB credentials match cPanel
2. In cPanel → MySQL Databases → ensure user is added to database with ALL PRIVILEGES
3. Run `php artisan config:clear`

### CSS/JS Not Loading (404)

**Fix:**
1. Ensure `public/build/` exists with compiled assets
2. Run `npm run build` locally and re-upload
3. Check `public/build/manifest.json` exists

### "No input file specified" Error

**Fix:** Document root is not set to `public/`. Update in cPanel → Domains.

### Permission Denied on Storage

```bash
chmod -R 775 /home/mweelacr/public_html/storage
chmod -R 775 /home/mweelacr/public_html/bootstrap/cache
```

### Composer Not Found

Since you uploaded pre-built `vendor/` folder, you don't need Composer on the server.

If you need to run composer:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/home/mweelacr --filename=composer
/home/mweelacr/composer install --no-dev --optimize-autoloader
```

---

## Quick Reference Commands

```bash
# Navigate to project
cd /home/mweelacr/public_html

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Test DB
php artisan tinker --execute="echo DB::connection()->getPdo() ? 'DB OK' : 'DB FAIL';"

# Run migrations
php artisan migrate --force

# Check logs
tail -f storage/logs/laravel.log

# Fix permissions
chmod -R 775 storage bootstrap/cache
```

---

## Files Included in Deployment Package

Your `mweela_production_deploy.zip` contains:

| Component | Description |
|-----------|-------------|
| `app/` | Laravel application code |
| `config/` | Configuration files |
| `database/` | Migrations and seeders |
| `public/build/` | Pre-compiled Vite assets |
| `resources/views/` | Blade templates |
| `routes/` | Route definitions |
| `vendor/` | Pre-installed Composer dependencies |
| `.env` | Production environment config |
| `php.ini` / `.user.ini` | PHP settings |
| `mweela_prod_dump.sql` | Database dump for import |

---

## Support Contacts

| Issue | Contact |
|-------|---------|
| DNS/Nameservers | Truehost Support: support@truehost.cloud |
| cPanel Issues | Truehost Support: support@truehost.cloud |
| Application Bugs | Check `storage/logs/laravel.log` |

---

## Deployment Checklist

- [ ] Nameservers updated to Truehost
- [ ] DNS propagated (check with dnschecker.org)
- [ ] PHP 8.3+ selected in cPanel
- [ ] Required PHP extensions enabled
- [ ] Database created in cPanel
- [ ] Database user added with ALL PRIVILEGES
- [ ] Database imported via phpMyAdmin
- [ ] Project files uploaded to `public_html/`
- [ ] `.env` file created with correct credentials
- [ ] `APP_KEY` generated
- [ ] Database connection test passes (`DB OK`)
- [ ] Migrations run successfully
- [ ] Seeders run successfully
- [ ] Storage link created
- [ ] Permissions set (775 storage, 644 .env)
- [ ] Document root set to `public_html/public`
- [ ] SSL certificate installed
- [ ] Site loads at `https://mweelacredit.co.ke`
- [ ] Login works
- [ ] M-Pesa callbacks updated

---

**Last Updated:** 2026-06-03  
**Application Version:** Mweela Cash Capital v1.0
