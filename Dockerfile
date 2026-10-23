FROM php:8.3-apache-bookworm

RUN docker-php-ext-install pdo pdo_mysql && a2enmod headers rewrite

COPY app/ /var/www/html/

EXPOSE 80