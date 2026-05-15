#!/bin/bash
# =============================================================================
# EC2 Initial Setup Script — Absence Manager Backend
# OS: Ubuntu 22.04 / 24.04 LTS
# Run once as ubuntu user: bash setup.sh
# =============================================================================
set -e

APP_DIR="/var/www/absence-manager"
REPO_URL="https://github.com/0xsnowZ/Absence-Manager-backend.git"
PHP_VERSION="8.4"

echo "=== [1/8] System update ==="
sudo apt-get update -y && sudo apt-get upgrade -y

echo "=== [2/8] Install Nginx ==="
sudo apt-get install -y nginx

echo "=== [3/8] Install PHP $PHP_VERSION + extensions ==="
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y
sudo apt-get install -y \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-pdo \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-tokenizer \
    php${PHP_VERSION}-opcache \
    unzip git curl

echo "=== [4/8] Install Composer ==="
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

echo "=== [5/8] Clone the repository ==="
sudo mkdir -p $APP_DIR
sudo chown ubuntu:www-data $APP_DIR
git clone $REPO_URL $APP_DIR
cd $APP_DIR

echo "=== [6/8] Install PHP dependencies ==="
composer install --no-dev --optimize-autoloader --no-interaction

echo "=== [7/8] Configure environment ==="
cp .env.example .env

# Generate app key
php artisan key:generate --force

# Configure SQLite (persistent on EC2 EBS volume)
touch database/database.sqlite
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i '/^DB_HOST/d; /^DB_PORT/d; /^DB_DATABASE/d; /^DB_USERNAME/d; /^DB_PASSWORD/d' .env

# Production settings
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i "s|^APP_URL=.*|APP_URL=http://$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)|" .env

# Session, cache, queue
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=cookie/' .env
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env

echo "=== [8/8] Permissions, migrations, optimisation ==="
sudo chown -R ubuntu:www-data $APP_DIR
sudo chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache
sudo chmod 664 $APP_DIR/database/database.sqlite
sudo chmod 775 $APP_DIR/database

php artisan migrate --force
php artisan db:seed --class=TimeBlockSeeder --force 2>/dev/null || true
php artisan db:seed --class=TypeAbsenceSeeder --force 2>/dev/null || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅ Setup complete!"
echo "   Now copy deploy/nginx.conf to /etc/nginx/sites-available/absence-manager"
echo "   and run: sudo ln -s /etc/nginx/sites-available/absence-manager /etc/nginx/sites-enabled/"
echo "   then: sudo nginx -t && sudo systemctl reload nginx"
echo ""
echo "   Don't forget to set FRONTEND_URL in .env for CORS!"
