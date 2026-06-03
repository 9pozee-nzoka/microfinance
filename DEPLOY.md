# Mweela Cash Capital — Truehost Cloud Deployment Plan

## Your Environment
- **Host**: Truehost Cloud (cPanel)
- **Domain**: mweelacredit.co.ke
- **Database**: MySQL (production)
- **PHP**: ^8.3 required
- **Build**: Vite (npm run build)

---

## Phase 1: Pre-Deploy Checklist (Local)

### 1.1 Commit Everything
```bash
git add .
git commit -m "Ready for production deploy"
git push origin main
```

### 1.2 Build Production Assets
```bash
npm run build
```
This creates `public/build/` with hashed assets. Commit this folder.

### 1.3 Export Local Database
```bash
mysqldump -u root -p'Pozee@5268' microfinance > mweela_prod_dump.sql
```

### 1.4 Create Production .env
Copy `.env.example` to `.env.production` and configure:
```env
APP_NAME="Mweela Cash Capital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mweelacredit.co.ke

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=mweelac_main
DB_USERNAME=mweelac_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Use cPanel email or SMTP
MAIL_MAILER=smtp
MAIL_HOST=mail.mweelacredit.co.ke
MAIL_PORT=587
MAIL_USERNAME=noreply@mweelacredit.co.ke
MAIL_PASSWORD=EMAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@mweelacredit.co.ke
```

---

## Phase 2: Server Setup (Truehost cPanel)

### 2.1 Create MySQL Database
1. cPanel → MySQL Database Wizard
2. Database: `mweelac_main`
3. User: `mweelac_user`
4. Password: Generate strong password
5. **ALL PRIVILEGES** → Next Step

### 2.2 Check PHP Version
1. cPanel → Select PHP Version
2. Select **PHP 8.3** (or 8.4 if available)
3. Enable extensions:
   - `pdo_mysql`
   - `mbstring`
   - `openssl`
   - `tokenizer`
   - `xml`
   - `ctype`
   - `json`
   - `bcmath`
   - `curl`
   - `fileinfo`
   - `zip`
   - `gd`
   - `intl`

### 2.3 Upload Project

**Option A: Git Clone (Best)**
```bash
# In cPanel Terminal
rm -rf ~/mweelacredit.co.ke/*
cd ~/mweelacredit.co.ke
git clone https://github.com/YOUR_USERNAME/microfinance.git .
```

**Option B: ZIP Upload**
1. Zip project locally (excluding `vendor/`, `node_modules/`, `.git/`)
2. cPanel File Manager → Upload to `mweelacredit.co.ke/`
3. Extract ZIP

### 2.4 Install Composer Dependencies
```bash
cd ~/mweelacredit.co.ke
composer install --no-dev --optimize-autoloader --no-interaction
```

If composer not available in cPanel, upload `vendor/` folder from local.

---

## Phase 3: Configuration

### 3.1 Upload .env
```bash
# From local machine
scp .env.production user@mweelacredit.co.ke:~/mweelacredit.co.ke/.env
```

Or edit via cPanel File Manager.

### 3.2 Generate App Key
```bash
cd ~/mweelacredit.co.ke
php artisan key:generate
```

### 3.3 Import Database
```bash
mysql -u mweelac_user -p'YOUR_PASSWORD' mweelac_main < mweela_prod_dump.sql
```

Or use cPanel → phpMyAdmin → Import.

### 3.4 Run Fresh Migrations (if needed)
```bash
cd ~/mweelacredit.co.ke
php artisan migrate --force
php artisan db:seed --force
```

---

## Phase 4: Web Server Setup

### 4.1 Point Domain to public/ folder

**Method A: Addon Domain with custom docroot**
cPanel → Domains → Create New Domain:
- Domain: `mweelacredit.co.ke`
- Document Root: `mweelacredit.co.ke/public`

**Method B: If docroot cannot be changed**
Create `~/mweelacredit.co.ke/.htaccess`:
```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

And `~/mweelacredit.co.ke/public/.htaccess` (Laravel default):
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 4.2 Force HTTPS
Add to `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

### 4.3 SSL Certificate
cPanel → SSL/TLS → Let's Encrypt:
- Issue certificate for `mweelacredit.co.ke`
- Include `www.mweelacredit.co.ke`

---

## Phase 5: Storage & Permissions

```bash
cd ~/mweelacredit.co.ke
php artisan storage:link

# Fix permissions
chmod -R 755 storage bootstrap/cache
chmod -R 755 public/build
chmod 644 .env
```

---

## Phase 6: Optimization

```bash
cd ~/mweelacredit.co.ke
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

---

## Phase 7: Cron Jobs

cPanel → Cron Jobs:

```bash
# Laravel Scheduler (every minute)
* * * * * cd /home/YOUR_CPANEL_USER/mweelacredit.co.ke && php artisan schedule:run >> /dev/null 2>&1

# Queue Worker (if using database queue driver)
* * * * * cd /home/YOUR_CPANEL_USER/mweelacredit.co.ke && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

---

## Phase 8: M-Pesa Callback Configuration

Update Safaricom Daraja Portal:
- **STK Callback**: `https://mweelacredit.co.ke/mpesa/stk/callback`
- **B2C Result**: `https://mweelacredit.co.ke/mpesa/b2c/result`
- **B2C Timeout**: `https://mweelacredit.co.ke/mpesa/b2c/timeout`

Ensure these routes have no auth middleware (already configured).

---

## Phase 9: Post-Deploy Testing

| Test | URL | Expected |
|------|-----|----------|
| Login page | https://mweelacredit.co.ke/login | 200 OK |
| Admin login | pauljohns730@gmail.com / Pozee@5268 | Dashboard |
| Staff Overview | /staff | Staff list |
| Loan Products | /loan-products | Chemsha & Jijenge |
| Customer create | /customers/create | New fields visible |
| M-Pesa callbacks | POST /mpesa/stk/callback | Returns JSON |

---

## Phase 10: Maintenance & Updates

### For Future Updates:
```bash
# Local
npm run build
git add .
git commit -m "Update"
git push origin main

# Server (cPanel Terminal)
cd ~/mweelacredit.co.ke
git pull origin main
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting Truehost Specific

| Problem | Fix |
|---------|-----|
| Composer not found | Upload `vendor/` folder from local |
| PHP version too old | cPanel → Select PHP Version → 8.3 |
| Memory limit | cPanel → Select PHP Version → memory_limit = 512M |
| Upload size limit | cPanel → Select PHP Version → upload_max_filesize = 10M |
| 403 Forbidden | Check folder permissions (755), file (644) |
| 500 Internal Error | Check `storage/logs/laravel.log` |
| CSS/JS 404 | Ensure `public/build/` exists, run `npm run build` |
| Database connection error | Verify `.env` DB_HOST is `localhost` |

---

## Files to Upload (if not using Git)

```
mweelacredit.co.ke/
├── app/
├── bootstrap/
├── config/
├── database/
├── lang/ (if exists)
├── public/
│   ├── build/          ← from npm run build
│   ├── index.php
│   └── .htaccess
├── resources/
├── routes/
├── storage/
├── vendor/             ← from composer install
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── vite.config.js
└── .env                ← production version
```

**DO NOT upload:** `.git/`, `node_modules/`, `.env.example`
