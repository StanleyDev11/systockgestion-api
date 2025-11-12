# Utiliser une image PHP officielle avec Apache
FROM php:8.2-apache

# Installer les dépendances nécessaires pour PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Activer le module de réécriture d'URL d'Apache pour la prise en charge de .htaccess
RUN a2enmod rewrite

# Copier les fichiers de l'application dans le répertoire web racine du conteneur
COPY api/ /var/www/html/

# Donner les bons droits à Apache (optionnel mais recommandé)
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80 pour le serveur web Apache
EXPOSE 80
