#!/bin/bash
set -e

PORT="${PORT:-80}"

# Configure Apache port dynamically for Railway
sed -i "s/Listen [0-9]*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Ensure storage directories exist with correct permissions
mkdir -p /var/www/html/storage/modules /var/www/html/storage/videos /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
