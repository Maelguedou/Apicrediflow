# 1. On part d'une image Linux qui contient déjà PHP 8.3 et le serveur web Apache
FROM php:8.3-apache

# 2. On installe les dépendances système nécessaires pour Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. On installe les extensions PHP requises par Laravel et la base de données (ex: pdo_mysql)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 4. On active le module "rewrite" d'Apache (indispensable pour les routes Laravel)
RUN a2enmod rewrite

# 5. ÉTAPE CRUCIALE : On dit à Apache de pointer sur le dossier /public de Laravel (et non la racine)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 6. On installe Composer depuis son image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. On définit le dossier de travail dans le conteneur
WORKDIR /var/www/html

# 8. On copie tout le code de votre projet dans le dossier de travail du conteneur
COPY . /var/www/html

# 9. On installe les dépendances PHP de Laravel (sans les outils de dev pour la production)
RUN COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-scripts

# 10. On donne les droits d'écriture au serveur web sur les dossiers storage et bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 11. On copie notre script de démarrage et on le rend exécutable
COPY start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

# 12. On dit au serveur de lancer le script au démarrage
CMD ["/usr/local/bin/start"]

RUN COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

