#!/bin/bash
# =============================================================================
# EC2 Deploy Script — run on every code update
# Usage (from your local machine):
#   ssh -i your-key.pem ubuntu@<EC2-IP> 'bash /var/www/absence-manager/deploy/deploy.sh'
# Or set up as GitHub Actions (see .github/workflows/deploy.yml)
# =============================================================================
set -e

APP_DIR="/var/www/absence-manager"
PHP="php8.4"

echo "=== Pulling latest code ==="
cd $APP_DIR
git pull origin main

echo "=== Installing/updating dependencies ==="
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== Fixing permissions ==="
sudo chown -R ubuntu:www-data $APP_DIR
sudo chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache
sudo chmod 664 $APP_DIR/database/database.sqlite 2>/dev/null || true
sudo chmod 775 $APP_DIR/database

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Clearing and rebuilding caches ==="
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Reloading PHP-FPM ==="
sudo systemctl reload php8.4-fpm

echo "✅ Deploy complete!"
