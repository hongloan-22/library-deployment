FROM php:8.3-apache-bookworm

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod headers rewrite \
    && sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && printf '\nServerName localhost\n' >> /etc/apache2/apache2.conf \
    && printf 'expose_php = Off\n' > /usr/local/etc/php/conf.d/security.ini \
    && mkdir -p /var/run/apache2 /var/lock/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2

COPY app/ /var/www/html/

EXPOSE 8080

USER www-data