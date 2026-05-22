FROM php:8.3-apache

# dossier officiel Apache
WORKDIR /var/www/html

# extensions PHP
RUN docker-php-ext-install pdo pdo_mysql

# copie du projet
COPY . /var/www/html/

EXPOSE 80
