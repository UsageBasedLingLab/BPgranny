FROM php:8.2-apache

# Enable Apache modules needed for PHP
RUN a2enmod rewrite
RUN a2enmod headers

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy application files
COPY . /var/www/html/
WORKDIR /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Create .htaccess for clean URLs (optional)
RUN echo "RewriteEngine On" > /var/www/html/.htaccess

EXPOSE 80

# Use Railway's PORT variable so Apache listens on the right port
CMD ["bash", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && sed -i \"s/*:80/*:${PORT:-80}/g\" /etc/apache2/sites-enabled/000-default.conf && apache2-foreground"]
