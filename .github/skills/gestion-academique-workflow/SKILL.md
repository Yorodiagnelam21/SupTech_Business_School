---
name: gestion-academique-workflow
description: "Use when: debugging a PHP issue in this project, validating login/auth flows, updating student/payment/note features, checking CSV export or PDF generation, or preparing a minimal fix in the gestion_academique app. Covers repo conventions, root-cause analysis, manual verification, and safe patching for this PHP project."
---

# Workflow de maintenance et de correction pour gestion_academique

## Objectif

Ce skill guide l’agent pour travailler efficacement sur le projet PHP de gestion académique en respectant le style existant, en privilégiant des correctifs petits et réversibles, et en vérifiant le comportement réel avant de conclure.

## Quand l’utiliser

- correction de bug PHP ou logique métier
- ajout ou modification d’une fonctionnalité du module étudiants, paiements, notes, enseignants ou salles
- vérification du flux de connexion / tableau de bord / accès à une page
- export CSV, génération PDF ou problématique d’encodage
- contrôle d’un changement impactant la base de données

## Processus recommandé

### 1. Comprendre la demande et le contexte du projet

- Lire les instructions de projet dans [AGENTS.md](../../AGENTS.md)
- Identifier le module concerné : auth, dashboard, étudiants, paiements, notes, enseignants, salles, statistiques
- Vérifier s’il faut modifier une vue PHP, un script de traitement, un export CSV, ou un PDF

### 2. Localiser précisément le point de rupture

- Repérer les fichiers clés du module concerné
- Lire uniquement les sections pertinentes : route, logique métier, requêtes SQL, inclusions HTML
- Chercher les éléments de contexte connus du projet :
  - connexion : [auth/login.php](../../auth/login.php)
  - tableau de bord : [dashboard/index.php](../../dashboard/index.php)
  - inclusions UI : [includes/header.php](../../includes/header.php), [includes/footer.php](../../includes/footer.php), [includes/sidebar.php](../../includes/sidebar.php)
  - exports : [etudiants/export_csv.php](../../etudiants/export_csv.php), [paiements/export_csv_.php](../../paiements/export_csv_.php)
  - PDF : [notes/releve_note_pdf.php](../../notes/releve_note_pdf.php), [paiements/recu_pdf.php](../../paiements/recu_pdf.php)
  - configuration DB : [config/database.php](../../config/database.php)

### 3. Déterminer la cause racine

- Vérifier si le problème vient d’une mauvaise requête SQL, d’un mauvais chemin d’inclusion, d’un fichier PDF manquant, ou d’un encodage Excel/UTF-8
- Comparer avec le style existant du projet avant de modifier la logique
- Si le comportement est ambigu, poser une question courte avant de changer la structure du projet

### 4. Faire un correctif minimal et propre

- Modifier le strict nécessaire
- Préserver le style procédural / modulaire du projet
- Respecter les inclusions de header/footer pour garder l’UI cohérente
- Éviter les dépendances lourdes si une solution simple existe déjà
- Préférer des changements réversibles et ciblés

### 5. Vérifier le comportement réel

Avant de conclure, faire au moins une vérification manuelle adaptée à la fonctionnalité :

- connexion / authentification
- affichage du dashboard ou de la page concernée
- export CSV : ouverture dans Excel, éventuels accents, colonnes correctes
- génération PDF : présence des polices dans [libs/fpdf/font/](../../libs/fpdf/font/) et absence d’erreur de chargement
- logique métier : paiement, note, étudiant, enseignant, salle

### 6. Gérer les changements de base de données avec précaution

- Si un schéma DB doit évoluer, produire un script SQL minimal et documenté
- Indiquer les étapes d’importation et les impacts sur les données existantes
- Vérifier la cohérence avec [base_de_donnees/gestion_academique_2.sql](../../base_de_donnees/gestion_academique_2.sql)

### 7. Contrôler la qualité avant finalisation

Le correctif est prêt quand :

- le changement est minimal et limité au périmètre demandé
- le code respecte les conventions du projet
- la fonctionnalité a été testée manuellement dans le navigateur / interface
- les erreurs connues sont traitées selon le contexte (PDF, CSV, encodage, SQL)
- aucune migration DB non documentée n’est laissée en suspens

## Points de décision

### Si le problème concerne le PDF

- Vérifier le dossier [libs/fpdf/font/](../../libs/fpdf/font/)
- Vérifier que les fichiers `.json` sont présents
- Vérifier les erreurs de police / définition de police
- Garder la logique actuelle si elle fonctionne ; corriger uniquement le chargement / chemin / encodage

### Si le problème concerne le CSV

- Vérifier l’encodage UTF-8
- Vérifier l’en-tête et le séparateur CSV
- Tester l’ouverture dans un outil comme Excel ou LibreOffice
- Vérifier les accents et les caractères spéciaux

### Si le problème concerne la base de données

- Vérifier [config/database.php](../../config/database.php)
- Confirmer la structure de table concernée
- Produire une migration SQL explicite si nécessaire
- Demander confirmation si le changement a un impact fonctionnel ou structurel large

### Si le problème concerne un flux de page / authentification

- Vérifier la logique de session et les redirections
- Contrôler les includes et les fichiers de layout
- Vérifier si le module est bien accessible après connexion

## Critères de qualité

- patch minimal et réversible
- pas de dépendances lourdes ajoutées sans raison
- respect des règles de l’UI existante
- vérification manuelle des actions core : connexion, export, PDF, navigation
- documentation claire si changement de schema DB

## Exemples de requêtes qui déclenchent ce skill

- “Corrige le bug de connexion dans le projet de gestion académique.”
- “Le PDF des relevés de notes ne génère plus. Trouve la cause et corrige.”
- “Le CSV des étudiants n’affiche pas correctement les accents.”
- “Ajoute la fonctionnalité X avec le style actuel du projet et vérifie le comportement.”
- “Renseigne-moi sur le point de rupture dans le module paiements et propose un patch minimal.”

## Références utiles

- [AGENTS.md](../../AGENTS.md)
- [manuel_utilisateur.md](../../manuel_utilisateur.md)
- [Cahier_des_charges.md](../../Cahier_des_charges.md)
- [config/database.php](../../config/database.php)
- [libs/fpdf/font/](../../libs/fpdf/font/)
