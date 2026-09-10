FROM php:8.2-apache

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure PHP runtime & safe memory limits
RUN { \
    echo 'upload_max_filesize = 100M'; \
    echo 'post_max_size = 128M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_time = 300'; \
    echo 'variables_order = "EGPCS"'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Enable Apache mod_rewrite & headers
RUN a2enmod rewrite headers

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides in DocumentRoot
RUN printf '<Directory /var/www/html/public/>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

# Copy application files
WORKDIR /var/www/html
COPY neurosatva/ /var/www/html/

# Copy and setup entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
