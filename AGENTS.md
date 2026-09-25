AGENTS - Instructions pour agents IA (français)
=============================================

But
----
Fournir des consignes courtes et actionnables pour qu'un agent IA devienne immédiatement productif sur ce projet PHP.

Langue
------
Toutes les interactions avec cet agent doivent être en français, sauf demande explicite contraire.

Démarrage rapide
----------------
- Déployer le dossier `gestion_academique` dans le répertoire web de votre serveur (ex: WAMP `www`).
- Démarrer le serveur web (WAMP) puis ouvrir `http://localhost/gestion_academique`.
- Importer la base de données depuis : [base_de_donnees/gestion_academique_2.sql](base_de_donnees/gestion_academique_2.sql).
- Fichier de configuration DB : [config/database.php](config/database.php).

Entrées clés du projet
----------------------
- Page de connexion : [auth/login.php](auth/login.php)
- Tableau de bord : [dashboard/index.php](dashboard/index.php)
- En-têtes / Pieds : [includes/header.php](includes/header.php), [includes/footer.php](includes/footer.php), [includes/sidebar.php](includes/sidebar.php)
- Export CSV : [etudiants/export_csv.php](etudiants/export_csv.php), [paiements/export_csv_.php](paiements/export_csv_.php)
- Génération PDF : [notes/releve_note_pdf.php](notes/releve_note_pdf.php), [paiements/recu_pdf.php](paiements/recu_pdf.php)
- Librairie PDF : [libs/fpdf/fpdf.php](libs/fpdf/fpdf.php) et dossier des polices [libs/fpdf/font/](libs/fpdf/font/)

Principales conventions et attentes
----------------------------------
- Code PHP majoritairement procédural / scripts modulaires. Préserver le style existant.
- Respecter les inclusions `includes/header.php` et `includes/footer.php` pour garder l'UI cohérente.
- Faire des changements petits et réversibles (PRs ciblés) ; documenter migrations de schéma DB.
- N'ajoutez pas de dépendances lourdes sans proposition préalable (expliquer pourquoi).

Pièges connus
-------------
- Erreur courante PDF : "Could not load font definition file" → vérifier que le dossier [libs/fpdf/font/](libs/fpdf/font/) contient les fichiers `.json` (voir `manuel_utilisateur.md`).
- Encodage CSV / accents : vérifier l'UTF-8 et l'entête correcte pour Excel.

Tests & CI
----------
Il n'y a pas de suite de tests automatisés ni de pipeline CI dans le dépôt. Avant une PR, effectuer des vérifications manuelles basiques : connexion, export CSV, génération PDF.

Comment l'agent doit se comporter
--------------------------------
- Poser une question concise si l'impact d'un changement est incertain (ex: modifications DB, structure de répertoires).
- Proposer un patch minimal (apply_patch) et mentionner les fichiers modifiés.
- Lorsqu'on propose une migration DB, inclure un script SQL et étapes d'importation.

Propositions de personnalisations futures
----------------------------------------
- Créer `.github/copilot-instructions.md` pour instructions de revue PR et workflow local.
- Ajouter un skill pour les tâches fréquentes : génération PDF/CSV et vérification des polices.

Ressources utilisateur
----------------------
- Manuel utilisateur : [manuel_utilisateur.md](manuel_utilisateur.md)
- Cahier des charges : [Cahier_des_charges.md](Cahier_des_charges.md)

Contact
-------
Pour toute question de contexte métier, consultez le `manuel_utilisateur.md` ou demandez un point au propriétaire du projet.
