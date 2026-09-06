#!/bin/bash
set -e

PORT="${PORT:-80}"

echo "Configuring Apache ports..."
cat << 'EOF' > /etc/apache2/ports.conf
Listen 80
Listen 8080
EOF

if [ "$PORT" != "80" ] && [ "$PORT" != "8080" ]; then
    echo "Listen ${PORT}" >> /etc/apache2/ports.conf
fi

cat << 'EOF' > /etc/apache2/sites-available/000-default.conf
<VirtualHost *>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public/>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Ensure storage directories exist with proper permissions
mkdir -p /var/www/html/storage/modules /var/www/html/storage/videos /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

echo "Starting Apache on ports (80, 8080, ${PORT})..."
exec apache2-foreground
