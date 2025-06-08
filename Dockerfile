FROM php:8.1-apache

# Create non-root user
RUN useradd -m -s /bin/bash appuser

# Enable Apache modules
RUN a2enmod rewrite headers

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy all necessary files with appropriate permissions
COPY --chown=appuser:appuser ./backend ./backend/
COPY --chown=appuser:appuser ./frontend ./frontend/
COPY --chown=appuser:appuser ./images ./images/
COPY --chown=appuser:appuser ./.env.example ./.env
COPY --chown=appuser:appuser ./index.php ./index.php
COPY --chown=appuser:appuser ./.htaccess ./.htaccess

# Apache configuration
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Add directory configuration to Apache main config
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Create and configure Apache virtual host
RUN echo '<VirtualHost *:${PORT}>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html\n\
    DirectoryIndex index.php index.html\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Set permissions
RUN chown -R appuser:appuser /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} + \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && chmod 640 /var/www/html/.env

# Make sure Apache can write to the necessary directories
RUN mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chown -R appuser:appuser /var/run/apache2 /var/lock/apache2 /var/log/apache2

# Configure Apache to run as appuser
RUN sed -i 's/www-data/appuser/g' /etc/apache2/envvars

# Switch to non-root user
USER appuser

# Expose port (for documentation)
EXPOSE ${PORT}

# Start Apache
CMD ["apache2-foreground"] 