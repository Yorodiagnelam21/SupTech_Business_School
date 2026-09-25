<?php
// ========================================
// Gestion des Enseignants
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

$modules_disponibles = [];
$modules_query = mysqli_query($connexion, "SELECT m.id_module, m.nom, c.nom AS classe_nom
    FROM modules m
    LEFT JOIN classes c ON c.id_classe = m.id_classe
    ORDER BY c.nom, m.nom");
if ($modules_query) {
    while ($module = mysqli_fetch_assoc($modules_query)) {
        $modules_disponibles[] = $module;
    }
}

// ========== TRAITEMENT FORMULAIRE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'ajouter') {
        $matricule = trim($_POST['matricule'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $grade = trim($_POST['grade'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $specialite = trim($_POST['specialite'] ?? '');
        $id_module = intval($_POST['id_module'] ?? 0);

        if (!$matricule || !$nom || !$prenom || !$id_module) {
            $message_error = "Les champs Matricule, Nom, Prénom et Matière assignée sont obligatoires.";
        } elseif (empty($modules_disponibles)) {
            $message_error = "Aucune matière n'est disponible. Créez d'abord une matière avant d'ajouter un enseignant.";
        } else {
            $module_existe = mysqli_query($connexion, "SELECT id_module FROM modules WHERE id_module = $id_module LIMIT 1");
            if (!$module_existe || mysqli_num_rows($module_existe) === 0) {
                $message_error = "La matière sélectionnée est invalide.";
            } else {
                $check_query = "SELECT id_enseignant FROM enseignants WHERE matricule = ?";
                $check_stmt = mysqli_prepare($connexion, $check_query);
                mysqli_stmt_bind_param($check_stmt, "s", $matricule);
                mysqli_stmt_execute($check_stmt);
                $result = mysqli_stmt_get_result($check_stmt);

                if (mysqli_num_rows($result) > 0) {
                    $message_error = "Le matricule '$matricule' existe déjà.";
                } else {
                    $insert_query = "INSERT INTO enseignants (matricule, nom, prenom, grade, telephone, email, specialite) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = mysqli_prepare($connexion, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, "sssssss", $matricule, $nom, $prenom, $grade, $telephone, $email, $specialite);

                    if (mysqli_stmt_execute($insert_stmt)) {
                        $id_enseignant = mysqli_insert_id($connexion);

                        $assign_query = "INSERT INTO enseignant_module (id_enseignant, id_module) VALUES (?, ?)";
                        $assign_stmt = mysqli_prepare($connexion, $assign_query);
                        mysqli_stmt_bind_param($assign_stmt, "ii", $id_enseignant, $id_module);

                        if (!mysqli_stmt_execute($assign_stmt)) {
                            mysqli_query($connexion, "DELETE FROM enseignants WHERE id_enseignant = $id_enseignant");
                            $message_error = "Erreur lors de l'affectation de la matière: " . mysqli_error($connexion);
                        } else {
                            $email_utilisateur = $email !== '' ? $email : strtolower($prenom . '.' . $nom . '@suptech.sn');
                            $email_utilisateur = str_replace(' ', '', $email_utilisateur);

                            $role_query = mysqli_query($connexion, "SELECT id_role FROM roles WHERE libelle = 'enseignant' LIMIT 1");
                            $role_row = mysqli_fetch_assoc($role_query);
                            $id_role_enseignant = $role_row ? (int) $role_row['id_role'] : 3;
                            $mot_de_passe = password_hash('enseignant123', PASSWORD_DEFAULT);

                            $check_user = mysqli_prepare($connexion, "SELECT id_utilisateur FROM utilisateurs WHERE email = ? LIMIT 1");
                            mysqli_stmt_bind_param($check_user, "s", $email_utilisateur);
                            mysqli_stmt_execute($check_user);
                            $user_result = mysqli_stmt_get_result($check_user);

                            if (mysqli_num_rows($user_result) === 0) {
                                $insert_user = mysqli_prepare($connexion, "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, actif, id_enseignant) VALUES (?, ?, ?, ?, ?, 1, ?)");
                                mysqli_stmt_bind_param($insert_user, "sssssi", $nom, $prenom, $email_utilisateur, $mot_de_passe, $id_role_enseignant, $id_enseignant);
                                mysqli_stmt_execute($insert_user);
                                mysqli_stmt_close($insert_user);
                            }
                            mysqli_stmt_close($check_user);

                            $message_success = "Enseignant ajouté avec succès et affecté à la matière sélectionnée. Un compte de connexion a été créé avec le mot de passe par défaut : enseignant123.";
                        }

                        mysqli_stmt_close($assign_stmt);
                    } else {
                        $message_error = "Erreur lors de l'ajout: " . mysqli_error($connexion);
                    }
                    mysqli_stmt_close($insert_stmt);
                }
                mysqli_stmt_close($check_stmt);
            }
        }
    }

    elseif ($action === 'modifier') {
        $id_enseignant = intval($_POST['id_enseignant'] ?? 0);
        $matricule = trim($_POST['matricule'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $grade = trim($_POST['grade'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $specialite = trim($_POST['specialite'] ?? '');
        $id_module = intval($_POST['id_module'] ?? 0);

        if (!$id_enseignant || !$nom || !$prenom || !$id_module) {
            $message_error = "Les champs Nom, Prénom et Matière assignée sont obligatoires.";
        } else {
            $module_existe = mysqli_query($connexion, "SELECT id_module FROM modules WHERE id_module = $id_module LIMIT 1");
            if (!$module_existe || mysqli_num_rows($module_existe) === 0) {
                $message_error = "La matière sélectionnée est invalide.";
            } else {
                $update_query = "UPDATE enseignants 
                                SET nom = ?, prenom = ?, grade = ?, telephone = ?, email = ?, specialite = ? 
                                WHERE id_enseignant = ?";
                $update_stmt = mysqli_prepare($connexion, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ssssssi", $nom, $prenom, $grade, $telephone, $email, $specialite, $id_enseignant);

                if (mysqli_stmt_execute($update_stmt)) {
                    $check_assign = mysqli_prepare($connexion, "SELECT 1 FROM enseignant_module WHERE id_enseignant = ? AND id_module = ? LIMIT 1");
                    mysqli_stmt_bind_param($check_assign, "ii", $id_enseignant, $id_module);
                    mysqli_stmt_execute($check_assign);
                    $assign_result = mysqli_stmt_get_result($check_assign);

                    if (mysqli_num_rows($assign_result) === 0) {
                        $insert_assign = mysqli_prepare($connexion, "INSERT INTO enseignant_module (id_enseignant, id_module) VALUES (?, ?)");
                        mysqli_stmt_bind_param($insert_assign, "ii", $id_enseignant, $id_module);
                        mysqli_stmt_execute($insert_assign);
                        mysqli_stmt_close($insert_assign);
                    }
                    mysqli_stmt_close($check_assign);

                    $nouvel_email = $email !== '' ? $email : strtolower($prenom . '.' . $nom . '@suptech.sn');
                    $nouvel_email = str_replace(' ', '', $nouvel_email);

                    $user_query = mysqli_prepare($connexion, "SELECT id_utilisateur FROM utilisateurs WHERE id_enseignant = ? LIMIT 1");
                    mysqli_stmt_bind_param($user_query, "i", $id_enseignant);
                    mysqli_stmt_execute($user_query);
                    $user_result = mysqli_stmt_get_result($user_query);
                    if ($user_row = mysqli_fetch_assoc($user_result)) {
                        $update_user = mysqli_prepare($connexion, "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id_enseignant = ?");
                        mysqli_stmt_bind_param($update_user, "sssi", $nom, $prenom, $nouvel_email, $id_enseignant);
                        mysqli_stmt_execute($update_user);
                        mysqli_stmt_close($update_user);
                    }
                    mysqli_stmt_close($user_query);

                    $message_success = "Enseignant modifié avec succès et matière assignée validée.";
                } else {
                    $message_error = "Erreur lors de la modification: " . mysqli_error($connexion);
                }
                mysqli_stmt_close($update_stmt);
            }
        }
    }

    elseif ($action === 'supprimer') {
        $id_enseignant = intval($_POST['id_enseignant'] ?? 0);

        if (!$id_enseignant) {
            $message_error = "ID invalide.";
        } else {
            $delete_query = "DELETE FROM enseignants WHERE id_enseignant = ?";
            $delete_stmt = mysqli_prepare($connexion, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $id_enseignant);

            if (mysqli_stmt_execute($delete_stmt)) {
                mysqli_query($connexion, "DELETE FROM utilisateurs WHERE id_enseignant = $id_enseignant");
                $message_success = "Enseignant supprimé avec succès.";
            } else {
                $message_error = "Erreur lors de la suppression: " . mysqli_error($connexion);
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// ========== RÉCUPÉRATION DONNÉES ==========
$query = "SELECT id_enseignant, matricule, nom, prenom, grade, telephone, email, specialite FROM enseignants ORDER BY nom, prenom";
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
    <title>Gestion des Enseignants - SupTech Business School</title>
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
                <!-- Titre -->
                <div class="mb-4">
                    <h1 class="h3 mb-0">
                        <i class="bi bi-person-badge me-2"></i>Gestion des Enseignants
                    </h1>
                </div>

                <!-- Messages -->
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

                <!-- Bouton Ajouter -->
                <div class="mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterEnseignant">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter un enseignant
                    </button>
                </div>

                <!-- Tableau -->
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom Complet</th>
                                    <th>Grade</th>
                                    <th>Spécialité</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (mysqli_fetch_all($result, MYSQLI_ASSOC) as $ens): ?>
                                    <tr>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($ens['matricule']) ?></span></td>
                                        <td><?= htmlspecialchars($ens['nom']) ?> <?= htmlspecialchars($ens['prenom']) ?></td>
                                        <td><?= htmlspecialchars($ens['grade'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($ens['specialite'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($ens['email'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($ens['telephone'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <a href="profil.php?id_enseignant=<?= (int) $ens['id_enseignant'] ?>" class="btn btn-sm btn-outline-info" title="Voir le profil et les matières">
                                                <i class="bi bi-person-lines-fill"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-primary" title="Modifier"
                                                    onclick="editEnseignant(<?= htmlspecialchars(json_encode($ens)) ?>)"
                                                    data-bs-toggle="modal" data-bs-target="#modalModifierEnseignant">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Supprimer"
                                                    onclick="deleteEnseignant(<?= $ens['id_enseignant'] ?>, '<?= htmlspecialchars($ens['nom'] . ' ' . $ens['prenom']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Aucun enseignant trouvé.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal - Ajouter -->
    <div class="modal fade" id="modalAjouterEnseignant" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Ajouter un nouvel enseignant</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="mb-3">
                            <label class="form-label">Matricule <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="matricule" placeholder="ex: ENS-001" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nom" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="prenom" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade</label>
                            <input type="text" class="form-control" name="grade" placeholder="ex: Professeur">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Spécialité</label>
                            <input type="text" class="form-control" name="specialite" placeholder="ex: Informatique">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Matière assignée <span class="text-danger">*</span></label>
                            <?php if (!empty($modules_disponibles)): ?>
                                <select class="form-select" name="id_module" required>
                                    <option value="">-- Sélectionner une matière --</option>
                                    <?php foreach ($modules_disponibles as $module): ?>
                                        <option value="<?= (int) $module['id_module'] ?>">
                                            <?= htmlspecialchars($module['nom']) ?>
                                            <?php if (!empty($module['classe_nom'])): ?>
                                                - <?= htmlspecialchars($module['classe_nom']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">Aucune matière disponible pour l'instant.</div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="email@suptech.sn">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone" placeholder="+221 77 XXX XX XX">
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
    <div class="modal fade" id="modalModifierEnseignant" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier l'enseignant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_enseignant" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Matricule</label>
                            <input type="text" class="form-control" id="edit_matricule" readonly>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_nom" name="nom" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_prenom" name="prenom" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade</label>
                            <input type="text" class="form-control" id="edit_grade" name="grade">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Spécialité</label>
                            <input type="text" class="form-control" id="edit_specialite" name="specialite">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Matière assignée <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_module" name="id_module" required>
                                <option value="">-- Sélectionner une matière --</option>
                                <?php foreach ($modules_disponibles as $module): ?>
                                    <option value="<?= (int) $module['id_module'] ?>">
                                        <?= htmlspecialchars($module['nom']) ?>
                                        <?php if (!empty($module['classe_nom'])): ?>
                                            - <?= htmlspecialchars($module['classe_nom']) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="edit_telephone" name="telephone">
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
    <div class="modal fade" id="modalSupprimerEnseignant" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Supprimer l'enseignant</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Attention!</strong> Cette action est irréversible et supprimera toutes les données associées.
                        </div>
                        <p>Êtes-vous sûr de vouloir supprimer <strong id="delete_nom"></strong> ?</p>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_enseignant" id="delete_id">
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
        function editEnseignant(enseignant) {
            document.getElementById('edit_id').value = enseignant.id_enseignant;
            document.getElementById('edit_matricule').value = enseignant.matricule;
            document.getElementById('edit_nom').value = enseignant.nom;
            document.getElementById('edit_prenom').value = enseignant.prenom;
            document.getElementById('edit_grade').value = enseignant.grade || '';
            document.getElementById('edit_specialite').value = enseignant.specialite || '';
            document.getElementById('edit_email').value = enseignant.email || '';
            document.getElementById('edit_telephone').value = enseignant.telephone || '';

            const moduleSelect = document.getElementById('edit_module');
            if (moduleSelect) {
                moduleSelect.value = '';

                const moduleId = enseignant.module_id || '';
                if (moduleId) {
                    moduleSelect.value = String(moduleId);
                }
            }
        }

        function deleteEnseignant(id, nom) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nom').textContent = nom;
            const modal = new bootstrap.Modal(document.getElementById('modalSupprimerEnseignant'));
            modal.show();
        }
    </script>
</body>
</html>
