---
mode: agent
description: "Use when: CSV exports produce wrong encoding, missing headers, broken columns, or export logic needs debugging in the gestion_academique app."
---

# Diagnostic rapide des exports CSV

Traite un problème d’export CSV dans ce projet.

## Objectif

Détecter la cause du bug sur un export CSV, corriger sans casser le reste de l’application, et vérifier le résultat réel.

## Fichiers concernés

- [etudiants/export_csv.php](../../etudiants/export_csv.php)
- [paiements/export_csv_.php](../../paiements/export_csv_.php)
- [AGENTS.md](../../AGENTS.md)

## Processus

1. Identifier le script d’export CSV qui pose un problème.
2. Vérifier l’encodage UTF-8 et l’en-tête correct pour Excel.
3. Contrôler les colonnes exportées, le séparateur et les accents.
4. Lire le script, localiser la logique de génération, puis corriger la cause racine.
5. Vérifier le fichier exporté dans un outil compatible comme Excel ou LibreOffice.

## Critères de réussite

- le fichier exporté est lisible sans caractères corrompus,
- les colonnes sont correctes,
- les accents sont préservés,
- le correctif reste minimal et compatible avec le projet.
