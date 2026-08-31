FROM php:8.2-apache

# Install PDO MySQL and required extensions
RUN docker-php-ext-install pdo pdo_mysql opcache

# Enable Apache mod_rewrite and headers
RUN a2enmod rewrite headers

# Configure Apache DocumentRoot to point to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN echo '<Directory /var/www/html/public/>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/neurosatva.conf \
    && a2enconf neurosatva

# Copy project files
WORKDIR /var/www/html
COPY neurosatva/ /var/www/html/

# Create and set permissions for storage directory
RUN mkdir -p /var/www/html/storage/modules /var/www/html/storage/videos \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Support dynamic PORT for Railway / Render
COPY <<'EOF' /usr/local/bin/entrypoint.sh
#!/bin/bash
set -e
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf
fi
exec apache2-foreground
EOF

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
