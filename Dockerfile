FROM php:8.2-apache
# Enable Apache modules needed for PHP
RUN a2enmod rewrite
RUN a2enmod headers
# Install mysqli extension
RUN docker-php-ext-install mysqli
# Copy application files
COPY . /var/www/html/
# Copy our custom vhost config (includes CORS headers) so Apache actually uses it
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
# Create .htaccess for clean URLs (optional)
RUN echo "RewriteEngine On" > /var/www/html/.htaccess
EXPOSE 80
# Fix MPM conflict at startup
CMD ["bash", "-c", "rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ && apache2-foreground"]FROM php:8.2-apache
# Enable Apache modules needed for PHP
RUN a2enmod rewrite
RUN a2enmod headers
# Install mysqli extension
RUN docker-php-ext-install mysqli
# Copy application files
COPY . /var/www/html/
# Copy our custom vhost config (includes CORS headers) so Apache actually uses it
COPY Apache-vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
# Create .htaccess for clean URLs (optional)
RUN echo "RewriteEngine On" > /var/www/html/.htaccess
EXPOSE 80
# Fix MPM conflict at startup
CMD ["bash", "-c", "rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ && apache2-foreground"]
