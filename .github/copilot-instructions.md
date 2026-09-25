# Instructions globales pour le dépôt gestion_academique

## Objectif

Travailler sur ce projet PHP avec un correctif minimal, réversible et cohérent avec l’architecture existante.

## Règles de travail

- Préférer des changements ciblés et petits plutôt qu’une refonte.
- Respecter le style procédural / modulaire du projet.
- Conserver la cohérence UI en utilisant les inclusions de [includes/header.php](../includes/header.php), [includes/footer.php](../includes/footer.php) et [includes/sidebar.php](../includes/sidebar.php).
- Éviter les dépendances lourdes sans justification claire.
- Documenter tout changement de schéma ou de migration SQL.
- Vérifier les impacts sur les modules concernés avant de conclure.

## Contexte projet

- Dossier web : `gestion_academique`
- Point d’entrée principal : [index.php](../index.php)
- Authentification : [auth/login.php](../auth/login.php)
- Dashboard : [dashboard/index.php](../dashboard/index.php)
- Configuration base de données : [config/database.php](../config/database.php)
- Export CSV : [etudiants/export_csv.php](../etudiants/export_csv.php), [paiements/export_csv_.php](../paiements/export_csv_.php)
- Génération PDF : [notes/releve_note_pdf.php](../notes/releve_note_pdf.php), [paiements/recu_pdf.php](../paiements/recu_pdf.php)
- Polices FPDF : [libs/fpdf/font/](../libs/fpdf/font/)

## Pièges connus

- PDF : erreur “Could not load font definition file” → vérifier la présence des fichiers JSON dans [libs/fpdf/font/](../libs/fpdf/font/).
- CSV : vérifier UTF-8 et en-tête Excel.
- DB : vérifier les requêtes SQL et la structure des tables avant de modifier les scripts.

## Vérification avant finalisation

Avant d’affirmer qu’un correctif est terminé :

1. vérifier la logique métier concernée,
2. tester le comportement réel dans le navigateur si possible,
3. valider les cas CSV / PDF si applicable,
4. vérifier que le patch est minimal et documenté,
5. confirmer qu’aucune migration DB non expliquée n’est laissée en suspens.

## Demandes fréquentes

Quand une demande touche les modules suivants, travailler de façon ciblée :

- étudiants
- paiements
- notes
- enseignants
- salles
- statistiques
- auth / dashboard

## Rappel de sécurité et de qualité

- Ne pas modifier la structure du projet sans nécessité.
- Ne pas ajouter de dépendances lourdes sans explication.
- Poser une question courte si l’impact du changement est incertain.
- Rester fidèle aux conventions de [AGENTS.md](../AGENTS.md).
