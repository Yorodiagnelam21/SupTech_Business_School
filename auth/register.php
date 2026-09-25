<?php
session_start();
if (isset($_SESSION['id_utilisateur'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$message_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim(isset($_POST['nom']) ? $_POST['nom'] : '');
    $prenom = trim(isset($_POST['prenom']) ? $_POST['prenom'] : '');
    $matricule = trim(isset($_POST['matricule']) ? $_POST['matricule'] : '');
    $email = filter_var(trim(isset($_POST['email']) ? $_POST['email'] : ''), FILTER_VALIDATE_EMAIL);
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    $confirmation = isset($_POST['confirmation']) ? $_POST['confirmation'] : '';

    if (!$nom || !$prenom || !$matricule || !$email || strlen($mot_de_passe) < 6) {
        $message_error = 'Tous les champs sont obligatoires et le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($mot_de_passe !== $confirmation) {
        $message_error = 'Les mots de passe ne correspondent pas.';
    } else {
        $stmt = mysqli_prepare($connexion, 'SELECT id_etudiant, nom, prenom, email FROM etudiants WHERE matricule = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $matricule);
        mysqli_stmt_execute($stmt);
        $etudiant = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);

        if (!$etudiant || strcasecmp($etudiant['nom'], $nom) !== 0 || strcasecmp($etudiant['prenom'], $prenom) !== 0) {
            $message_error = 'Aucun étudiant ne correspond à ce nom, prénom et matricule.';
        } else {
            $stmt = mysqli_prepare($connexion, 'SELECT id_utilisateur FROM utilisateurs WHERE email = ? OR id_etudiant = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'si', $email, $etudiant['id_etudiant']);
            mysqli_stmt_execute($stmt);
            $compte_existant = mysqli_stmt_get_result($stmt)->fetch_assoc();
            mysqli_stmt_close($stmt);

            if ($compte_existant) {
                $message_error = 'Un compte existe déjà pour cet e-mail ou cet étudiant.';
            } else {
                $role_result = mysqli_query($connexion, "SELECT id_role FROM roles WHERE libelle = 'etudiant' LIMIT 1");
                $role = $role_result ? mysqli_fetch_assoc($role_result) : false;
                if (!$role) {
                    $message_error = 'Le rôle étudiant n’est pas configuré.';
                } else {
                    $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($connexion, 'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, id_etudiant, actif) VALUES (?, ?, ?, ?, ?, ?, 1)');
                    mysqli_stmt_bind_param($stmt, 'ssssii', $nom, $prenom, $email, $hash, $role['id_role'], $etudiant['id_etudiant']);
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        header('Location: login.php?inscription=ok');
                        exit;
                    }
                    $message_error = 'Impossible de créer le compte : ' . mysqli_error($connexion);
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 620px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3 mb-3">Créer un compte étudiant</h1>
            <p class="text-muted">Utilisez les informations déjà enregistrées par la scolarité.</p>
            <?php if ($message_error): ?><div class="alert alert-danger"><?= htmlspecialchars($message_error) ?></div><?php endif; ?>
            <form method="post">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nom</label><input class="form-control" name="nom" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Prénom</label><input class="form-control" name="prenom" required></div>
                </div>
                <div class="mb-3"><label class="form-label">Matricule</label><input class="form-control" name="matricule" required></div>
                <div class="mb-3"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email" required></div>
                <div class="mb-3"><label class="form-label">Mot de passe</label><input type="password" class="form-control" name="mot_de_passe" minlength="6" required></div>
                <div class="mb-4"><label class="form-label">Confirmer le mot de passe</label><input type="password" class="form-control" name="confirmation" minlength="6" required></div>
                <button class="btn btn-primary w-100">Créer mon compte</button>
            </form>
            <a class="d-block text-center mt-3" href="login.php">Retour à la connexion</a>
        </div>
    </div>
</div>
</body>
</html>
