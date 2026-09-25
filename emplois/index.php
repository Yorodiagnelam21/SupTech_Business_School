<?php
// ========================================
// Gestion de l'Emploi du Temps
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';

$message_success = '';
$message_error = '';

// ========== TRAITEMENT FORMULAIRE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'ajouter') {
        $jour = trim($_POST['jour'] ?? '');
        $heure_debut = trim($_POST['heure_debut'] ?? '');
        $heure_fin = trim($_POST['heure_fin'] ?? '');
        $id_salle = intval($_POST['id_salle'] ?? 0);
        $id_classe = intval($_POST['id_classe'] ?? 0);
        $id_enseignant = intval($_POST['id_enseignant'] ?? 0);
        $id_module = intval($_POST['id_module'] ?? 0);
        $id_annee = intval($_POST['id_annee'] ?? 0);

        if (!$jour || !$heure_debut || !$heure_fin || !$id_salle || !$id_classe || !$id_enseignant || !$id_module || !$id_annee) {
            $message_error = "Tous les champs sont obligatoires.";
        } else {
            $insert_query = "INSERT INTO emploi_temps (jour, heure_debut, heure_fin, id_salle, id_classe, id_enseignant, id_module, id_annee) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($connexion, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sssiiiii", $jour, $heure_debut, $heure_fin, $id_salle, $id_classe, $id_enseignant, $id_module, $id_annee);

            if (mysqli_stmt_execute($insert_stmt)) {
                $message_success = "Séance ajoutée avec succès.";
            } else {
                $message_error = "Erreur lors de l'ajout: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($insert_stmt);
        }
    }

    elseif ($action === 'modifier') {
        $id_seance = intval($_POST['id_seance'] ?? 0);
        $jour = trim($_POST['jour'] ?? '');
        $heure_debut = trim($_POST['heure_debut'] ?? '');
        $heure_fin = trim($_POST['heure_fin'] ?? '');
        $id_salle = intval($_POST['id_salle'] ?? 0);
        $id_classe = intval($_POST['id_classe'] ?? 0);

        if (!$id_seance || !$jour || !$heure_debut || !$heure_fin || !$id_salle || !$id_classe) {
            $message_error = "Données invalides.";
        } else {
            $update_query = "UPDATE emploi_temps 
                            SET jour = ?, heure_debut = ?, heure_fin = ?, id_salle = ?, id_classe = ? 
                            WHERE id_seance = ?";
            $update_stmt = mysqli_prepare($connexion, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sssiii", $jour, $heure_debut, $heure_fin, $id_salle, $id_classe, $id_seance);

            if (mysqli_stmt_execute($update_stmt)) {
                $message_success = "Séance modifiée avec succès.";
            } else {
                $message_error = "Erreur lors de la modification: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($update_stmt);
        }
    }

    elseif ($action === 'supprimer') {
        $id_seance = intval($_POST['id_seance'] ?? 0);

        if (!$id_seance) {
            $message_error = "ID invalide.";
        } else {
            $delete_query = "DELETE FROM emploi_temps WHERE id_seance = ?";
            $delete_stmt = mysqli_prepare($connexion, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $id_seance);

            if (mysqli_stmt_execute($delete_stmt)) {
                $message_success = "Séance supprimée avec succès.";
            } else {
                $message_error = "Erreur lors de la suppression: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// ========== RÉCUPÉRATION DONNÉES ==========
$query = "SELECT et.id_seance, et.jour, et.heure_debut, et.heure_fin, 
                 s.nom as salle, c.nom as classe, ens.nom, ens.prenom, m.nom as module, a.libelle as annee
          FROM emploi_temps et
          JOIN salles s ON et.id_salle = s.id_salle
          JOIN classes c ON et.id_classe = c.id_classe
          JOIN enseignants ens ON et.id_enseignant = ens.id_enseignant
          JOIN modules m ON et.id_module = m.id_module
          JOIN annees_universitaires a ON et.id_annee = a.id_annee
          ORDER BY FIELD(et.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), et.heure_debut";
$result = mysqli_query($connexion, $query);

if (!$result) {
    $message_error = "Erreur lors de la récupération des données: " . mysqli_error($connexion);
}

// Données pour les formulaires
$salles_query = "SELECT id_salle, nom FROM salles ORDER BY nom";
$salles = mysqli_fetch_all(mysqli_query($connexion, $salles_query), MYSQLI_ASSOC);

$classes_query = "SELECT id_classe, nom FROM classes ORDER BY nom";
$classes = mysqli_fetch_all(mysqli_query($connexion, $classes_query), MYSQLI_ASSOC);

$enseignants_query = "SELECT id_enseignant, CONCAT(nom, ' ', prenom) as nom_complet FROM enseignants ORDER BY nom, prenom";
$enseignants = mysqli_fetch_all(mysqli_query($connexion, $enseignants_query), MYSQLI_ASSOC);

$modules_query = "SELECT id_module, nom FROM modules ORDER BY nom";
$modules = mysqli_fetch_all(mysqli_query($connexion, $modules_query), MYSQLI_ASSOC);

$niveaux_annee = ['L1', 'L2', 'L3', 'M1', 'M2'];
foreach ($niveaux_annee as $niveau_annee) {
    $niveau_annee_echappe = mysqli_real_escape_string($connexion, $niveau_annee);
    mysqli_query($connexion, "INSERT IGNORE INTO annees_universitaires (libelle, date_debut, date_fin, active)
                              VALUES ('$niveau_annee_echappe', '2025-09-01', '2026-06-30', 0)");
}

$annees_query = "SELECT id_annee, libelle FROM annees_universitaires WHERE libelle IN ('L1', 'L2', 'L3', 'M1', 'M2') ORDER BY FIELD(libelle, 'L1', 'L2', 'L3', 'M1', 'M2')";
$annees = mysqli_fetch_all(mysqli_query($connexion, $annees_query), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emploi du Temps - SupTech Business School</title>
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
                    <h1 class="h3 mb-0"><i class="bi bi-calendar3 me-2"></i>Emploi du Temps</h1>
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterSeance">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter une séance
                    </button>
                </div>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>Jour</th>
                                    <th>Horaire</th>
                                    <th>Classe</th>
                                    <th>Module</th>
                                    <th>Enseignant</th>
                                    <th>Salle</th>
                                    <th>Année</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (mysqli_fetch_all($result, MYSQLI_ASSOC) as $seance): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($seance['jour']) ?></span></td>
                                        <td><?= substr($seance['heure_debut'], 0, 5) ?> - <?= substr($seance['heure_fin'], 0, 5) ?></td>
                                        <td><?= htmlspecialchars($seance['classe']) ?></td>
                                        <td><?= htmlspecialchars($seance['module']) ?></td>
                                        <td><?= htmlspecialchars($seance['nom'] . ' ' . $seance['prenom']) ?></td>
                                        <td><?= htmlspecialchars($seance['salle']) ?></td>
                                        <td><?= htmlspecialchars($seance['annee']) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary" title="Modifier"
                                                    onclick="editSeance(<?= htmlspecialchars(json_encode($seance)) ?>)"
                                                    data-bs-toggle="modal" data-bs-target="#modalModifierSeance">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Supprimer"
                                                    onclick="deleteSeance(<?= $seance['id_seance'] ?>, '<?= htmlspecialchars($seance['jour']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Aucune séance programmée.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal - Ajouter -->
    <div class="modal fade" id="modalAjouterSeance" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Ajouter une nouvelle séance</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jour <span class="text-danger">*</span></label>
                                <select class="form-select" name="jour" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="Lundi">Lundi</option>
                                    <option value="Mardi">Mardi</option>
                                    <option value="Mercredi">Mercredi</option>
                                    <option value="Jeudi">Jeudi</option>
                                    <option value="Vendredi">Vendredi</option>
                                    <option value="Samedi">Samedi</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Début <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="heure_debut" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="heure_fin" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Classe <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_classe" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id_classe'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Module <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_module" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($modules as $mod): ?>
                                    <option value="<?= $mod['id_module'] ?>"><?= htmlspecialchars($mod['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Enseignant <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_enseignant" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($enseignants as $ens): ?>
                                    <option value="<?= $ens['id_enseignant'] ?>"><?= htmlspecialchars($ens['nom_complet']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salle <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_salle" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($salles as $salle): ?>
                                        <option value="<?= $salle['id_salle'] ?>"><?= htmlspecialchars($salle['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Année <span class="text-danger">*</span></label>
                                <select class="form-select" name="id_annee" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($annees as $an): ?>
                                        <option value="<?= $an['id_annee'] ?>"><?= htmlspecialchars($an['libelle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
    <div class="modal fade" id="modalModifierSeance" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier la séance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_seance" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jour <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_jour" name="jour" required>
                                    <option value="Lundi">Lundi</option>
                                    <option value="Mardi">Mardi</option>
                                    <option value="Mercredi">Mercredi</option>
                                    <option value="Jeudi">Jeudi</option>
                                    <option value="Vendredi">Vendredi</option>
                                    <option value="Samedi">Samedi</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Début <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="edit_heure_debut" name="heure_debut" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="edit_heure_fin" name="heure_fin" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Classe <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_id_classe" name="id_classe" required>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id_classe'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Salle <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_id_salle" name="id_salle" required>
                                <?php foreach ($salles as $salle): ?>
                                    <option value="<?= $salle['id_salle'] ?>"><?= htmlspecialchars($salle['nom']) ?></option>
                                <?php endforeach; ?>
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
    <div class="modal fade" id="modalSupprimerSeance" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Supprimer la séance</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Attention!</strong> Cette action est irréversible.
                        </div>
                        <p>Êtes-vous sûr de vouloir supprimer la séance du <strong id="delete_jour"></strong> ?</p>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_seance" id="delete_id">
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
        function editSeance(seance) {
            document.getElementById('edit_id').value = seance.id_seance;
            document.getElementById('edit_jour').value = seance.jour;
            document.getElementById('edit_heure_debut').value = seance.heure_debut;
            document.getElementById('edit_heure_fin').value = seance.heure_fin;
            document.getElementById('edit_id_classe').value = seance.id_classe || '';
            document.getElementById('edit_id_salle').value = seance.id_salle || '';
        }

        function deleteSeance(id, jour) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_jour').textContent = jour;
            const modal = new bootstrap.Modal(document.getElementById('modalSupprimerSeance'));
            modal.show();
        }
    </script>
</body>
</html>
