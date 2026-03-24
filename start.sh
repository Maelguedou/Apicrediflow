#!/bin/sh

# On met en cache les configurations et les routes pour que l'API soit plus rapide
php artisan config:cache
php artisan route:cache

# On lance les migrations (Création des tables dans la base MySQL). 
# Le --force est obligatoire en production pour confirmer qu'on veut manipuler la DB.
php artisan migrate --force

# On démarre le serveur web Apache en arrière-plan
apache2-foreground
