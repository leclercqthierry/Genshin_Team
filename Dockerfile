FROM php:apache

# Installation des dépendances si besoin (libxml2-dev, libonig-dev, etc.)
RUN apt-get update && apt-get install -y libxml2-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring xml \
    && docker-php-ext-enable opcache

# Activer mod_rewrite d'Apache
RUN a2enmod rewrite

RUN pecl install xdebug && docker-php-ext-enable xdebug

EXPOSE 80