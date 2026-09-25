<?php
// notes/index.php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$role = isset($_SESSION['role_libelle']) ? $_SESSION['role_libelle'] : '';
require_role(['administrateur', 'scolarite', 'enseignant']);
$id_enseignant = isset($_SESSION['id_enseignant']) ? $_SESSION['id_enseignant'] : null;

// Les enseignants ne voient que leurs modules
$modules_enseignant = [];
if ($role === 'enseignant' && $id_enseignant) {
    $query = "SELECT m.*, c.nom as classe_nom 
              FROM modules m
              JOIN classes c ON m.id_classe = c.id_classe
              JOIN enseignant_module em ON m.id_module = em.id_module
              WHERE em.id_enseignant = ?";
    
    if ($stmt = mysqli_prepare($connexion, $query)) {
        mysqli_stmt_bind_param($stmt, "i", $id_enseignant);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $modules_enseignant[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Administrateurs & Scolarité voient tous les modules
    $query = "SELECT m.*, c.nom as classe_nom FROM modules m JOIN classes c ON m.id_classe = c.id_classe ORDER BY c.nom, m.nom";
    if ($res = mysqli_query($connexion, $query)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $modules_enseignant[] = $row;
        }
    }
}

// Inclusion de l'entête et du menu latéral
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestion des Notes</h1>
    <div class="btn-toolbar mb-2 mb-md-0 d-flex gap-2 align-items-center">
        <?php if ((isset($role) ? $role : '') === 'enseignant'): ?>
            <a href="../enseignants/profil.php?id_enseignant=<?= (int) (isset($id_enseignant) ? $id_enseignant : 0) ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-lines-fill me-1"></i>Mon profil
            </a>
        <?php endif; ?>
        <span class="badge bg-primary px-3 py-2 fs-6">
            <i class="bi bi-person-badge-fill me-1"></i>
            <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?> (<?= htmlspecialchars($_SESSION['role_libelle']) ?>)
        </span>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h4 class="card-title text-primary"><i class="bi bi-journal-bookmark-fill me-2"></i>Espace Enseignant - Saisie des notes</h4>
        <p class="text-muted">
            <?php if ($role === 'enseignant'): ?>
                En tant qu'enseignant, vous pouvez saisir et modifier les notes de Contrôle Continu (CC), Travaux Pratiques (TP) et d'Examen pour les modules qui vous sont attribués.
            <?php else: ?>
                En tant qu'administrateur ou membre du service scolarité, vous pouvez visualiser l'ensemble des modules de l'établissement.
            <?php endif; ?>
        </p>
    </div>
</div>

<h5 class="mb-3 font-weight-bold text-secondary">Liste de vos modules attribués</h5>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php if (empty($modules_enseignant)): ?>
        <div class="col-12 w-100">
            <div class="alert alert-info py-4 text-center">
                <i class="bi bi-info-circle-fill fs-2 d-block mb-2"></i>
                Aucun module ne vous est attribué pour le moment.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($modules_enseignant as $mod): ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm card-module">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-info text-dark">Classe : <?= htmlspecialchars($mod['classe_nom']) ?></span>
                            <span class="badge bg-light text-secondary">Coef : <?= htmlspecialchars($mod['coefficient']) ?></span>
                        </div>
                        <h5 class="card-title text-dark font-weight-bold"><?= htmlspecialchars($mod['nom']) ?></h5>
                        <p class="card-text text-muted mb-0 small"><i class="bi bi-clock me-1"></i> <?= htmlspecialchars($mod['nb_heures']) ?> heures de cours</p>
                    </div>
                    <?php if ($role === 'enseignant' || $role === 'administrateur'): ?>
                        <div class="card-footer bg-transparent border-0 pb-3 pt-0">
                            <a href="saisie.php?id_module=<?= $mod['id_module'] ?>" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-pencil-square me-1"></i> Saisir les notes
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="card-footer bg-transparent border-0 pb-3 pt-0">
                            <span class="badge bg-secondary w-100 py-2">Consultation uniquement</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .card-module {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card-module:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
