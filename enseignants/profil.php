<?php
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id_enseignant = isset($_GET['id_enseignant']) ? (int) $_GET['id_enseignant'] : 0;
if (!$id_enseignant) {
    header('Location: /gestion_academique/enseignants/');
    exit;
}

$enseignant = null;
if ($stmt = mysqli_prepare($connexion, "SELECT * FROM enseignants WHERE id_enseignant = ? LIMIT 1")) {
    mysqli_stmt_bind_param($stmt, 'i', $id_enseignant);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $enseignant = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$enseignant) {
    header('Location: /gestion_academique/enseignants/');
    exit;
}

$modules = [];
if ($stmt = mysqli_prepare($connexion, "SELECT m.*, c.nom AS classe_nom
    FROM enseignant_module em
    JOIN modules m ON em.id_module = m.id_module
    JOIN classes c ON m.id_classe = c.id_classe
    WHERE em.id_enseignant = ?
    ORDER BY c.nom, m.nom")) {
    mysqli_stmt_bind_param($stmt, 'i', $id_enseignant);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $modules[] = $row;
    }
    mysqli_stmt_close($stmt);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-person-badge-fill me-2"></i>Profil enseignant</h1>
            <p class="text-muted mb-0">Informations du compte et matières assignées</p>
        </div>
        <a href="/gestion_academique/enseignants/" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:60px;height:60px;font-size:1.5rem;">
                            <?= strtoupper(substr($enseignant['prenom'], 0, 1) . substr($enseignant['nom'], 0, 1)) ?>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-0"><?= htmlspecialchars($enseignant['prenom'] . ' ' . $enseignant['nom']) ?></h4>
                            <small class="text-muted"><?= htmlspecialchars($enseignant['grade'] ?: 'Grade non renseigné') ?></small>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Matricule :</strong> <?= htmlspecialchars($enseignant['matricule']) ?></li>
                        <li class="list-group-item"><strong>Email :</strong> <?= htmlspecialchars($enseignant['email'] ?: '-') ?></li>
                        <li class="list-group-item"><strong>Téléphone :</strong> <?= htmlspecialchars($enseignant['telephone'] ?: '-') ?></li>
                        <li class="list-group-item"><strong>Spécialité :</strong> <?= htmlspecialchars($enseignant['specialite'] ?: '-') ?></li>
                        <li class="list-group-item"><strong>Mot de passe par défaut :</strong> <span class="badge bg-warning text-dark">enseignant123</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h4 class="mb-3"><i class="bi bi-book-half me-2"></i>Matières affectées</h4>

                    <?php if (empty($modules)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Cet enseignant n’a aucune matière assignée pour le moment.
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($modules as $module): ?>
                                <div class="col">
                                    <div class="border rounded p-3 h-100 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="mb-0"><?= htmlspecialchars($module['nom']) ?></h5>
                                            <span class="badge bg-primary">Coef <?= htmlspecialchars((string) $module['coefficient']) ?></span>
                                        </div>
                                        <p class="mb-1 text-muted"><strong>Classe :</strong> <?= htmlspecialchars($module['classe_nom']) ?></p>
                                        <p class="mb-0 text-muted"><strong>Heures :</strong> <?= (int) $module['nb_heures'] ?> heures</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
