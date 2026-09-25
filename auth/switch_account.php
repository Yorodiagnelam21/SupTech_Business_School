<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$mot_de_passe = isset($_POST['mot_de_passe']) ? trim($_POST['mot_de_passe']) : '';

if ($role === '' || $mot_de_passe === '') {
    header('Location: ../index.php');
    exit;
}

$normalized_role = strtolower($role);
$normalized_role = str_replace(
    [' ', 'é', 'è', 'ê', 'à', 'ç', 'ô', 'û', 'î', 'ï', 'ù'],
    ['', 'e', 'e', 'e', 'a', 'c', 'o', 'u', 'i', 'i', 'u'],
    $normalized_role
);

$credentials = [
    'admin' => ['email' => 'admin@suptech.sn', 'password' => 'admin123', 'role' => 'administrateur'],
    'scolarite' => ['email' => 'scolarite@suptech.sn', 'password' => 'scolarite123', 'role' => 'scolarite'],
    'enseignant' => ['email' => 'enseignant@suptech.sn', 'password' => 'enseignant123', 'role' => 'enseignant'],
    'etudiant' => ['email' => 'etudiant@suptech.sn', 'password' => 'etudiant123', 'role' => 'etudiant'],
];

if (!isset($credentials[$normalized_role])) {
    header('Location: ../index.php');
    exit;
}

$selected = $credentials[$normalized_role];

$query = "SELECT u.*, r.libelle AS role_libelle
          FROM utilisateurs u
          JOIN roles r ON r.id_role = u.id_role
          WHERE u.email = ? AND u.actif = 1
          LIMIT 1";

$user = null;
if ($stmt = mysqli_prepare($connexion, $query)) {
    mysqli_stmt_bind_param($stmt, 's', $selected['email']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
    session_regenerate_id(true);

    $_SESSION['id_utilisateur'] = (int) $user['id_utilisateur'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['prenom'] = $user['prenom'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['id_role'] = (int) $user['id_role'];
    $_SESSION['role_libelle'] = $user['role_libelle'];
    $_SESSION['id_enseignant'] = $user['id_enseignant'];
    $_SESSION['id_etudiant'] = $user['id_etudiant'];

    header('Location: ../index.php');
    exit;
}

if ($mot_de_passe === $selected['password']) {
    session_regenerate_id(true);

    $_SESSION['id_utilisateur'] = 0;
    $_SESSION['nom'] = ucfirst($role);
    $_SESSION['prenom'] = '';
    $_SESSION['email'] = $selected['email'];
    $_SESSION['id_role'] = 0;
    $_SESSION['role_libelle'] = $selected['role'];
    $_SESSION['id_enseignant'] = null;
    $_SESSION['id_etudiant'] = null;

    header('Location: ../index.php');
    exit;
}

header('Location: login.php?error=1');
exit;
