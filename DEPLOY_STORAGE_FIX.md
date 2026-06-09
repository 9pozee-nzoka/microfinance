# Fix KYC File 404 Errors on Production (Truehost)

## Root Causes

The 404 errors for `/storage/kyc/...` files on `mweelacredit.co.ke` are caused by one of these issues:

### 1. Missing `public/storage` Symlink (Most Common)

On cPanel shared hosting, `php artisan storage:link` often fails because symlinks are disabled or restricted.

### 2. Symlink Broken After Deployment
If you deployed via git or FTP, the symlink may not have been created or may be pointing to the wrong path.

### 3. Wrong Document Root
If the domain document root is not set to `public/`, the `/storage/` URLs won't resolve correctly.

---

## Fix Option 1: Manual Symlink via cPanel Terminal (Recommended)

Log into cPanel → Terminal and run:

```bash
cd ~/mweelacredit.co.ke

# Remove broken symlink if exists
rm -f public/storage

# Create symlink (may fail on some shared hosts)
php artisan storage:link
```

If `php artisan storage:link` fails with a permission error, use **Fix Option 2**.

---

## Fix Option 2: Copy Files Instead of Symlink (cPanel Shared Hosting)

On Truehost and many cPanel hosts, symlinks in `public/` are blocked for security. Use this instead:

```bash
cd ~/mweelacredit.co.ke

# Remove broken symlink
rm -rf public/storage

# Create actual directory
mkdir -p public/storage

# Copy all files from storage/app/public to public/storage
# (Run this after every new file upload, or set up a cron job)
cp -r storage/app/public/* public/storage/

# Fix permissions
chmod -R 755 public/storage
```

### Automate with a Cron Job

cPanel → Cron Jobs → Add New:

```bash
# Sync storage files every 5 minutes
*/5 * * * * cd /home/mweelacr/mweelacredit.co.ke && cp -r storage/app/public/* public/storage/ 2>/dev/null
```

> Replace `mweelacr` with your actual cPanel username.

---

## Fix Option 3: Custom Storage Route (Most Reliable)

If symlinks and copies don't work, serve files through Laravel instead:

### Step 1: Create a Route

Add to `routes/web.php` inside the staff middleware group:

```php
// File serving route for KYC documents (bypasses symlink issues)
Route::get('/kyc-file/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    return response()->file($fullPath);
})->where('path', '.*')->name('kyc.file');
```

### Step 2: Update the KYC Documents View

In `resources/views/customers/kyc-documents.blade.php`, replace:

```blade
{{ Storage::url($customer->id_front_path) }}
```

With:

```blade
{{ route('kyc.file', $customer->id_front_path) }}
```

Do this for all 4 document types.

---

## Fix Option 4: Change Storage Disk to `local` with Custom URL

### Step 1: Update `config/filesystems.php`

Add a new disk:

```php
'kyc_public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL') . '/kyc-file',
    'visibility' => 'public',
    'throw' => false,
],
```

### Step 2: Update Controller

In `CustomerController.php`, change:

```php
$paths[$column] = $request->file($field)->store('kyc', 'public');
```

To:

```php
$paths[$column] = $request->file($field)->store('kyc', 'kyc_public');
```

---

## Quick Checklist for Production

| Check | Command / Location |
|-------|-------------------|
| Symlink exists? | `ls -la public/storage` |
| Files in storage? | `ls storage/app/public/kyc/` |
| Files accessible via URL? | Visit `https://mweelacredit.co.ke/storage/kyc/FILENAME` |
| Document root is `public/`? | cPanel → Domains → Check Document Root |
| `.htaccess` allows access? | Check `public/.htaccess` has no deny rules for `/storage` |
| File permissions correct? | `chmod -R 755 storage/app/public` |

---

## Immediate Fix for Truehost

Since Truehost cPanel often blocks symlinks, the **most reliable fix** is:

1. **Delete the broken symlink:**
   ```bash
   cd ~/mweelacredit.co.ke
   rm -rf public/storage
   ```

2. **Create the directory and copy files:**
   ```bash
   mkdir -p public/storage/kyc
   cp -r storage/app/public/kyc/* public/storage/kyc/
   chmod -R 755 public/storage
   ```

3. **Verify:**
   Visit `https://mweelacredit.co.ke/storage/kyc/FILENAME.jpg` — should show the image.

4. **For future uploads**, update the controller to save directly to `public/storage` OR run the copy command after each deployment.

---

## Permanent Fix: Save Directly to Public

If you want to avoid symlink/copy issues entirely, change where files are saved:

In `CustomerController.php`:

```php
// Instead of:
$paths[$column] = $request->file($field)->store('kyc', 'public');

// Use:
$filename = $request->file($field)->hashName();
$request->file($field)->move(public_path('storage/kyc'), $filename);
$paths[$column] = 'kyc/' . $filename;
```

This saves files directly to `public/storage/kyc/` — no symlink needed ever.
