<?php
// ========================================
// Gestion des Classes
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
        $filiere = trim($_POST['filiere'] ?? '');
        $niveau = trim($_POST['niveau'] ?? '');
        $effectif_max = intval($_POST['effectif_max'] ?? 0);

        if (!$nom || !$filiere || !$niveau || !$effectif_max) {
            $message_error = "Les champs Nom, Filière, Niveau et Effectif maximum sont obligatoires.";
        } else {
            $insert_query = "INSERT INTO classes (nom, filiere, niveau, effectif_max) VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($connexion, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sssi", $nom, $filiere, $niveau, $effectif_max);

            if (mysqli_stmt_execute($insert_stmt)) {
                $message_success = "Classe ajoutée avec succès. Le nom de la classe sert aussi de filière.";
            } else {
                $message_error = "Erreur lors de l'ajout: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($insert_stmt);
        }
    }

    elseif ($action === 'modifier') {
        $id_classe = intval($_POST['id_classe'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $filiere = trim($_POST['filiere'] ?? '');
        $niveau = trim($_POST['niveau'] ?? '');
        $effectif_max = intval($_POST['effectif_max'] ?? 0);

        if (!$id_classe || !$nom || !$filiere || !$niveau || !$effectif_max) {
            $message_error = "Données invalides.";
        } else {
            $update_query = "UPDATE classes SET nom = ?, filiere = ?, niveau = ?, effectif_max = ? WHERE id_classe = ?";
            $update_stmt = mysqli_prepare($connexion, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sssii", $nom, $filiere, $niveau, $effectif_max, $id_classe);

            if (mysqli_stmt_execute($update_stmt)) {
                $message_success = "Classe modifiée avec succès.";
            } else {
                $message_error = "Erreur lors de la modification: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($update_stmt);
        }
    }

    elseif ($action === 'supprimer') {
        $id_classe = intval($_POST['id_classe'] ?? 0);

        if (!$id_classe) {
            $message_error = "ID invalide.";
        } else {
            mysqli_begin_transaction($connexion);

            try {
                // Supprimer en premier les données liées à la classe
                mysqli_query($connexion, "DELETE FROM paiements WHERE id_inscription IN (SELECT id_inscription FROM inscriptions WHERE id_classe = $id_classe)");
                mysqli_query($connexion, "DELETE FROM notes WHERE id_inscription IN (SELECT id_inscription FROM inscriptions WHERE id_classe = $id_classe)");
                mysqli_query($connexion, "DELETE FROM notes WHERE id_module IN (SELECT id_module FROM modules WHERE id_classe = $id_classe)");
                mysqli_query($connexion, "DELETE FROM emploi_temps WHERE id_module IN (SELECT id_module FROM modules WHERE id_classe = $id_classe)");
                mysqli_query($connexion, "DELETE FROM emploi_temps WHERE id_classe = $id_classe");
                mysqli_query($connexion, "DELETE FROM enseignant_module WHERE id_module IN (SELECT id_module FROM modules WHERE id_classe = $id_classe)");
                mysqli_query($connexion, "DELETE FROM inscriptions WHERE id_classe = $id_classe");
                mysqli_query($connexion, "DELETE FROM modules WHERE id_classe = $id_classe");

                $delete_query = "DELETE FROM classes WHERE id_classe = ?";
                $delete_stmt = mysqli_prepare($connexion, $delete_query);
                mysqli_stmt_bind_param($delete_stmt, "i", $id_classe);
                if (!mysqli_stmt_execute($delete_stmt)) {
                    throw new Exception(mysqli_error($connexion));
                }
                mysqli_stmt_close($delete_stmt);

                mysqli_commit($connexion);
                $message_success = "Classe supprimée avec succès.";
            } catch (Exception $e) {
                mysqli_rollback($connexion);
                $message_error = "Erreur lors de la suppression: " . $e->getMessage();
            }
        }
    }
}

// ========== RÉCUPÉRATION DONNÉES ==========
$annee_active_result = mysqli_query($connexion, "SELECT id_annee, libelle FROM annees_universitaires WHERE active = 1 LIMIT 1");
$annee_active = mysqli_fetch_assoc($annee_active_result);
$id_annee_active = $annee_active ? (int) $annee_active['id_annee'] : 0;

$query = "SELECT c.id_classe, c.nom, c.filiere, c.niveau, c.effectif_max,
                COUNT(DISTINCT i.id_inscription) AS nb_etudiants,
                GROUP_CONCAT(DISTINCT CONCAT(e.prenom, ' ', e.nom) ORDER BY e.nom, e.prenom SEPARATOR ', ') AS etudiants_nom
          FROM classes c
          LEFT JOIN inscriptions i ON i.id_classe = c.id_classe AND i.id_annee = ?
          LEFT JOIN etudiants e ON e.id_etudiant = i.id_etudiant
          GROUP BY c.id_classe, c.nom, c.filiere, c.niveau, c.effectif_max
          ORDER BY c.niveau, c.filiere, c.nom";

$stmt = mysqli_prepare($connexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_annee_active);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    $message_error = "Erreur lors de la récupération des données: " . mysqli_error($connexion);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Classes - SupTech Business School</title>
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
                    <h1 class="h3 mb-0"><i class="bi bi-building me-2"></i>Gestion des Classes</h1>
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterClasse">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter une classe
                    </button>
                </div>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="row g-4">
                        <?php foreach (mysqli_fetch_all($result, MYSQLI_ASSOC) as $classe): ?>
                            <div class="col-xl-4 col-lg-6 col-md-12">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="bi bi-building me-2"></i><?= htmlspecialchars($classe['nom']) ?></h5>
                                        <span class="badge bg-light text-primary"><?= htmlspecialchars($classe['niveau']) ?></span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <small class="text-uppercase text-muted">Filière</small>
                                            <div class="fw-semibold"><?= htmlspecialchars($classe['filiere']) ?></div>
                                        </div>
                                        <div class="mb-3 d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Effectif</span>
                                            <span class="badge bg-info-subtle text-info-emphasis"><?= (int) $classe['nb_etudiants'] ?> / <?= (int) $classe['effectif_max'] ?></span>
                                        </div>

                                        <div>
                                            <small class="text-uppercase text-muted">Étudiants inscrits</small>
                                            <?php if (!empty($classe['etudiants_nom'])): ?>
                                                <ul class="list-group list-group-flush mt-2">
                                                    <?php foreach (explode(', ', $classe['etudiants_nom']) as $etudiant): ?>
                                                        <li class="list-group-item px-0 py-1"><?= htmlspecialchars($etudiant) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="text-muted mt-2">Aucun étudiant inscrit</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-outline-primary" title="Modifier"
                                                onclick="editClasse(<?= htmlspecialchars(json_encode($classe)) ?>)"
                                                data-bs-toggle="modal" data-bs-target="#modalModifierClasse">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Supprimer"
                                                onclick="deleteClasse(<?= $classe['id_classe'] ?>, '<?= htmlspecialchars($classe['nom']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Aucune classe trouvée.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal - Ajouter -->
    <div class="modal fade" id="modalAjouterClasse" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Ajouter une nouvelle classe</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="mb-3">
                            <label class="form-label">Nom de la classe <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" placeholder="ex: Informatique" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Filière <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="filiere" placeholder="ex: Développement Web" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Niveau <span class="text-danger">*</span></label>
                            <select class="form-select" name="niveau" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="L1">L1</option>
                                <option value="L2">L2</option>
                                <option value="L3">L3</option>
                                <option value="M1">M1</option>
                                <option value="M2">M2</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Effectif Max <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="effectif_max" placeholder="ex: 50" required>
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
    <div class="modal fade" id="modalModifierClasse" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier la classe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_classe" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nom de la classe <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Filière <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_filiere" name="filiere" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Niveau <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_niveau" name="niveau" required>
                                <option value="L1">L1</option>
                                <option value="L2">L2</option>
                                <option value="L3">L3</option>
                                <option value="M1">M1</option>
                                <option value="M2">M2</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Effectif Max <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_effectif_max" name="effectif_max" required>
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
    <div class="modal fade" id="modalSupprimerClasse" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Supprimer la classe</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Attention!</strong> Cette action est irréversible.
                        </div>
                        <p>Êtes-vous sûr de vouloir supprimer la classe <strong id="delete_nom"></strong> ?</p>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_classe" id="delete_id">
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
        function editClasse(classe) {
            document.getElementById('edit_id').value = classe.id_classe;
            document.getElementById('edit_nom').value = classe.nom;
            document.getElementById('edit_niveau').value = classe.niveau;
            document.getElementById('edit_effectif_max').value = classe.effectif_max;
            if (document.getElementById('edit_filiere')) {
                document.getElementById('edit_filiere').value = classe.filiere || classe.nom;
            }
        }

        function deleteClasse(id, nom) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nom').textContent = nom;
            const modal = new bootstrap.Modal(document.getElementById('modalSupprimerClasse'));
            modal.show();
        }
    </script>
</body>
</html>
