<?php
// ========================================
// Tableau de Bord des Statistiques
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';
require_role(['administrateur', 'scolarite']);

// ========== STATISTIQUES GLOBALES ==========

// 1. Nombre d'étudiants
$etudiants_query = "SELECT COUNT(*) as total FROM etudiants";
$etudiants_result = mysqli_query($connexion, $etudiants_query);
$total_etudiants = mysqli_fetch_assoc($etudiants_result)['total'];

// 2. Nombre de classes
$classes_query = "SELECT COUNT(*) as total FROM classes";
$classes_result = mysqli_query($connexion, $classes_query);
$total_classes = mysqli_fetch_assoc($classes_result)['total'];

// 3. Nombre d'enseignants
$enseignants_query = "SELECT COUNT(*) as total FROM enseignants";
$enseignants_result = mysqli_query($connexion, $enseignants_query);
$total_enseignants = mysqli_fetch_assoc($enseignants_result)['total'];

// 4. Nombre de modules
$modules_query = "SELECT COUNT(*) as total FROM modules";
$modules_result = mysqli_query($connexion, $modules_query);
$total_modules = mysqli_fetch_assoc($modules_result)['total'];

// 5. Statistiques par sexe
$sexe_query = "SELECT sexe, COUNT(*) as count FROM etudiants GROUP BY sexe";
$sexe_result = mysqli_query($connexion, $sexe_query);
$sexe_stats = array('M' => 0, 'F' => 0);
while ($row = mysqli_fetch_assoc($sexe_result)) {
    $sexe_stats[$row['sexe']] = $row['count'];
}

// 6. Statistiques de notes
$notes_query = "SELECT COUNT(*) as total, AVG(moyenne) as moyenne FROM notes WHERE moyenne IS NOT NULL";
$notes_result = mysqli_query($connexion, $notes_query);
$notes_stats = mysqli_fetch_assoc($notes_result);

// 7. Top 5 meilleurs étudiants
$top_query = "SELECT e.nom, e.prenom, AVG(n.moyenne) as moyenne_generale
             FROM etudiants e
             JOIN inscriptions i ON e.id_etudiant = i.id_etudiant
             JOIN notes n ON i.id_inscription = n.id_inscription
             WHERE n.moyenne IS NOT NULL
             GROUP BY e.id_etudiant
             ORDER BY moyenne_generale DESC
             LIMIT 5";
$top_result = mysqli_query($connexion, $top_query);
$top_students = mysqli_fetch_all($top_result, MYSQLI_ASSOC);

// 8. Distribution des notes
$distribution_query = "SELECT 
                        SUM(CASE WHEN moyenne >= 16 THEN 1 ELSE 0 END) as excellent,
                        SUM(CASE WHEN moyenne >= 14 AND moyenne < 16 THEN 1 ELSE 0 END) as tresbien,
                        SUM(CASE WHEN moyenne >= 12 AND moyenne < 14 THEN 1 ELSE 0 END) as bien,
                        SUM(CASE WHEN moyenne >= 10 AND moyenne < 12 THEN 1 ELSE 0 END) as assez,
                        SUM(CASE WHEN moyenne < 10 THEN 1 ELSE 0 END) as faible
                      FROM notes WHERE moyenne IS NOT NULL";
$distribution_result = mysqli_query($connexion, $distribution_query);
$distribution = mysqli_fetch_assoc($distribution_result);

// 9. Taux de paiement
$paiement_query = "SELECT
                    SUM(CASE WHEN total_du - total_paye <= 0 THEN 1 ELSE 0 END) as complet,
                    SUM(CASE WHEN total_du - total_paye > 0 THEN 1 ELSE 0 END) as partiel
                   FROM (
                       SELECT id_inscription, MAX(montant_total) AS total_du, SUM(montant_paye) AS total_paye
                       FROM paiements
                       GROUP BY id_inscription
                   ) paiements_inscriptions";
