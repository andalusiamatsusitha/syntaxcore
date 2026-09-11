#!/bin/sh
set -e

# Ensure storage directories exist and have proper permissions
mkdir -p /var/www/html/storage/cache \
         /var/www/html/storage/logs \
         /var/www/html/storage/uploads

chmod 777 /var/www/html/storage/cache \
          /var/www/html/storage/logs \
          /var/www/html/storage/uploads 2>/dev/null || true

# If vendor/autoload.php does not exist, run composer install
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Composer autoloader not found. Running composer install..."
    composer install --no-interaction --optimize-autoloader --no-progress
fi

exec "$@"
