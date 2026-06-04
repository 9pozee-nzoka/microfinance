#!/bin/bash
# Create deployment package for Truehost cPanel upload

echo "Creating deployment package..."

# Build assets first
npm run build 2>/dev/null || echo "No npm build needed"

# Create package directory
rm -rf mweela_deploy_package
mkdir -p mweela_deploy_package

# Copy all project files except excluded items
rsync -av \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='mweela_deploy_package' \
  --exclude='vendor.zip' \
  --exclude='mweela_prod_dump.sql' \
  --exclude='deploy.sh' \
  --exclude='deploy_package.sh' \
  --exclude='.env' \
  --exclude='.env.example' \
  ./ mweela_deploy_package/

# Add vendor folder (since composer may fail on server)
if [ -d "vendor" ]; then
    echo "Adding vendor folder..."
    cp -r vendor mweela_deploy_package/
fi

# Add php config files
cp php.ini mweela_deploy_package/ 2>/dev/null
cp .user.ini mweela_deploy_package/ 2>/dev/null

# Create .env from example
cp .env.example mweela_deploy_package/.env

# Compress
tar -czf mweela_deploy_package.tar.gz mweela_deploy_package/

# Cleanup
rm -rf mweela_deploy_package

echo ""
echo "═══════════════════════════════════════════════"
echo "  Deployment Package Ready!"
echo "═══════════════════════════════════════════════"
echo ""
echo "Files to upload to Truehost:"
echo "  1. mweela_deploy_package.tar.gz (project files + vendor)"
echo "  2. mweela_prod_dump.sql (database)"
echo ""
echo "After uploading and extracting:"
echo "  1. cPanel → Select PHP Version → enable allow_url_fopen + extensions"
echo "  2. Edit .env with your database credentials"
echo "  3. Run: php artisan key:generate"
echo "  4. Run: php artisan storage:link"
echo "  5. Import mweela_prod_dump.sql via phpMyAdmin"
echo "  6. Run: php artisan migrate --force"
echo "  7. Run: php artisan config:cache && php artisan optimize"
echo ""
