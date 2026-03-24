# Guide Définitif : Déployer une API Laravel 11/13 sur Render.com et Aiven (MySQL)

Ce document récapitule toutes les étapes exactes, de la configuration du code jusqu'aux clics dans les interfaces web, pour héberger facilement et gratuitement une architecture API Laravel complète.

---

## SOMMAIRE
1. Préparation du code local (Fichiers requis)
2. Tutoriel Interface Aiven : Créer la Base de Données
3. Tutoriel Interface Render : Déployer le Serveur
4. Pièges classiques et Solutions (Mémoire, HTTPS, etc.)
5. Configurer la Messagerie (Mails OTP Chrome/Gmail)
6. Tester son API en production

---

## 1. Préparation du code local (Fichiers requis)

Pour que Render sache comment lancer Laravel (qui a besoin de pointer sur le dossier `/public`), il faut "conteneuriser" le projet. Créez ces 3 fichiers à la racine de votre projet :

### A. Le `Dockerfile`
Ce fichier explique le processus d'installation.
```dockerfile
# 1. Image de base avec PHP et Apache
FROM php:8.3-apache

# 2. Dépendances système Linux
RUN apt-get update && apt-get install -y libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Extensions PHP pour Laravel et MySQL
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 4. Activation du module rewrite d'Apache
RUN a2enmod rewrite

# 5. Pointe Apache vers le dossier /public de Laravel (OBLIGATOIRE)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 6. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . /var/www/html

# 7. Installation des paquets Laravel (avec les drapeaux de sécurité pour éviter les crashs)
RUN COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# 8. Droits d'écriture
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Lancement du script de démarrage
COPY start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start
CMD ["/usr/local/bin/start"]
```

### B. Le fichier `.dockerignore`
Empêche de saturer la RAM de Render. **Ne l'oubliez surtout pas.**
```text
vendor/
node_modules/
.env
public/hot
public/storage
storage/logs/*
tests/
```

### C. Le fichier de démarrage (`start.sh`)
Crée automatiquement vos tables MySQL au démarrage du serveur.
```bash
#!/bin/sh
php artisan config:cache
php artisan route:cache
php artisan migrate --force
apache2-foreground
```

### D. La Règle du "Mixed Content" (Pour que Swagger fonctionne)
Dans le fichier `app/Providers/AppServiceProvider.php`, ajoutez ce code dans la fonction `boot` pour forcer le HTTPS en production (Render utilise un proxy) :
```php
public function boot(): void
{
    if (env('APP_ENV') === 'production') {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
}
```

**➔ Une fois ces fichiers créés, poussez l'intégralité de votre code (sauf le `.env`) sur GitHub.**

---

## 2. Tutoriel Interface Aiven : Créer la Base de Données (A à Z)

Aiven va héberger votre base MySQL 100% gratuitement.

1. **Inscription :** Allez sur `console.aiven.io/signup` et créez un compte.
2. **Nouveau Projet :** Aiven crée un projet par défaut (souvent nommé d'après votre email). C'est parfait.
3. **Créer le Service :** 
   - Cliquez sur le gros bouton bleu **"Create service"**.
   - Dans le catalogue, sélectionnez **"MySQL"**.
4. **Choisir le Forfait Gratuit :**
   - Descendez à la section *Service Plan*.
   - Cliquez sur l'onglet **"Free"** (Vérifiez bien qu'il affiche 0,00 $ / mois).
   - Choisissez la région européenne la plus proche (ex: *DigitalOcean - Frankfurt*).
5. **Finaliser :**
   - Donnez un nom au service tout en bas (ex: `crediflow-db`).
   - Cliquez sur **"Create Free Service"**.
6. **Récupérer les identifiants :**
   - La base va mettre ~2 minutes à s'allumer (l'icône passera au vert "RUNNING").
   - Restez sur l'onglet **Overview**. Vous verrez un encart "Connection information".
   - Au lieu de l'URL brute, cliquez sur l'onglet **Parameters** dans cet encart.
   - Vous aurez alors sous les yeux toutes les valeurs dont nous aurons besoin pour Render : `Host`, `Port` (souvent 19066 ou dans les 20000), `User` (qui est **avnadmin**, pas root), et le `Password` (attention aux caractères spéciaux).
   - *Laissez cet onglet Aiven ouvert dans un coin.*

---

## 3. Tutoriel Interface Render : Déployer le Serveur (A à Z)

Render va faire tourner le code PHP et s'occuper de relier votre GitHub à internet.

1. **Inscription :** Allez sur `render.com` et inscrivez-vous avec votre compte GitHub.
2. **Nouveau Service :**
   - Sur le tableau de bord (Dashboard), cliquez sur le bouton **"New +"** en haut à droite.
   - Sélectionnez **"Web Service"**.
3. **Connecter GitHub :**
   - Sous la section *Deploy an existing repository*, cliquez sur **"Build and deploy from a Git repository"**.
   - Cliquez sur le bouton pour lier votre compte GitHub si ce n'est pas fait, puis sélectionnez votre dépôt (ex: `Maelguedou/Apicrediflow`).
