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

# Expose port 80 (Apache default - Railway will map this)
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
