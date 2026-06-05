FROM php:8.2-fpm

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Install composer for dependency management (optional)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application files
COPY . /app
WORKDIR /app

# Create user for running PHP (security best practice)
RUN groupadd -r www && useradd -r -g www www
USER www

# Expose port for PHP-FPM
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD php -r "mysqli_connect('${DB_HOST}', '${DB_USER}', '${DB_PASS}', '${DB_NAME}', ${DB_PORT}) or exit(1);"

CMD ["php-fpm"]
