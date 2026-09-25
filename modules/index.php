<?php
// ========================================
// Gestion des Modules (d'enseignement)
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';

$message_success = '';
$message_error = '';
$MAX_MODULES_PAR_CLASSE = 10;

// ========== TRAITEMENT FORMULAIRE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'ajouter') {
        $nom = trim($_POST['nom'] ?? '');
        $id_classe = intval($_POST['id_classe'] ?? 0);
        $coefficient = floatval($_POST['coefficient'] ?? 0);
        $nb_heures = intval($_POST['nb_heures'] ?? 0);

        if (!$nom || !$id_classe || !$coefficient || !$nb_heures) {
            $message_error = "Tous les champs sont obligatoires.";
        } else {
            $count_query = mysqli_query($connexion, "SELECT COUNT(*) AS total FROM modules WHERE id_classe = $id_classe");
            $count_row = mysqli_fetch_assoc($count_query);
            $nb_modules_classe = (int) ($count_row['total'] ?? 0);

            if ($nb_modules_classe >= $MAX_MODULES_PAR_CLASSE) {
                $message_error = "Cette classe a déjà atteint le maximum de {$MAX_MODULES_PAR_CLASSE} matières. Impossible d'ajouter un nouveau module.";
            } else {
                $insert_query = "INSERT INTO modules (nom, id_classe, coefficient, nb_heures) VALUES (?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($connexion, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "sidi", $nom, $id_classe, $coefficient, $nb_heures);

                if (mysqli_stmt_execute($insert_stmt)) {
                    $message_success = "Module ajouté avec succès.";
                } else {
                    $message_error = "Erreur lors de l'ajout: " . mysqli_error($connexion);
                }
                mysqli_stmt_close($insert_stmt);
            }
        }
    }

    elseif ($action === 'modifier') {
        $id_module = intval($_POST['id_module'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $id_classe = intval($_POST['id_classe'] ?? 0);
        $coefficient = floatval($_POST['coefficient'] ?? 0);
        $nb_heures = intval($_POST['nb_heures'] ?? 0);

        if (!$id_module || !$nom || !$id_classe || !$coefficient || !$nb_heures) {
            $message_error = "Données invalides.";
        } else {
            $count_query = mysqli_query($connexion, "SELECT COUNT(*) AS total FROM modules WHERE id_classe = $id_classe AND id_module != $id_module");
            $count_row = mysqli_fetch_assoc($count_query);
            $nb_modules_classe = (int) ($count_row['total'] ?? 0);

            if ($nb_modules_classe >= $MAX_MODULES_PAR_CLASSE) {
                $message_error = "Cette classe a déjà atteint le maximum de {$MAX_MODULES_PAR_CLASSE} matières. Vous devez en supprimer une avant d'en ajouter une autre.";
            } else {
                $update_query = "UPDATE modules SET nom = ?, id_classe = ?, coefficient = ?, nb_heures = ? WHERE id_module = ?";
                $update_stmt = mysqli_prepare($connexion, $update_query);
                mysqli_stmt_bind_param($update_stmt, "sidii", $nom, $id_classe, $coefficient, $nb_heures, $id_module);

                if (mysqli_stmt_execute($update_stmt)) {
                    $message_success = "Module modifié avec succès.";
                } else {
                    $message_error = "Erreur lors de la modification: " . mysqli_error($connexion);
                }
                mysqli_stmt_close($update_stmt);
            }
        }
    }

    elseif ($action === 'supprimer') {
        $id_module = intval($_POST['id_module'] ?? 0);

        if (!$id_module) {
            $message_error = "ID invalide.";
        } else {
            $delete_query = "DELETE FROM modules WHERE id_module = ?";
            $delete_stmt = mysqli_prepare($connexion, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $id_module);

            if (mysqli_stmt_execute($delete_stmt)) {
                $message_success = "Module supprimé avec succès.";
            } else {
                $message_error = "Erreur lors de la suppression: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// ========== RÉCUPÉRATION DONNÉES ==========
$query = "SELECT m.id_module, m.nom, m.coefficient, m.nb_heures, c.nom as classe_nom, c.id_classe 
          FROM modules m 
          JOIN classes c ON m.id_classe = c.id_classe 
          ORDER BY c.nom, m.nom";
$result = mysqli_query($connexion, $query);

if (!$result) {
    $message_error = "Erreur lors de la récupération des données: " . mysqli_error($connexion);
}

// Récupérer les classes pour le formulaire
$classes_query = "SELECT id_classe, nom FROM classes ORDER BY nom";
$classes_result = mysqli_query($connexion, $classes_query);
$classes = mysqli_fetch_all($classes_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Modules - SupTech Business School</title>
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
                    <h1 class="h3 mb-0"><i class="bi bi-book-fill me-2"></i>Gestion des Modules</h1>
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterModule">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter un module
                    </button>
                </div>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>Nom du Module</th>
                                    <th>Classe</th>
                                    <th>Coefficient</th>
                                    <th>Nombre d'heures</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (mysqli_fetch_all($result, MYSQLI_ASSOC) as $mod): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($mod['nom']) ?></strong></td>
                                        <td><?= htmlspecialchars($mod['classe_nom']) ?></td>
                                        <td><span class="badge bg-success"><?= $mod['coefficient'] ?></span></td>
                                        <td><?= $mod['nb_heures'] ?>h</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary" title="Modifier"
                                                    onclick="editModule(<?= htmlspecialchars(json_encode($mod)) ?>)"
                                                    data-bs-toggle="modal" data-bs-target="#modalModifierModule">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Supprimer"
                                                    onclick="deleteModule(<?= $mod['id_module'] ?>, '<?= htmlspecialchars($mod['nom']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Aucun module trouvé.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal - Ajouter -->
    <div class="modal fade" id="modalAjouterModule" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Ajouter un nouveau module</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="mb-3">
                            <label class="form-label">Nom du Module <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" placeholder="ex: Algorithmique" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Classe <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_classe" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Coefficient <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" class="form-control" name="coefficient" placeholder="ex: 1.5" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre d'heures <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="nb_heures" placeholder="ex: 30" required>
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
    <div class="modal fade" id="modalModifierModule" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier le module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_module" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nom du Module <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Classe <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_id_classe" name="id_classe" required>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Coefficient <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" class="form-control" id="edit_coefficient" name="coefficient" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre d'heures <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_nb_heures" name="nb_heures" required>
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
    <div class="modal fade" id="modalSupprimerModule" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Supprimer le module</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Attention!</strong> Cette action est irréversible.
                        </div>
                        <p>Êtes-vous sûr de vouloir supprimer le module <strong id="delete_nom"></strong> ?</p>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_module" id="delete_id">
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
        function editModule(module) {
            document.getElementById('edit_id').value = module.id_module;
            document.getElementById('edit_nom').value = module.nom;
            document.getElementById('edit_id_classe').value = module.id_classe;
            document.getElementById('edit_coefficient').value = module.coefficient;
            document.getElementById('edit_nb_heures').value = module.nb_heures;
        }

        function deleteModule(id, nom) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nom').textContent = nom;
            const modal = new bootstrap.Modal(document.getElementById('modalSupprimerModule'));
            modal.show();
        }
    </script>
</body>
</html>
