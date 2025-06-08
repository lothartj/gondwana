FROM php:8.1-apache

# Create non-root user
RUN useradd -m -s /bin/bash appuser

# Enable Apache modules
RUN a2enmod rewrite

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy only necessary files, excluding sensitive data
COPY --chown=appuser:appuser ./backend ./backend/
COPY --chown=appuser:appuser ./frontend ./frontend/
COPY --chown=appuser:appuser ./images ./images/
COPY --chown=appuser:appuser ./.env.example ./.env

# Apache configuration
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Set permissions
RUN chown -R appuser:appuser /var/www/html \
    && chmod -R 755 /var/www/html

# Switch to non-root user
USER appuser

# Expose port (for documentation)
EXPOSE ${PORT}

# Start Apache
CMD ["apache2-foreground"] 