4. **Configurer le Serveur :**
   - **Name :** Donnez le nom public de votre API (ex: `api-crediflow`). Cela donnera l'URL `api-crediflow.onrender.com`.
   - **Region :** Choisissez Francfort (Frankfurt).
   - **Branch :** Laissez `main` (ou la branche master).
   - **Environment :** Render doit automatiquement afficher **"Docker"** grâce à notre fichier `Dockerfile`. Si ce n'est pas le cas, sélectionnez-le manuellement.
   - **Instance Type :** Assurez-vous que l'onglet sélectionné est bien le **"Free"** (0 $ / mois).
5. **Les Variables d'Environnement (L'Étape Critique) :**
   C'est ici que l'on remplace le `.env` local. Descendez jusqu'à "Environment Variables" et cliquez sur "Add Environment Variable" pour ajouter chaque ligne :
   - `APP_NAME` ➔ `CrediFlow`
   - `APP_ENV` ➔ `production`
   - `APP_KEY` ➔ `base64:votre_cle_exacte_du_fichier_.env_local`
   - `APP_DEBUG` ➔ `false` *(À passer sur `true` uniquement si le site affiche "Erreur 500" pour voir pourquoi)*
   - `APP_URL` ➔ `https://api-crediflow.onrender.com` *(Remplacez par votre vrai nom)*
   - `ASSET_URL` ➔ `https://api-crediflow.onrender.com` *(Crucial pour que Swagger ne soit pas une page blanche)*
   - `L5_SWAGGER_CONST_HOST` ➔ `https://api-crediflow.onrender.com`
   - `DB_CONNECTION` ➔ `mysql`
   - `DB_HOST` ➔ *(Copiez l'hôte depuis l'onglet Aiven)*
   - `DB_PORT` ➔ *(Copiez le port depuis l'onglet Aiven)*
   - `DB_DATABASE` ➔ `defaultdb` *(Le nom par défaut d'Aiven)*
   - `DB_USERNAME` ➔ `avnadmin`
   - `DB_PASSWORD` ➔ *(Copiez le mot de passe depuis l'onglet Aiven)*. **ATTENTION :** Si le mot de passe contient un caractère spécial comme un `$`, entourez-le impérativement de guillemets simples ` 'mot$depasse' ` dans Render !
6. **Lancement :**
   - Cliquez au fond de la page sur **"Create Web Service"**.
   - Une console noire apparaît. Attendez environ 5 minutes. Une fois le message "Live" affiché en vert, votre API est mondialement en ligne !

*(Une fois ceci fait, à chaque fois que vous ferez un `git push` sur votre code local, Render lancera automatiquement un nouveau déploiement gratuit en 2 minutes).*

---

## 4. Pièges classiques et Solutions Déjà traitées

- **Le Crash Composer "Exit Code 2"** : Sans `--ignore-platform-reqs`, le serveur refusera d'installer vos paquets s'il lui manque une extension spécifique que vous aviez sur votre ordi. Sans `--no-scripts`, l'API plantera au build en cherchant la base de données trop tôt.
- **Access Denied Aiven** : 99% du temps, vous avez oublié de changer l'utilisateur "root" en "avnadmin", ou vous n'avez pas protégé un mot de passe contenant un `$` via des guillemets.
- **La Page Blanche de Swagger** : N'oubliez surtout pas en local la commande `php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"` puis de `git add public/` pour pousser les fichiers CSS. Pensez à forcer le HTTPS dans votre `AppServiceProvider` !

---

## 5. Configurer la Messagerie (Les mails OTP)

Pour que l'Authentification à Double Facteur (OTP) fonctionne, Render doit pouvoir utiliser votre compte Gmail.
Dans votre tableau de bord Render (onglet *Environment*), ajoutez ceci :
- `MAIL_MAILER` ➔ `smtp`
- `MAIL_HOST` ➔ `smtp.gmail.com`
- `MAIL_PORT` ➔ `587`
- `MAIL_USERNAME` ➔ `votre-email@gmail.com`
- `MAIL_PASSWORD` ➔ `votre_mot_de_passe_dapplication` (Généré via la sécurité du compte Google : "Mots de passe des applications").
- `MAIL_ENCRYPTION` ➔ `tls`
- `MAIL_FROM_ADDRESS` ➔ `votre-email@gmail.com`

**Astuce de survie :** Google va bloquer le tout premier e-mail envoyé car Render est localisé en Allemagne (Francfort). Il suffit d'aller dans la boîte mail concernée, d'ouvrir l'alerte rouge de Google, et de valider "Oui c'était moi". Le blocage sera levé à vie.

---

## 6. Tester son API en production

**Vous avez une erreur "Route [login] not defined" ?**
Cela veut dire que vous avez testé une route protégée de votre API depuis la barre d'adresse de votre navigateur (Chrome/Firefox). Les navigateurs demandent par défaut du HTML. Laravel, voyant que vous n'avez pas donné de Token (Mot de passe API), essaie de vous re-diriger visuellement vers le formulaire de Connexion Web (la fameuse route `/login`). Comme c'est une API pure, ça plante.

**La bonne méthode :**
On ne teste **JAMAIS** une API sécurisée dans une barre d'adresse. 
Utilisez impérativement l'URL de votre documentation `/api/documentation`, cliquez sur **Authorize** pour y mettre un token. Swagger enverra l'entête correcte `Accept: application/json`.
Vous obtiendrez ainsi de vrais retours d'erreurs lisibles (comme `401 Unauthenticated`) !
