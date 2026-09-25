# Cahier des charges — Système de Gestion Académique SupTech Business School

## 1. Présentation du projet

### 1.1 Contexte
SupTech Business School gère actuellement le suivi des étudiants, des enseignants, des notes, des paiements et des emplois du temps de façon dispersée (fichiers Excel, papier). Cette absence d'outil centralisé entraîne des pertes de temps, des risques d'erreur (doublons d'inscription, notes égarées) et une difficulté à produire des statistiques fiables pour la direction.

### 1.2 Objectifs du projet
- Centraliser dans une seule application web l'ensemble des données académiques et administratives de l'établissement.
- Sécuriser l'accès aux données selon le rôle de chaque utilisateur (principe du moindre privilège).
- Automatiser les calculs répétitifs (moyennes, mentions, taux de réussite, soldes de paiement).
- Fournir à la direction un tableau de bord synthétique et des statistiques en temps réel.

### 1.3 Porteur du projet
Établissement : SupTech Business School.
Réalisation : projet académique individuel (Licence Informatique de Gestion), développé en PHP/MySQL.

## 2. Périmètre du projet

### 2.1 Acteurs et rôles

| Acteur | Description | Niveau d'accès |
|---|---|---|
| Administrateur | Pilotage global de l'établissement | Accès complet à tous les modules |
| Scolarité | Gestion administrative quotidienne (inscriptions, paiements, emplois du temps) | Accès aux modules de gestion, hors paramétrage système |
| Enseignant | Personnel enseignant | Accès restreint à la saisie des notes de ses propres modules |

### 2.2 Périmètre fonctionnel (modules)

1. Authentification et gestion des rôles
2. Gestion des étudiants (fiche, inscription, historique)
3. Gestion des enseignants (fiche, affectation aux modules)
4. Gestion des classes et des modules
5. Saisie et consultation des notes
6. Gestion des paiements de scolarité
7. Gestion des emplois du temps
8. Gestion des salles
9. Tableau de bord et statistiques

### 2.3 Hors périmètre
- Paiement en ligne (carte bancaire, mobile money) : les paiements sont enregistrés a posteriori, pas de passerelle de paiement.
- Application mobile native : l'interface web responsive couvre l'usage mobile.
- Gestion de la paie des enseignants.

## 3. Spécifications fonctionnelles par module

### Authentification
- Connexion par email + mot de passe (haché avec `password_hash`).
- Redirection automatique selon le rôle après connexion.
- Un enseignant ne peut jamais accéder au tableau de bord administratif ; il est redirigé vers son espace notes.

### Étudiants
- Ajout, modification, suppression (CRUD complet) avec matricule unique.
- Historique des inscriptions par année universitaire.
- Export de la liste des étudiants au format Excel/CSV.

### Enseignants
- Fiche enseignant (matricule, grade, spécialité).
- Affectation à un ou plusieurs modules.

### Classes / Modules
- Une classe regroupe plusieurs modules, chacun avec un coefficient et un volume horaire.

### Notes
- Saisie de CC, TP, Examen par module et par étudiant inscrit.
- Calcul automatique : moyenne = CC×0,2 + TP×0,3 + Examen×0,5.
- Attribution automatique d'une mention (Très Bien / Bien / Assez Bien / Passable / Insuffisant).
- Un enseignant ne peut saisir des notes que pour les modules qui lui sont affectés.
- Génération du relevé de notes de l'étudiant au format PDF.

### Paiements
- Enregistrement des versements par inscription, avec calcul du reste à payer.
- Génération d'un reçu de paiement au format PDF (bibliothèque FPDF).
- Export de la liste des paiements au format Excel/CSV.

### Emplois du temps
- Planification des séances (jour, horaire, salle, classe, enseignant, module).
- Doit éviter les collisions (même salle/enseignant/classe sur le même créneau).

### Salles
- Gestion de la capacité et de la disponibilité.

### Statistiques / Tableau de bord
- Effectifs par classe, taux de réussite, état des paiements, activité récente.

## 4. Spécifications techniques

- Langage serveur : PHP (procédural, mysqli avec requêtes préparées).
- Base de données : MySQL/MariaDB, 13 tables (voir MCD/MLD).
- Environnement de développement : XAMPP en local.
- Frontend : Bootstrap 5, Bootstrap Icons, police Inter.
- Génération de PDF : FPDF (bibliothèque PHP légère, sans dépendance Composer).
- Sécurité : mots de passe hachés, requêtes préparées (anti-injection SQL), vérification de session sur chaque page, échappement systématique des sorties (`htmlspecialchars`).

## 5. Contraintes

- Contrainte de délai : projet à livrer dans le cadre du cursus universitaire.
- Contrainte technique : doit fonctionner sur un environnement XAMPP standard, sans dépendances externes payantes.
- Contrainte d'ergonomie : interface utilisable sans formation préalable par du personnel non technique (scolarité).

## 6. Planning indicatif

| Phase | Contenu |
|---|---|
| 1. Cadrage | Cahier des charges, MCD/MLD |
| 2. Base de données | Script SQL, jeux de test |
| 3. Authentification | Login, gestion des rôles, sécurité des sessions |
| 4. Modules cœur | Étudiants, enseignants, classes, modules |
| 5. Modules pédagogiques | Notes, emplois du temps, salles |
| 6. Modules administratifs | Paiements, statistiques, tableau de bord |
| 7. Finalisation | Tests, manuel utilisateur, soutenance |

## 7bis. Perspectives d'évolution

Ces fonctionnalités ne sont pas incluses dans la version actuelle mais constituent des pistes d'amélioration identifiées pour une prochaine itération :

- **Journal des connexions et des actions des utilisateurs** (traçabilité / audit).
- **Notifications en temps réel** pour les nouvelles inscriptions ou les paiements reçus.
- **Sauvegarde et restauration de la base de données** directement depuis l'application.

> Fonctionnalités désormais implémentées (retirées de cette liste) : génération de documents PDF (relevés, reçus), export des listes en Excel/CSV, thème clair/sombre.

## 7. Livrables attendus

1. Cahier des charges
2. Diagramme de cas d'utilisation
3. MCD
4. MLD
5. Diagramme relationnel
6. Script SQL de création de la base de données
7. Maquettes de l'interface
8. Code source complet de l'application
9. Manuel utilisateur
