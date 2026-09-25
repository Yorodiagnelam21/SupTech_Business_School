<?php
// index.php - point d'entrée du site
session_start();

// TODO : si l'utilisateur est déjà connecté ($_SESSION['id_utilisateur'] existe),
// rediriger vers dashboard/index.php
// sinon rediriger vers auth/login.php

if (isset($_SESSION['id_utilisateur'])) {
    $role = isset($_SESSION['role_libelle']) ? $_SESSION['role_libelle'] : '';
    
    if ($role === 'etudiant') {
        header('Location: etudiants/espace.php');
    } elseif ($role === 'enseignant') {
        header('Location: notes/index.php');
    } else {
        // Les administrateurs et membres de la scolarité accèdent au tableau de bord
        header('Location: dashboard/index.php');
    }
} else {
    header('Location: auth/login.php');
}
exit;
