#!/bin/sh
set -e

# Ensure storage and public upload directories exist with least-privilege permissions
mkdir -p /var/www/html/storage/cache \
         /var/www/html/storage/logs \
         /var/www/html/storage/uploads \
         /var/www/html/public/uploads/wallpapers \
         /var/www/html/public/uploads/news

# Set ownership to www-data and permissions to 775 (directories) / 664 (files)
chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads 2>/dev/null || true
find /var/www/html/storage /var/www/html/public/uploads -type d -exec chmod 775 {} + 2>/dev/null || true
find /var/www/html/storage /var/www/html/public/uploads -type f ! -name ".gitkeep" -exec chmod 664 {} + 2>/dev/null || true

# If vendor/autoload.php does not exist, run composer install
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Composer autoloader not found. Running composer install..."
    composer install --no-interaction --optimize-autoloader --no-progress
fi

exec "$@"
