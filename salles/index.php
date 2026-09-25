<?php
// ========================================
// Gestion des Salles
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';
require_role(['administrateur']);

$message_success = '';
$message_error = '';

// ========== TRAITEMENT FORMULAIRE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'ajouter') {
        $nom = trim($_POST['nom'] ?? '');
        $capacite = intval($_POST['capacite'] ?? 0);

        if (!$nom || !$capacite) {
            $message_error = "Tous les champs sont obligatoires.";
        } else {
            $check_query = "SELECT id_salle FROM salles WHERE nom = ?";
            $check_stmt = mysqli_prepare($connexion, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $nom);
            mysqli_stmt_execute($check_stmt);
            $result_check = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($result_check) > 0) {
                $message_error = "Le nom de salle '$nom' existe déjà.";
            } else {
                $insert_query = "INSERT INTO salles (nom, capacite) VALUES (?, ?)";
                $insert_stmt = mysqli_prepare($connexion, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "si", $nom, $capacite);

                if (mysqli_stmt_execute($insert_stmt)) {
                    $message_success = "Salle ajoutée avec succès.";
                } else {
                    $message_error = "Erreur lors de l'ajout: " . mysqli_error($connexion);
                }
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    }

    elseif ($action === 'modifier') {
        $id_salle = intval($_POST['id_salle'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $capacite = intval($_POST['capacite'] ?? 0);

        if (!$id_salle || !$nom || !$capacite) {
            $message_error = "Données invalides.";
        } else {
            $update_query = "UPDATE salles SET nom = ?, capacite = ? WHERE id_salle = ?";
            $update_stmt = mysqli_prepare($connexion, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sii", $nom, $capacite, $id_salle);

            if (mysqli_stmt_execute($update_stmt)) {
                $message_success = "Salle modifiée avec succès.";
            } else {
                $message_error = "Erreur lors de la modification: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($update_stmt);
        }
    }

    elseif ($action === 'supprimer') {
        $id_salle = intval($_POST['id_salle'] ?? 0);

        if (!$id_salle) {
            $message_error = "ID invalide.";
        } else {
            $delete_query = "DELETE FROM salles WHERE id_salle = ?";
            $delete_stmt = mysqli_prepare($connexion, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $id_salle);

            if (mysqli_stmt_execute($delete_stmt)) {
                $message_success = "Salle supprimée avec succès.";
            } else {
                $message_error = "Erreur lors de la suppression: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// ========== RÉCUPÉRATION DONNÉES ==========
$query = "SELECT id_salle, nom, capacite FROM salles ORDER BY nom";
$result = mysqli_query($connexion, $query);

if (!$result) {
    $message_error = "Erreur lors de la récupération des données: " . mysqli_error($connexion);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Salles - SupTech Business School</title>
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
                <div class="mb-4">
                    <h1 class="h3 mb-0"><i class="bi bi-door-closed me-2"></i>Gestion des Salles</h1>
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterSalle">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter une salle
                    </button>
                </div>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>Nom de la Salle</th>
                                    <th>Capacité</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (mysqli_fetch_all($result, MYSQLI_ASSOC) as $salle): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($salle['nom']) ?></strong></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="bi bi-people-fill me-1"></i><?= $salle['capacite'] ?> places
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary" title="Modifier"
                                                    onclick="editSalle(<?= htmlspecialchars(json_encode($salle)) ?>)"
                                                    data-bs-toggle="modal" data-bs-target="#modalModifierSalle">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Supprimer"
                                                    onclick="deleteSalle(<?= $salle['id_salle'] ?>, '<?= htmlspecialchars($salle['nom']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Aucune salle trouvée.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal - Ajouter -->
    <div class="modal fade" id="modalAjouterSalle" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Ajouter une nouvelle salle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="mb-3">
                            <label class="form-label">Nom de la Salle <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" placeholder="ex: A101" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacité <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="capacite" placeholder="ex: 50" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal - Modifier -->
    <div class="modal fade" id="modalModifierSalle" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier la salle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_salle" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nom de la Salle <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacité <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_capacite" name="capacite" required>
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
    <div class="modal fade" id="modalSupprimerSalle" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Supprimer la salle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Attention!</strong> Cette action est irréversible.
                        </div>
                        <p>Êtes-vous sûr de vouloir supprimer la salle <strong id="delete_nom"></strong> ?</p>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_salle" id="delete_id">
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
        function editSalle(salle) {
            document.getElementById('edit_id').value = salle.id_salle;
            document.getElementById('edit_nom').value = salle.nom;
            document.getElementById('edit_capacite').value = salle.capacite;
        }

        function deleteSalle(id, nom) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nom').textContent = nom;
            const modal = new bootstrap.Modal(document.getElementById('modalSupprimerSalle'));
            modal.show();
        }
    </script>
</body>
</html>
