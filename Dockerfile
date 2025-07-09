FROM php:apache

# Installation des dépendances si besoin (libxml2-dev, libonig-dev, etc.)
RUN apt-get update && apt-get install -y libxml2-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring xml \
    && docker-php-ext-enable opcache

# Activer mod_rewrite d'Apache
RUN a2enmod rewrite

# Activer la mise en cache des ressources statiques (Expires)
RUN a2enmod expires

# Activer la compression GZIP des contenus textuels (Deflate)
RUN a2enmod deflate

RUN pecl install xdebug && docker-php-ext-enable xdebug

EXPOSE 80