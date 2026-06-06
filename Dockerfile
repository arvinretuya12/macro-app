FROM php:8.2-apache
RUN apt-get update && apt-get install -y ca-certificates && docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite
COPY . /var/www/html/
EXPOSE 80