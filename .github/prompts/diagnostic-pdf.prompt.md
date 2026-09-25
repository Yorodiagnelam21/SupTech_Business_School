---
mode: agent
description: "Use when: PDF generation fails, FPDF font files are missing, or the note/payment PDF must be diagnosed in the gestion_academique project."
---

# Diagnostic rapide des PDF

Analyse le problème de génération PDF dans ce projet PHP.

## Objectif

Trouver la cause racine du bug de PDF, corriger le correctif minimal et vérifier le comportement réel.

## Contexte du projet

- Fichiers de génération PDF : [notes/releve_note_pdf.php](../../notes/releve_note_pdf.php), [paiements/recu_pdf.php](../../paiements/recu_pdf.php)
- Librairie PDF : [libs/fpdf/fpdf.php](../../libs/fpdf/fpdf.php)
- Dossier des polices : [libs/fpdf/font/](../../libs/fpdf/font/)
- Instructions de projet : [AGENTS.md](../../AGENTS.md)

## Processus

1. Identifier le script PDF qui échoue.
2. Vérifier les erreurs courantes liées au chargement des polices.
3. Contrôler si le dossier [libs/fpdf/font/](../../libs/fpdf/font/) contient bien les fichiers nécessaires.
4. Lire les sections pertinentes du script concerné et identifier la cause.
5. Appliquer un correctif minimal et réversible.
6. Vérifier le rendu ou au moins la génération dans le navigateur.

## Critères de réussite

- la cause racine est clairement identifiée,
- le correctif est minimal,
- le script PDF fonctionne sans erreur de police ou de chemin,
- le résultat est compatible avec le style du projet.
