<?php
// ========================================
// Gestion des Paiements
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';
require_role(['administrateur', 'scolarite']);

$message_success = '';
$message_error = '';

// ========== TRAITEMENT FORMULAIRE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'ajouter') {
        $id_inscription = intval($_POST['id_inscription'] ?? 0);
        $montant_paye = floatval($_POST['montant_paye'] ?? 0);
        $montant_total = floatval($_POST['montant_total'] ?? 0);
        $date_paiement = trim($_POST['date_paiement'] ?? '');
        $mode_paiement = trim($_POST['mode_paiement'] ?? '');

        if (!$id_inscription || !$montant_paye || !$montant_total || !$date_paiement) {
            $message_error = "Les champs obligatoires doivent être remplis.";
        } elseif ($montant_paye > $montant_total) {
            $message_error = "Le montant versé ne peut pas dépasser le montant total dû.";
        } else {
            $insert_query = "INSERT INTO paiements (id_inscription, montant_paye, montant_total, date_paiement, mode_paiement) 
                             VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($connexion, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "iddss", $id_inscription, $montant_paye, $montant_total, $date_paiement, $mode_paiement);

            if (mysqli_stmt_execute($insert_stmt)) {
                $message_success = "Paiement enregistré avec succès.";
            } else {
                $message_error = "Erreur lors de l'enregistrement: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($insert_stmt);
        }
    }

    elseif ($action === 'modifier') {
        $id_paiement = intval($_POST['id_paiement'] ?? 0);
        $montant_paye = floatval($_POST['montant_paye'] ?? 0);
        $montant_total = floatval($_POST['montant_total'] ?? 0);
        $date_paiement = trim($_POST['date_paiement'] ?? '');
        $mode_paiement = trim($_POST['mode_paiement'] ?? '');

        if (!$id_paiement || !$montant_paye || !$montant_total) {
            $message_error = "Données invalides.";
        } elseif ($montant_paye > $montant_total) {
            $message_error = "Le montant versé ne peut pas dépasser le montant total dû.";
        } else {
            $update_query = "UPDATE paiements 
                            SET montant_paye = ?, montant_total = ?, date_paiement = ?, mode_paiement = ? 
                            WHERE id_paiement = ?";
            $update_stmt = mysqli_prepare($connexion, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ddssi", $montant_paye, $montant_total, $date_paiement, $mode_paiement, $id_paiement);

            if (mysqli_stmt_execute($update_stmt)) {
                $message_success = "Paiement modifié avec succès.";
            } else {
                $message_error = "Erreur lors de la modification: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($update_stmt);
        }
    }

    elseif ($action === 'supprimer') {
        $id_paiement = intval($_POST['id_paiement'] ?? 0);

        if (!$id_paiement) {
            $message_error = "ID invalide.";
        } else {
            $delete_query = "DELETE FROM paiements WHERE id_paiement = ?";
            $delete_stmt = mysqli_prepare($connexion, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $id_paiement);

            if (mysqli_stmt_execute($delete_stmt)) {
                $message_success = "Paiement supprimé avec succès.";
            } else {
                $message_error = "Erreur lors de la suppression: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// ========== RÉCUPÉRATION DONNÉES ==========
$query = "SELECT p.id_paiement, p.montant_paye, p.montant_total, p.date_paiement, p.mode_paiement,
                 p.montant_total - (SELECT COALESCE(SUM(p2.montant_paye), 0) FROM paiements p2 WHERE p2.id_inscription = p.id_inscription) AS reste,
                 i.id_inscription, e.nom, e.prenom, c.nom as classe
          FROM paiements p
          JOIN inscriptions i ON p.id_inscription = i.id_inscription
          JOIN etudiants e ON i.id_etudiant = e.id_etudiant
          JOIN classes c ON i.id_classe = c.id_classe
          ORDER BY p.date_paiement DESC";
$result = mysqli_query($connexion, $query);

if (!$result) {
    $message_error = "Erreur lors de la récupération des données: " . mysqli_error($connexion);
}

// Récupérer les inscriptions pour le formulaire
$inscriptions_query = "SELECT i.id_inscription, CONCAT(e.nom, ' ', e.prenom, ' - ', c.nom) as label
                       FROM inscriptions i
                       JOIN etudiants e ON i.id_etudiant = e.id_etudiant
                       JOIN classes c ON i.id_classe = c.id_classe
                       ORDER BY e.nom, e.prenom";
$inscriptions_result = mysqli_query($connexion, $inscriptions_query);
$inscriptions = mysqli_fetch_all($inscriptions_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Paiements - SupTech Business School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/gestion_academique/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="d-flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-grow-1 p-4">
            <div class="container-fluid">
                <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h1 class="h3 mb-0"><i class="bi bi-cash-coin me-2"></i>Gestion des Paiements</h1>
                    <a href="export_csv.php" class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-2"></i>Exporter Excel
                    </a>
                </div>

                <?php if ($message_success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message_success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($message_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($message_error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterPaiement">
                        <i class="bi bi-plus-circle me-2"></i>Enregistrer un paiement
                    </button>
                </div>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Montant Payé</th>
                                    <th>Montant Total</th>
                                    <th>Reste</th>
                                    <th>Mode de Paiement</th>
                                    <th>Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (mysqli_fetch_all($result, MYSQLI_ASSOC) as $paiement): ?>
                                    <?php 
                                        $reste = max(0, (float) $paiement['reste']);
                                        $couleur_reste = $reste <= 0 ? 'bg-success' : ($reste < $paiement['montant_total'] * 0.3 ? 'bg-warning' : 'bg-danger');
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($paiement['nom'] . ' ' . $paiement['prenom']) ?></td>
                                        <td><?= htmlspecialchars($paiement['classe']) ?></td>
                                        <td><strong><?= number_format($paiement['montant_paye'], 2, ',', ' ') ?> XOF</strong></td>
                                        <td><?= number_format($paiement['montant_total'], 2, ',', ' ') ?> XOF</td>
                                        <td><span class="badge <?= $couleur_reste ?>"><?= number_format($reste, 2, ',', ' ') ?> XOF</span></td>
                                        <td><?= htmlspecialchars($paiement['mode_paiement'] ?? '-') ?></td>
                                        <td><?= date('d/m/Y', strtotime($paiement['date_paiement'])) ?></td>
                                        <td class="text-center">
                                            <a class="btn btn-sm btn-outline-secondary" title="Télécharger le reçu"
                                               href="recu_pdf.php?id_paiement=<?= $paiement['id_paiement'] ?>">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-primary" title="Modifier"
                                                    onclick="editPaiement(<?= htmlspecialchars(json_encode($paiement)) ?>)"
                                                    data-bs-toggle="modal" data-bs-target="#modalModifierPaiement">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Supprimer"
                                                    onclick="deletePaiement(<?= $paiement['id_paiement'] ?>, '<?= htmlspecialchars($paiement['nom']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Aucun paiement enregistré.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal - Ajouter -->
    <div class="modal fade" id="modalAjouterPaiement" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Enregistrer un nouveau paiement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="mb-3">
                            <label class="form-label">Inscription (Étudiant - Classe) <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_inscription" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($inscriptions as $inscr): ?>
                                    <option value="<?= $inscr['id_inscription'] ?>"><?= htmlspecialchars($inscr['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant Payé (XOF) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="montant_paye" placeholder="ex: 50000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant Total (XOF) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="montant_total" placeholder="ex: 150000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date de Paiement <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_paiement" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mode de Paiement</label>
                            <select class="form-select" name="mode_paiement">
                                <option value="">-- Sélectionner --</option>
                                <option value="Espèces">Espèces</option>
                                <option value="Chèque">Chèque</option>
                                <option value="Virement">Virement</option>
                                <option value="Orange Money">Orange Money</option>
                                <option value="Wave">Wave</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal - Modifier -->
    <div class="modal fade" id="modalModifierPaiement" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier le paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_paiement" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Montant Payé (XOF) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="edit_montant_paye" name="montant_paye" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant Total (XOF) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="edit_montant_total" name="montant_total" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date de Paiement <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_date_paiement" name="date_paiement" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mode de Paiement</label>
                            <select class="form-select" id="edit_mode_paiement" name="mode_paiement">
                                <option value="">-- Sélectionner --</option>
                                <option value="Espèces">Espèces</option>
                                <option value="Chèque">Chèque</option>
                                <option value="Virement">Virement</option>
                                <option value="Orange Money">Orange Money</option>
                                <option value="Wave">Wave</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i>Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal - Supprimer -->
    <div class="modal fade" id="modalSupprimerPaiement" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Supprimer le paiement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Attention!</strong> Cette action est irréversible.
                        </div>
                        <p>Êtes-vous sûr de vouloir supprimer ce paiement de <strong id="delete_nom"></strong> ?</p>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_paiement" id="delete_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Supprimer définitivement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPaiement(paiement) {
            document.getElementById('edit_id').value = paiement.id_paiement;
            document.getElementById('edit_montant_paye').value = paiement.montant_paye;
            document.getElementById('edit_montant_total').value = paiement.montant_total;
            document.getElementById('edit_date_paiement').value = paiement.date_paiement;
            document.getElementById('edit_mode_paiement').value = paiement.mode_paiement || '';
        }

        function deletePaiement(id, nom) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nom').textContent = nom;
            const modal = new bootstrap.Modal(document.getElementById('modalSupprimerPaiement'));
            modal.show();
        }
    </script>
</body>
</html>
