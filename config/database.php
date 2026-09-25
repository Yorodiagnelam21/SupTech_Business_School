<?php
// ============================================================
// config/database.php
// Point de connexion UNIQUE à la base de données.
// Tous les autres fichiers PHP doivent faire :
//   require_once __DIR__ . '/../config/database.php';
// ============================================================

$host = 'localhost';
$db_name = 'gestion_academique';
$db_user = 'root';
$db_pass = ''; // par défaut vide sur XAMPP

$connexion = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$connexion) {
    die('Erreur de connexion à la base de données : ' . mysqli_connect_error());
}

mysqli_set_charset($connexion, 'utf8mb4');

if (!function_exists('require_role')) {
    function require_role(array $roles)
    {
        $role = isset($_SESSION['role_libelle']) ? $_SESSION['role_libelle'] : '';
        if (!in_array($role, $roles, true)) {
            header('Location: /gestion_academique/index.php');
            exit;
        }
    }
}

if (session_status() === PHP_SESSION_ACTIVE
    && isset($_SESSION['role_libelle'])
    && $_SESSION['role_libelle'] === 'etudiant') {
    $script = str_replace('\\', '/', isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
    $pages_etudiant = [
        '/gestion_academique/etudiants/espace.php',
        '/gestion_academique/notes/releve_note_pdf.php',
        '/gestion_academique/paiements/recu_pdf.php'
    ];

    if (!in_array($script, $pages_etudiant, true)) {
        header('Location: /gestion_academique/etudiants/espace.php');
        exit;
    }
}

if (session_status() === PHP_SESSION_ACTIVE
    && isset($_SESSION['role_libelle'])
    && $_SESSION['role_libelle'] === 'scolarite') {
    $script = str_replace('\\', '/', isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
    $pages_scolarite = [
        '/gestion_academique/dashboard/index.php',
        '/gestion_academique/etudiants/index.php',
        '/gestion_academique/etudiants/export_csv.php',
        '/gestion_academique/enseignants/index.php',
        '/gestion_academique/enseignants/profil.php',
        '/gestion_academique/classes/index.php',
        '/gestion_academique/modules/index.php',
        '/gestion_academique/paiements/index.php',
        '/gestion_academique/paiements/export_csv_.php',
        '/gestion_academique/paiements/recu_pdf.php',
        '/gestion_academique/emplois/index.php',
        '/gestion_academique/salles/index.php',
        '/gestion_academique/notes/index.php',
        '/gestion_academique/notes/releve_note_pdf.php'
    ];

    if (!in_array($script, $pages_scolarite, true)) {
        header('Location: /gestion_academique/dashboard/index.php');
        exit;
    }
}