$paiement_result = mysqli_query($connexion, $paiement_query);
$paiement_stats = mysqli_fetch_assoc($paiement_result);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - SupTech Business School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/gestion_academique/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="d-flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-grow-1 p-4">
            <div class="container-fluid">
                <!-- Titre -->
                <div class="mb-4">
                    <h1 class="h3 mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Statistiques et Tableaux de Bord
                    </h1>
                </div>

                <!-- Cartes KPI -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title text-muted">Étudiants</h5>
                                <h2 class="text-primary"><?= $total_etudiants ?></h2>
                                <i class="bi bi-people-fill" style="font-size: 2rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h5 class="card-title text-muted">Classes</h5>
                                <h2 class="text-success"><?= $total_classes ?></h2>
                                <i class="bi bi-building" style="font-size: 2rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h5 class="card-title text-muted">Enseignants</h5>
                                <h2 class="text-info"><?= $total_enseignants ?></h2>
                                <i class="bi bi-person-badge" style="font-size: 2rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h5 class="card-title text-muted">Modules</h5>
                                <h2 class="text-warning"><?= $total_modules ?></h2>
                                <i class="bi bi-book-fill" style="font-size: 2rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row mb-4">
                    <!-- Distribution Sexe -->
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Distribution par Sexe</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartSexe" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Distribution Notes -->
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Répartition des Notes</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartNotes" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques Détaillées -->
                <div class="row mb-4">
                    <!-- Notes Statistiques -->
                    <div class="col-md-6 mb-3" style="max-width: 100%;">
                        <div class="card w-100">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Statistiques de Notes</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td><strong>Nombre de notes</strong></td>
                                        <td class="text-end"><?= $notes_stats['total'] ?? 0 ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Moyenne Générale</strong></td>
                                        <td class="text-end">
                                            <strong><?= round($notes_stats['moyenne'] ?? 0, 2) ?>/20</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success">Excellent (≥16)</span></td>
                                        <td class="text-end"><?= $distribution['excellent'] ?? 0 ?> étudiants</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-info">Très Bien (14-16)</span></td>
                                        <td class="text-end"><?= $distribution['tresbien'] ?? 0 ?> étudiants</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-primary">Bien (12-14)</span></td>
                                        <td class="text-end"><?= $distribution['bien'] ?? 0 ?> étudiants</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-warning">Assez (10-12)</span></td>
                                        <td class="text-end"><?= $distribution['assez'] ?? 0 ?> étudiants</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-danger">Faible (<10)</span></td>
                                        <td class="text-end"><?= $distribution['faible'] ?? 0 ?> étudiants</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Taux de Paiement -->
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Taux de Paiement</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                    $total_paiements = ($paiement_stats['complet'] ?? 0) + ($paiement_stats['partiel'] ?? 0);
                                    $taux_complete = $total_paiements > 0 ? round(($paiement_stats['complet'] ?? 0) / $total_paiements * 100, 1) : 0;
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><strong>Paiements Complets</strong></span>
                                        <span class="badge bg-success"><?= $taux_complete ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $taux_complete ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= $paiement_stats['complet'] ?? 0 ?> sur <?= $total_paiements ?> inscriptions</small>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><strong>Paiements Partiels</strong></span>
                                        <span class="badge bg-warning"><?= round(100 - $taux_complete, 1) ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= round(100 - $taux_complete, 1) ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= $paiement_stats['partiel'] ?? 0 ?> sur <?= $total_paiements ?> inscriptions</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Meilleurs Étudiants -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="bi bi-award me-2"></i>Top 5 Meilleurs Étudiants</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($top_students)): ?>
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Rang</th>
                                                <th>Étudiant</th>
                                                <th>Moyenne Générale</th>
                                                <th>Performance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_students as $index => $student): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge" style="background-color: <?= ['#FFD700', '#C0C0C0', '#CD7F32', '#9370DB', '#4B0082'][$index] ?>;">
                                                            <?= $index + 1 ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($student['nom'] . ' ' . $student['prenom']) ?></td>
                                                    <td><strong><?= round($student['moyenne_generale'], 2) ?>/20</strong></td>
                                                    <td>
                                                        <div class="progress" style="height: 1.5rem;">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                 style="width: <?= ($student['moyenne_generale'] / 20 * 100) ?>%">
                                                                <?= round($student['moyenne_generale'] / 20 * 100, 0) ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-info">Aucune donnée de notes disponible.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart Sexe
        const ctxSexe = document.getElementById('chartSexe').getContext('2d');
        new Chart(ctxSexe, {
            type: 'doughnut',
            data: {
                labels: ['Masculin', 'Féminin'],
                datasets: [{
                    data: [<?= $sexe_stats['M'] ?>, <?= $sexe_stats['F'] ?>],
                    backgroundColor: ['#0d6efd', '#d946ef'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Chart Distribution Notes
        const ctxNotes = document.getElementById('chartNotes').getContext('2d');
        new Chart(ctxNotes, {
            type: 'bar',
            data: {
                labels: ['Excellent\n(≥16)', 'Très Bien\n(14-16)', 'Bien\n(12-14)', 'Assez\n(10-12)', 'Faible\n(<10)'],
                datasets: [{
                    label: 'Nombre d\'étudiants',
                    data: [
                        <?= $distribution['excellent'] ?? 0 ?>,
                        <?= $distribution['tresbien'] ?? 0 ?>,
                        <?= $distribution['bien'] ?? 0 ?>,
                        <?= $distribution['assez'] ?? 0 ?>,
                        <?= $distribution['faible'] ?? 0 ?>
                    ],
                    backgroundColor: ['#198754', '#0dcaf0', '#0d6efd', '#ffc107', '#dc3545'],
                    borderRadius: 5,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
