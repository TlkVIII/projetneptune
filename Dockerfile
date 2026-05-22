FROM php:8.3-apache

# extensions PHP
RUN docker-php-ext-install pdo pdo_mysql

# ON COPIE TOUT
COPY . /var/www/html/

# IMPORTANT : on change le document root vers /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# modification Apache pour utiliser /public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80
