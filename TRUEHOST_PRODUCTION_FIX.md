# Truehost Production Fix — KYC File 404 Errors

## Your Server Setup (from shell output)
- **Username**: `mweelacr`
- **Server**: `das107`
- **Current directory**: `~/public_html` (this IS your project root)
- **Project path**: `/home/mweelacr/public_html`

## The Problem

The `public/storage` folder exists but is a **real directory** (not a symlink). However, your KYC files are being saved to `storage/app/public/kyc/` (Laravel's default) but they need to be in `public/storage/kyc/` to be accessible via web URLs.

## Fix Steps (Run on Truehost cPanel Terminal)

### Step 1: Check Current State

```bash
cd ~/public_html
pwd
ls -la public/storage
ls storage/app/public/kyc/
```

### Step 2: Copy Existing KYC Files to public/storage/

```bash
cd ~/public_html

# Copy all existing KYC files from storage to public/storage/
cp -r storage/app/public/kyc/* public/storage/kyc/ 2>/dev/null

# Fix permissions
chmod -R 755 public/storage
```

### Step 3: Verify Files Are Accessible

Visit these URLs in your browser:
- `https://mweelacredit.co.ke/storage/kyc/FILENAME.pdf`
- `https://mweelacredit.co.ke/storage/kyc/FILENAME.jpg`

Replace `FILENAME` with an actual file from `public/storage/kyc/`.

### Step 4: Test New Upload

Register a new customer or edit an existing one, upload a KYC document, and verify it's accessible immediately.

---

## Permanent Fix (Already Applied in Code)

The code changes I made will ensure **all future uploads** go directly to `public/storage/kyc/` instead of `storage/app/public/kyc/`.

### What Changed in CustomerController.php

**Before (broken on shared hosting):**
```php
$paths[$column] = $request->file($field)->store('kyc', 'public');
// Saves to: storage/app/public/kyc/ (NOT web accessible without symlink)
```

**After (works everywhere):**
```php
$filename = $request->file($field)->hashName();
$request->file($field)->move(public_path('storage/kyc'), $filename);
$paths[$column] = 'kyc/' . $filename;
// Saves to: public/storage/kyc/ (directly web accessible)
```

---

## If You Still Get 404s After Copying

### Check 1: Is the file actually there?

```bash
cd ~/public_html
ls -la public/storage/kyc/
```

### Check 2: Check .htaccess isn't blocking storage

```bash
cat public/.htaccess
```

Make sure there's NO rule like:
```apache
RewriteRule ^storage/.*$ - [F,L]
```

### Check 3: Check file permissions

```bash
cd ~/public_html
chmod -R 755 public/storage
chmod -R 644 public/storage/kyc/*
```

### Check 4: Check if mod_rewrite is working

```bash
curl -I https://mweelacredit.co.ke/storage/kyc/FILENAME.jpg
```

Should return `HTTP/2 200` not `404`.

---

## For Future Deployments

When you deploy new code via git/FTP:

1. **Make sure `public/storage/` is a real directory** (not a symlink):
   ```bash
   cd ~/public_html
   rm -f public/storage        # remove if it's a symlink
   mkdir -p public/storage/kyc # create real directory
   ```

2. **The `.gitignore` in `public/storage/` will preserve the folder** but ignore uploaded files, so new deployments won't delete customer uploads.

3. **After deployment, re-copy existing files if needed:**
   ```bash
   cp -r storage/app/public/kyc/* public/storage/kyc/ 2>/dev/null
   ```

---

## Summary

| Location | Purpose | Web Accessible? |
|----------|---------|----------------|
| `storage/app/public/kyc/` | Laravel's default storage | ❌ No (needs symlink) |
| `public/storage/kyc/` | Direct public access | ✅ Yes |

**The fix**: All new uploads now go to `public/storage/kyc/`. For existing files, copy them over once.
