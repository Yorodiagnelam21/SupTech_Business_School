<?php
// includes/header.php
// Inclus en haut de chaque page (après vérification de session)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SupTech Business School - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
   <link rel="stylesheet" href="/gestion_academique/assets/css/style.css">
    <script>
        (function () {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/gestion_academique/dashboard/">
            <i class="bi bi-mortarboard-fill me-2"></i>SupTech - Gestion Académique
        </a>
        
        <div class="d-flex align-items-center gap-2 me-3">
            <button id="themeToggle" type="button" class="btn btn-outline-light btn-sm" title="Changer de thème">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Affichage utilisateur -->
                <li class="nav-item d-flex align-items-center">
                    <div class="text-light">
                        <small class="text-muted d-block">Connecté : <strong><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></strong></small>
                        <span class="badge bg-info"><?= htmlspecialchars($_SESSION['role_libelle']) ?></span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
