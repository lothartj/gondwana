FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    libzip-dev \
    zip \
    && docker-php-ext-install zip pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure nginx
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Create app directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod +x /var/www/html/docker/entrypoint.sh

# Expose port 80
EXPOSE 80

# Start Nginx & PHP-FPM
CMD ["/var/www/html/docker/entrypoint.sh"] 