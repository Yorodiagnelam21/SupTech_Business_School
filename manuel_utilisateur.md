# Manuel utilisateur — Système de Gestion Académique SupTech

## 1. Introduction

Cette application permet de gérer les étudiants, les enseignants, les notes, les paiements et les emplois du temps de SupTech Business School. L'accès et les fonctionnalités disponibles dépendent du rôle de chaque utilisateur : Administrateur, Scolarité ou Enseignant.

## 2. Connexion

1. Ouvrez l'application dans votre navigateur.
2. Saisissez votre adresse e-mail et votre mot de passe.
3. Cliquez sur « Se connecter ».

Vous êtes automatiquement redirigé vers l'espace correspondant à votre rôle : le tableau de bord pour un administrateur ou un membre de la scolarité, l'espace de saisie des notes pour un enseignant.

En cas d'oubli de mot de passe, contactez l'administrateur système : il n'existe pas (à ce stade) de procédure de réinitialisation en libre-service.

## 3. Le tableau de bord

Après connexion (administrateur / scolarité), le tableau de bord affiche :
- le nombre total d'étudiants, d'enseignants, de classes et de modules ;
- le taux de réussite global ;
- les derniers étudiants inscrits ;
- les dernières notes enregistrées.

Le menu latéral, à gauche, permet d'accéder à tous les modules.

## 4. Gestion des étudiants

Menu : **Étudiants**

- **Ajouter un étudiant** : cliquez sur « Ajouter un étudiant », remplissez le matricule (obligatoire et unique), le nom et le prénom (obligatoires), puis les informations optionnelles (date de naissance, sexe, email, adresse). Cliquez sur « Ajouter ».
- **Modifier un étudiant** : cliquez sur l'icône de modification sur la ligne de l'étudiant concerné, ajustez les champs, cliquez sur « Modifier ».
- **Supprimer un étudiant** : cliquez sur l'icône de suppression, confirmez dans la boîte de dialogue. Cette action est irréversible et supprime aussi les données liées (inscriptions, notes, paiements).
- **Exporter la liste en Excel** : cliquez sur le bouton « Exporter Excel » en haut de la page. Un fichier `.csv` se télécharge, à ouvrir directement avec Excel (les accents s'affichent correctement).

## 5. Gestion des enseignants

Menu : **Enseignants** (réservé à l'administrateur)

- Ajout d'une fiche enseignant avec matricule, grade et spécialité.
- Affectation d'un enseignant à un ou plusieurs modules : cette affectation conditionne les modules pour lesquels il pourra saisir des notes.

## 6. Gestion des classes et modules

Menu : **Classes** puis **Modules** (réservé à l'administrateur)

- Créez d'abord la classe (nom, filière, niveau, effectif maximum).
- Créez ensuite les modules rattachés à cette classe, avec leur coefficient et leur volume horaire.

## 7. Saisie des notes

Menu : **Notes**

- Sélectionnez un module dans la liste (un enseignant ne voit que les modules qui lui sont affectés).
- Pour chaque étudiant inscrit, saisissez le CC, le TP et l'Examen (notes sur 20).
- La moyenne (CC 20 % + TP 30 % + Examen 50 %) et la mention se calculent automatiquement dès que les trois notes sont renseignées.
- Cliquez sur « Enregistrer » pour valider la saisie.

Vous pouvez laisser une note vide si elle n'a pas encore été évaluée ; la moyenne ne sera calculée qu'une fois les trois notes présentes.

**Télécharger le relevé de notes (PDF)** : depuis la fiche d'un étudiant, accédez au relevé de notes complet (modules, CC/TP/Examen, moyenne, mention) au format PDF, prêt à imprimer ou à envoyer par email.

## 8. Gestion des paiements

Menu : **Paiements** (administrateur, scolarité)

- Sélectionnez l'inscription concernée, saisissez le montant versé, le montant total dû et le mode de paiement.
- Le solde restant est calculé et affiché automatiquement.
- **Télécharger le reçu (PDF)** : cliquez sur l'icône PDF sur la ligne du paiement concerné pour générer et télécharger un reçu officiel (montant payé, montant total, reste à payer, date).
- **Exporter la liste en Excel** : cliquez sur le bouton « Exporter Excel » en haut de la page pour obtenir un fichier `.csv` de tous les paiements.

## 9. Emploi du temps

Menu : **Emplois du temps** (administrateur, scolarité)

- Créez une séance en choisissant le jour, l'horaire, la salle, la classe, l'enseignant et le module.
- Le système signale toute collision (même salle, même enseignant ou même classe déjà occupés sur ce créneau).

## 10. Gestion des salles

Menu : **Salles** (réservé à l'administrateur)

- Ajout d'une salle avec son nom et sa capacité d'accueil.

## 11. Statistiques

Menu : **Statistiques** (administrateur, scolarité)

- Vue synthétique des effectifs par classe, du taux de réussite par module et de l'état global des paiements.

## 12. Déconnexion

Cliquez sur l'icône de déconnexion (en haut à droite ou dans le menu latéral) puis confirmez. Votre session est immédiatement fermée.

## 13. Thème clair / sombre

Un bouton (icône lune/soleil) en haut à droite de chaque page permet de basculer entre le thème clair et le thème sombre. Votre préférence est mémorisée automatiquement : elle s'applique à nouveau la prochaine fois que vous ouvrez l'application, même après avoir fermé le navigateur.

## 14. Bonnes pratiques et sécurité

- Ne partagez jamais votre mot de passe.
- Déconnectez-vous systématiquement lorsque vous quittez un poste partagé.
- Vérifiez le matricule avant de créer un étudiant ou un enseignant : il doit être unique et ne peut pas être modifié ensuite.
- Toute suppression est définitive ; en cas de doute, contactez l'administrateur avant de supprimer une fiche.

## 15. Résolution des problèmes courants

| Problème | Cause probable | Solution |
|---|---|---|
| « Adresse e-mail ou mot de passe incorrect » | Identifiants erronés ou compte désactivé | Vérifier la saisie, contacter l'administrateur si le compte est désactivé |
| Impossible de saisir une note | Le module ne vous est pas affecté | Demander à l'administrateur de vous affecter au module concerné |
| « Ce matricule existe déjà » | Matricule déjà utilisé par un autre étudiant/enseignant | Vérifier la liste existante avant de créer une nouvelle fiche |
| Erreur lors du téléchargement d'un PDF (« Could not load font definition file ») | Le dossier `libs/fpdf/font/` (fichiers `.json`) est manquant ou mal placé | Vérifier que `libs/fpdf/font/` contient bien les 14 fichiers `.json` (helvetica, times, courier...), au même niveau que `fpdf.php` |
