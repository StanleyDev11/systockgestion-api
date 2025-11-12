# Utiliser une image PHP officielle avec Apache
FROM php:8.2-apache

# Installer les extensions PHP nécessaires pour PostgreSQL
# docker-php-ext-install est un script fourni dans l'image pour installer les extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Activer le module de réécriture d'URL d'Apache pour la prise en charge de .htaccess
RUN a2enmod rewrite

# Copier les fichiers de l'application dans le répertoire web racine du conteneur
# Le contenu du répertoire 'api' sera à la racine du serveur web
COPY api/ /var/www/html/

# Exposer le port 80 pour le serveur web Apache
EXPOSE 80
