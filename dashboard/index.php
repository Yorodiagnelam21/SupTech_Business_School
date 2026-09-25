<?php
// dashboard/index.php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: ../index.php');
    exit;
}

// Vérification des droits d'accès
$role = $_SESSION['role_libelle'] ?? '';
if ($role === 'enseignant') {
    // Les enseignants sont redirigés vers leur espace de notes
    header('Location: ../notes/index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// ============================================================
// RÉCUPÉRATION DES STATISTIQUES DEPUIS LA BDD
// ============================================================

// 1. Nombre d'étudiants
$nb_etudiants = 0;
if ($res = mysqli_query($connexion, "SELECT COUNT(*) as total FROM etudiants")) {
    $nb_etudiants = mysqli_fetch_assoc($res)['total'];
}

// 2. Nombre d'enseignants
$nb_enseignants = 0;
if ($res = mysqli_query($connexion, "SELECT COUNT(*) as total FROM enseignants")) {
    $nb_enseignants = mysqli_fetch_assoc($res)['total'];
}

// 3. Nombre de classes
$nb_classes = 0;
if ($res = mysqli_query($connexion, "SELECT COUNT(*) as total FROM classes")) {
    $nb_classes = mysqli_fetch_assoc($res)['total'];
}

// 4. Nombre de modules
$nb_modules = 0;
if ($res = mysqli_query($connexion, "SELECT COUNT(*) as total FROM modules")) {
    $nb_modules = mysqli_fetch_assoc($res)['total'];
}

// 5. Nombre d'inscriptions
$nb_inscriptions = 0;
if ($res = mysqli_query($connexion, "SELECT COUNT(*) as total FROM inscriptions")) {
    $nb_inscriptions = mysqli_fetch_assoc($res)['total'];
}

// 6. Nombre de paiements
$nb_paiements = 0;
if ($res = mysqli_query($connexion, "SELECT COUNT(*) as total FROM paiements")) {
    $nb_paiements = mysqli_fetch_assoc($res)['total'];
}

// 7. Taux de réussite (Calculé sur les notes avec moyenne >= 10)
$taux_reussite = 0;
$total_notes_res = mysqli_query($connexion, "SELECT COUNT(*) as total FROM notes");
if ($total_notes_res) {
    $total_notes = mysqli_fetch_assoc($total_notes_res)['total'];
    if ($total_notes > 0) {
        $admis_res = mysqli_query($connexion, "SELECT COUNT(*) as admis FROM notes WHERE moyenne >= 10");
        $admis = mysqli_fetch_assoc($admis_res)['admis'];
        $taux_reussite = round(($admis / $total_notes) * 100);
    }
}

// 8. Derniers étudiants inscrits
$derniers_etudiants = [];
$query_etud = "SELECT e.matricule, e.nom, e.prenom, c.nom as classe_nom, i.date_inscription 
               FROM inscriptions i 
               JOIN etudiants e ON i.id_etudiant = e.id_etudiant 
               JOIN classes c ON i.id_classe = c.id_classe 
               ORDER BY i.id_inscription DESC LIMIT 5";
if ($res_etud = mysqli_query($connexion, $query_etud)) {
    while ($row = mysqli_fetch_assoc($res_etud)) {
        $derniers_etudiants[] = $row;
    }
}

// 9. Dernières notes enregistrées
$dernieres_notes = [];
$query_notes = "SELECT e.nom as etudiant_nom, e.prenom as etudiant_prenom, m.nom as module_nom, n.moyenne, n.mention 
                FROM notes n 
                JOIN inscriptions i ON n.id_inscription = i.id_inscription 
                JOIN etudiants e ON i.id_etudiant = e.id_etudiant 
                JOIN modules m ON n.id_module = m.id_module 
                ORDER BY n.id_note DESC LIMIT 5";
if ($res_notes = mysqli_query($connexion, $query_notes)) {
    while ($row = mysqli_fetch_assoc($res_notes)) {
        $dernieres_notes[] = $row;
    }
}

// Inclusion de l'entête
require_once __DIR__ . '/../includes/header.php';
// Inclusion de la barre latérale
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tableau de bord</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-primary px-3 py-2 fs-6">
            <i class="bi bi-person-badge-fill me-1"></i>
            <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?> (<?= htmlspecialchars($_SESSION['role_libelle']) ?>)
        </span>
    </div>
</div>

<!-- Alertes et messages de bienvenue -->
<div class="alert alert-light border-0 shadow-sm p-4 mb-4 bg-body rounded d-flex align-items-center justify-content-between">
    <div>
        <h4 class="alert-heading text-primary mb-1">Bienvenue sur le portail SupTech !</h4>
        <p class="text-muted mb-0">Vous êtes connecté en tant que <strong><?= htmlspecialchars($_SESSION['role_libelle']) ?></strong>. Vous pouvez gérer l'ensemble de l'établissement à l'aide du menu latéral.</p>
    </div>
    <div class="d-none d-lg-block">
        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
    </div>
</div>

<!-- Cartes Statistiques -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 mb-4">
    <!-- Étudiants -->
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-primary bg-gradient text-white">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="card-title text-uppercase opacity-75 mb-2" style="font-size: 0.8rem;">Étudiants</h6>
                    <h3 class="card-text mb-0 font-weight-bold"><?= $nb_etudiants ?></h3>
                </div>
                <div>
                    <i class="bi bi-people-fill fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 opacity-75 pb-3">
                <a href="../etudiants/" class="text-white text-decoration-none small">Gérer les étudiants <i class="bi bi-arrow-right-short"></i></a>
            </div>
        </div>
    </div>

    <!-- Enseignants -->
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-success bg-gradient text-white">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="card-title text-uppercase opacity-75 mb-2" style="font-size: 0.8rem;">Enseignants</h6>
                    <h3 class="card-text mb-0 font-weight-bold"><?= $nb_enseignants ?></h3>
                </div>
                <div>
                    <i class="bi bi-person-video3 fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 opacity-75 pb-3">
                <a href="../enseignants/" class="text-white text-decoration-none small">Voir la liste <i class="bi bi-arrow-right-short"></i></a>
            </div>
        </div>
    </div>

    <!-- Classes & Modules -->
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-warning bg-gradient text-white">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="card-title text-uppercase opacity-75 mb-2" style="font-size: 0.8rem;">Classes / Modules</h6>
                    <h3 class="card-text mb-0 font-weight-bold"><?= $nb_classes ?> / <?= $nb_modules ?></h3>
                </div>
                <div>
                    <i class="bi bi-building fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 opacity-75 pb-3">
                <a href="../classes/" class="text-white text-decoration-none small">Voir les classes <i class="bi bi-arrow-right-short"></i></a>
            </div>
        </div>
    </div>

    <!-- Inscriptions & Taux Réussite -->
    <div class="col">
        <div class="card h-100 border-0 shadow-sm bg-danger bg-gradient text-white">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="card-title text-uppercase opacity-75 mb-2" style="font-size: 0.8rem;">Inscriptions (Taux Réussite)</h6>
                    <h3 class="card-text mb-0 font-weight-bold"><?= $nb_inscriptions ?> (<?= $taux_reussite ?>%)</h3>
                </div>
                <div>
                    <i class="bi bi-card-checklist fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 opacity-75 pb-3">
                <a href="../statistiques/" class="text-white text-decoration-none small">Voir les statistiques <i class="bi bi-arrow-right-short"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Derniers étudiants inscrits -->
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title text-dark mb-0 font-weight-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i>Derniers étudiants inscrits</h5>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Matricule</th>
                                <th>Nom Complet</th>
                                <th>Classe</th>
                                <th>Date Inscription</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($derniers_etudiants)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Aucun étudiant inscrit récemment.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($derniers_etudiants as $etud): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($etud['matricule']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($etud['nom']) ?></strong> <?= htmlspecialchars($etud['prenom']) ?></td>
                                        <td><?= htmlspecialchars($etud['classe_nom']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($etud['date_inscription'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernières notes enregistrées -->
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title text-dark mb-0 font-weight-bold"><i class="bi bi-file-earmark-bar-graph-fill text-success me-2"></i>Dernières notes enregistrées</h5>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Étudiant</th>
                                <th>Module</th>
                                <th>Moyenne</th>
                                <th>Mention</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dernieres_notes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Aucune note enregistrée récemment.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dernieres_notes as $note): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($note['etudiant_nom']) ?> <?= htmlspecialchars($note['etudiant_prenom']) ?></td>
                                        <td><?= htmlspecialchars($note['module_nom']) ?></td>
                                        <td>
                                            <span class="badge <?= $note['moyenne'] >= 10 ? 'bg-success' : 'bg-danger' ?> fs-6">
                                                <?= number_format($note['moyenne'], 2) ?>/20
                                            </span>
                                        </td>
                                        <td><span class="text-muted"><?= htmlspecialchars($note['mention']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../includes/footer.php';
?>
