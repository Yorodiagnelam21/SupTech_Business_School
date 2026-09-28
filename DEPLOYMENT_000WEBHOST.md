# Déploiement sur 000webhost

## 1) Créer le compte
- Allez sur : https://www.000webhost.com/
- Créez un compte gratuit
- Créez un site web gratuit

## 2) Télécharger le projet
- Depuis GitHub, téléchargez le dépôt ZIP ou clonez-le localement
- Dézippez le dossier

## 3) Uploader le projet
- Dans votre panneau 000webhost, ouvrez le gestionnaire de fichiers
- Uploadez le dossier du projet dans `public_html/`
- Le plus simple est de placer le projet dans un sous-dossier :
  `public_html/gestion_academique/`

## 4) Créer la base de données MySQL
- Dans 000webhost, ouvrez la section Base de données MySQL
- Créez une base : `gestion_academique`
- Récupérez :
  - Host : `localhost` ou `mysql.000webhost.com` selon le panneau
  - Nom de la base
  - Utilisateur
  - Mot de passe

## 5) Importer le script SQL
- Ouvrez phpMyAdmin
- Sélectionnez la base `gestion_academique`
- Importez le fichier :
  `base_de_donnees/gestion_academique_2.sql`

## 6) Configurer la connexion
- Modifiez le fichier : `config/database.php`
- Remplacez par vos identifiants réels :

```php
$host = 'localhost';
$db_name = 'gestion_academique';
$db_user = 'votre_utilisateur';
$db_pass = 'votre_mot_de_passe';
```

## 7) Vérifier le site
Votre site sera accessible via :

`https://votre-nom.000webhostapp.com/gestion_academique/`

ou si le projet est placé directement à la racine :

`https://votre-nom.000webhostapp.com/`

## 8) Connexion par défaut
Le projet contient une logique d’authentification ; utilisez les comptes créés dans la base de données ou ajoutez un utilisateur depuis phpMyAdmin / SQL.

## Points importants
- Ce projet est en PHP procédural et utilise des chemins absolus `/gestion_academique/...`
- Il est donc plus fiable de le déployer dans un sous-dossier `gestion_academique`
- Les chemins d'accès doivent rester cohérents avec le nom du dossier de déploiement

## Si le site ne s'ouvre pas
Vérifiez :
- le dossier est bien dans `public_html/gestion_academique`
- la base est importée correctement
- les identifiants MySQL sont corrects
- les fonctions PHP mysqli sont activées
