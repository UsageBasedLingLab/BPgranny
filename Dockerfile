FROM php:8.2-apache
FROM php:8.2-cli

RUN docker-php-ext-install mysqli

COPY . /var/www/html/
COPY . /app/

EXPOSE 80
WORKDIR /app

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080"]
