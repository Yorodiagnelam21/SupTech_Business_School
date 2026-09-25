<?php
// etudiants/index.php - Gestion des étudiants
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_role(['administrateur', 'scolarite']);

function genererMatriculeEtudiant($connexion)
{
    $query = "SELECT matricule FROM etudiants WHERE matricule REGEXP '^[A-Z]+[-_]?([0-9]+)$' OR matricule LIKE 'ETU%' ORDER BY matricule DESC";
    $result = mysqli_query($connexion, $query);
    $max = 0;

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $numero = preg_replace('/[^0-9]/', '', (string) $row['matricule']);
            if ($numero !== '' && (int) $numero > $max) {
                $max = (int) $numero;
            }
        }
    }

    return 'ETU-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
}

// ============================================================
// TRAITEMENT DES ACTIONS (AJOUTER, MODIFIER, SUPPRIMER)
// ============================================================

$message_success = '';
$message_error = '';

$annee_active_result = mysqli_query($connexion, "SELECT id_annee FROM annees_universitaires WHERE active = 1 LIMIT 1");
$annee_active = mysqli_fetch_assoc($annee_active_result);
$id_annee_active = $annee_active ? (int) $annee_active['id_annee'] : 0;

$classes_result = mysqli_query($connexion, "SELECT id_classe, nom, filiere, niveau FROM classes ORDER BY niveau, filiere, nom");
$classes = $classes_result ? mysqli_fetch_all($classes_result, MYSQLI_ASSOC) : [];

// Ajouter un nouvel étudiant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'ajouter') {
        $matricule = trim(isset($_POST['matricule']) ? $_POST['matricule'] : '');
        if ($matricule === '') {
            $matricule = genererMatriculeEtudiant($connexion);
        }

        $nom = trim(isset($_POST['nom']) ? $_POST['nom'] : '');
        $prenom = trim(isset($_POST['prenom']) ? $_POST['prenom'] : '');
        $date_naissance = isset($_POST['date_naissance']) ? $_POST['date_naissance'] : '';
        $sexe = isset($_POST['sexe']) ? $_POST['sexe'] : '';
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $adresse = trim(isset($_POST['adresse']) ? $_POST['adresse'] : '');
        $id_classe = intval(isset($_POST['id_classe']) ? $_POST['id_classe'] : 0);

        if (empty($nom) || empty($prenom) || !$id_classe) {
            $message_error = "Les champs Nom, Prénom et Classe sont obligatoires.";
        } else {
            // Vérifier si le matricule existe déjà
            $check_query = "SELECT id_etudiant FROM etudiants WHERE matricule = ?";
            $check_stmt = mysqli_prepare($connexion, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $matricule);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $message_error = "Ce matricule existe déjà.";
            } else {
                // Gestion de l'upload de la photo (facultative)
                $photo_nom = 'default.png';
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $extensions_autorisees = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    $taille_max = 2 * 1024 * 1024; // 2 Mo

                    if (!in_array($extension, $extensions_autorisees)) {
                        $message_error = "Format de photo non autorisé (jpg, jpeg ou png uniquement).";
                    } elseif ($_FILES['photo']['size'] > $taille_max) {
                        $message_error = "La photo dépasse la taille maximale autorisée (2 Mo).";
                    } else {
                        // Nom de fichier unique pour éviter tout écrasement
                        $photo_nom = 'etu_' . $matricule . '_' . time() . '.' . $extension;
                        $chemin_destination = __DIR__ . '/../uploads/' . $photo_nom;
                        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $chemin_destination)) {
                            $message_error = "Erreur lors de l'enregistrement de la photo.";
                            $photo_nom = 'default.png';
                        }
                    }
                }

                if (empty($message_error)) {
                    $insert_query = "INSERT INTO etudiants (matricule, nom, prenom, date_naissance, sexe, email, adresse, photo) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = mysqli_prepare($connexion, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, "ssssssss", $matricule, $nom, $prenom, $date_naissance, $sexe, $email, $adresse, $photo_nom);

                    if (mysqli_stmt_execute($insert_stmt)) {
                        $id_etudiant = mysqli_insert_id($connexion);

                        if ($id_annee_active > 0) {
                            $inscription_query = "INSERT INTO inscriptions (id_etudiant, id_classe, id_annee, date_inscription)
                                                  VALUES (?, ?, ?, CURDATE())
                                                  ON DUPLICATE KEY UPDATE id_classe = VALUES(id_classe), date_inscription = VALUES(date_inscription)";
                            $inscription_stmt = mysqli_prepare($connexion, $inscription_query);
                            mysqli_stmt_bind_param($inscription_stmt, "iii", $id_etudiant, $id_classe, $id_annee_active);
                            mysqli_stmt_execute($inscription_stmt);
                            mysqli_stmt_close($inscription_stmt);
                        }

                        $message_success = "Étudiant ajouté avec succès.";
                    } else {
                        $message_error = "Erreur lors de l'ajout de l'étudiant.";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
            }
            mysqli_stmt_close($check_stmt);
        }
    }

    // Modifier un étudiant
    elseif ($action === 'modifier') {
        $id_etudiant = intval(isset($_POST['id_etudiant']) ? $_POST['id_etudiant'] : 0);
        $nom = trim(isset($_POST['nom']) ? $_POST['nom'] : '');
        $prenom = trim(isset($_POST['prenom']) ? $_POST['prenom'] : '');
        $date_naissance = isset($_POST['date_naissance']) ? $_POST['date_naissance'] : '';
        $sexe = isset($_POST['sexe']) ? $_POST['sexe'] : '';
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $adresse = trim(isset($_POST['adresse']) ? $_POST['adresse'] : '');
        $id_classe = intval(isset($_POST['id_classe']) ? $_POST['id_classe'] : 0);

        if (empty($nom) || empty($prenom)) {
            $message_error = "Les champs Nom et Prénom sont obligatoires.";
        } else {
            // Gestion de l'upload de la nouvelle photo (facultative — on ne touche pas à la photo si aucun fichier n'est envoyé)
            $nouvelle_photo = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $extensions_autorisees = ['jpg', 'jpeg', 'png'];
                $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $taille_max = 2 * 1024 * 1024;

                if (!in_array($extension, $extensions_autorisees)) {
                    $message_error = "Format de photo non autorisé (jpg, jpeg ou png uniquement).";
                } elseif ($_FILES['photo']['size'] > $taille_max) {
                    $message_error = "La photo dépasse la taille maximale autorisée (2 Mo).";
                } else {
                    $nouvelle_photo = 'etu_' . $id_etudiant . '_' . time() . '.' . $extension;
                    $chemin_destination = __DIR__ . '/../uploads/' . $nouvelle_photo;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $chemin_destination)) {
                        $message_error = "Erreur lors de l'enregistrement de la photo.";
                        $nouvelle_photo = null;
                    }
                }
            }

            if (empty($message_error)) {
                if ($nouvelle_photo !== null) {
                    $update_query = "UPDATE etudiants SET nom = ?, prenom = ?, date_naissance = ?, sexe = ?, email = ?, adresse = ?, photo = ? 
                             WHERE id_etudiant = ?";
                    $update_stmt = mysqli_prepare($connexion, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "sssssssi", $nom, $prenom, $date_naissance, $sexe, $email, $adresse, $nouvelle_photo, $id_etudiant);
                } else {
                    $update_query = "UPDATE etudiants SET nom = ?, prenom = ?, date_naissance = ?, sexe = ?, email = ?, adresse = ? 
                             WHERE id_etudiant = ?";
                    $update_stmt = mysqli_prepare($connexion, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "ssssssi", $nom, $prenom, $date_naissance, $sexe, $email, $adresse, $id_etudiant);
                }

                if (mysqli_stmt_execute($update_stmt)) {
                    if ($id_classe > 0 && $id_annee_active > 0) {
                        $inscription_update = "INSERT INTO inscriptions (id_etudiant, id_classe, id_annee, date_inscription)
                                               VALUES (?, ?, ?, CURDATE())
                                               ON DUPLICATE KEY UPDATE id_classe = VALUES(id_classe), date_inscription = VALUES(date_inscription)";
                        $inscription_stmt = mysqli_prepare($connexion, $inscription_update);
                        mysqli_stmt_bind_param($inscription_stmt, "iii", $id_etudiant, $id_classe, $id_annee_active);
                        mysqli_stmt_execute($inscription_stmt);
                        mysqli_stmt_close($inscription_stmt);
                    }

                    $message_success = "Étudiant modifié avec succès.";
                } else {
                    $message_error = "Erreur lors de la modification de l'étudiant.";
                }
                mysqli_stmt_close($update_stmt);
            }
        }
    }

    // Supprimer un étudiant
    elseif ($action === 'supprimer') {
        $id_etudiant = intval(isset($_POST['id_etudiant']) ? $_POST['id_etudiant'] : 0);

        mysqli_begin_transaction($connexion);
        try {
            $delete_related = mysqli_prepare($connexion, "DELETE p, n, i FROM inscriptions i
                LEFT JOIN paiements p ON p.id_inscription = i.id_inscription
                LEFT JOIN notes n ON n.id_inscription = i.id_inscription
                WHERE i.id_etudiant = ?");
            mysqli_stmt_bind_param($delete_related, "i", $id_etudiant);
            if (!mysqli_stmt_execute($delete_related)) {
                throw new Exception(mysqli_error($connexion));
            }
            mysqli_stmt_close($delete_related);

            $delete_user = mysqli_prepare($connexion, "DELETE FROM utilisateurs WHERE id_etudiant = ?");
            mysqli_stmt_bind_param($delete_user, "i", $id_etudiant);
            mysqli_stmt_execute($delete_user);
            mysqli_stmt_close($delete_user);

            $delete_stmt = mysqli_prepare($connexion, "DELETE FROM etudiants WHERE id_etudiant = ?");
            mysqli_stmt_bind_param($delete_stmt, "i", $id_etudiant);
            if (!mysqli_stmt_execute($delete_stmt)) {
                throw new Exception(mysqli_error($connexion));
            }
            mysqli_stmt_close($delete_stmt);
            mysqli_commit($connexion);
            $message_success = "Étudiant supprimé avec succès.";
        } catch (Exception $e) {
            mysqli_rollback($connexion);
            $message_error = "Erreur lors de la suppression de l'étudiant.";
        }
    }
}

// ============================================================
// RÉCUPÉRATION DE LA LISTE DES ÉTUDIANTS
// ============================================================

$etudiants = [];
$query = "SELECT e.id_etudiant, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe, e.email, e.adresse, e.photo,
                 i.id_classe AS classe_id, i.id_inscription
          FROM etudiants e
          LEFT JOIN inscriptions i ON i.id_etudiant = e.id_etudiant AND i.id_annee = ?
          ORDER BY e.nom, e.prenom";
$stmt = mysqli_prepare($connexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_annee_active);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $etudiants[] = $row;
    }
}

// Inclusion de l'entête et du menu latéral
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="col-md-10 px-md-4 py-3">
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestion des Étudiants</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="export_csv.php" class="btn btn-outline-success me-2">
            <i class="bi bi-file-earmark-excel me-2"></i>Exporter Excel
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterEtudiant">
            <i class="bi bi-plus-circle me-2"></i>Ajouter un étudiant
        </button>
    </div>
</div>

<!-- Messages d'alerte -->
<?php if (!empty($message_success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= htmlspecialchars($message_success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($message_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($message_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Tableau des étudiants -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="card-title text-dark mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>Liste des étudiants (<?= count($etudiants) ?>)
        </h5>
    </div>
    <div class="card-body pt-0">
        <?php if (empty($etudiants)): ?>
            <div class="alert alert-info py-4 text-center">
                <i class="bi bi-info-circle-fill fs-2 d-block mb-2"></i>
                Aucun étudiant enregistré pour le moment.
                <br>
                <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalAjouterEtudiant">
                    <i class="bi bi-plus-circle me-1"></i>Ajouter le premier étudiant
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 56px;">Photo</th>
                            <th style="width: 80px;">Matricule</th>
                            <th>Nom Complet</th>
                            <th>Sexe</th>
                            <th>Email</th>
                            <th>Date de Naissance</th>
                            <th style="width: 120px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etudiants as $etud): ?>
                            <tr>
                                <td>
                                    <img src="../uploads/<?= htmlspecialchars($etud['photo'] ?: 'default.png') ?>"
                                         onerror="this.src='../uploads/default.png'"
                                         alt="Photo de <?= htmlspecialchars($etud['nom']) ?>"
                                         class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($etud['matricule']) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($etud['nom']) ?></strong> <?= htmlspecialchars($etud['prenom']) ?>
                                </td>
                                <td>
                                    <?php if ($etud['sexe'] === 'M'): ?>
                                        <span class="badge bg-info">M</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">F</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($etud['email'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <small><?= $etud['date_naissance'] ? date('d/m/Y', strtotime($etud['date_naissance'])) : '-' ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($etud['id_inscription'])): ?>
                                        <a class="btn btn-sm btn-outline-secondary" title="Aperçu du bulletin" target="_blank" rel="noopener"
                                           href="../notes/releve_note_pdf.php?id_inscription=<?= (int) $etud['id_inscription'] ?>&apercu=1">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a class="btn btn-sm btn-outline-success" title="Télécharger le bulletin"
                                           href="../notes/releve_note_pdf.php?id_inscription=<?= (int) $etud['id_inscription'] ?>">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-primary" title="Modifier" 
                                            onclick="editEtudiant(<?= htmlspecialchars(json_encode($etud)) ?>)"
                                            data-bs-toggle="modal" data-bs-target="#modalModifierEtudiant">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Supprimer"
                                            onclick="deleteEtudiant(<?= $etud['id_etudiant'] ?>, '<?= htmlspecialchars($etud['nom'] . ' ' . $etud['prenom']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</main>

<!-- Modal - Ajouter un étudiant -->
<div class="modal fade" id="modalAjouterEtudiant" tabindex="-1" aria-labelledby="labelModalAjouter" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="labelModalAjouter">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter un nouvel étudiant
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="ajouter">

                    <div class="mb-3 text-center">
                        <img id="previewAjout" src="../uploads/default.png" alt="Aperçu photo"
                             class="rounded-circle mb-2" width="80" height="80" style="object-fit: cover;">
                        <label for="photo_ajout" class="form-label d-block">Photo (facultatif, jpg/png, 2 Mo max)</label>
                        <input type="file" class="form-control" id="photo_ajout" name="photo" accept=".jpg,.jpeg,.png"
                               onchange="previewPhoto(this, 'previewAjout')">
                    </div>

                    <div class="mb-3">
                        <label for="matricule" class="form-label">Matricule</label>
                        <input type="text" class="form-control" id="matricule" name="matricule" placeholder="ex: ETU-001" readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom de famille" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Prénom" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_naissance" class="form-label">Date de Naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sexe" class="form-label">Sexe</label>
                            <select class="form-select" id="sexe" name="sexe">
                                <option value="">-- Sélectionner --</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="id_classe" class="form-label">Classe <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_classe" name="id_classe" required>
                            <option value="">-- Sélectionner une classe --</option>
                            <?php foreach ($classes as $classe): ?>
                                <option value="<?= (int) $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom'] . ' - ' . $classe['filiere'] . ' (' . $classe['niveau'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="email@example.com">
                    </div>

                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="2" placeholder="Adresse complète"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal - Modifier un étudiant -->
<div class="modal fade" id="modalModifierEtudiant" tabindex="-1" aria-labelledby="labelModalModifier" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="labelModalModifier">
                    <i class="bi bi-pencil-square me-2"></i>Modifier l'étudiant
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id_etudiant" id="edit_id_etudiant" value="">

                    <div class="mb-3 text-center">
                        <img id="previewModifier" src="../uploads/default.png" alt="Aperçu photo"
                             class="rounded-circle mb-2" width="80" height="80" style="object-fit: cover;">
                        <label for="photo_modifier" class="form-label d-block">Changer la photo (facultatif, jpg/png, 2 Mo max)</label>
                        <input type="file" class="form-control" id="photo_modifier" name="photo" accept=".jpg,.jpeg,.png"
                               onchange="previewPhoto(this, 'previewModifier')">
                    </div>

                    <div class="mb-3">
                        <label for="edit_matricule" class="form-label">Matricule</label>
                        <input type="text" class="form-control" id="edit_matricule" name="edit_matricule" readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_prenom" name="prenom" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_date_naissance" class="form-label">Date de Naissance</label>
                            <input type="date" class="form-control" id="edit_date_naissance" name="date_naissance">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_sexe" class="form-label">Sexe</label>
                            <select class="form-select" id="edit_sexe" name="sexe">
                                <option value="">-- Sélectionner --</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_id_classe" class="form-label">Classe</label>
                        <select class="form-select" id="edit_id_classe" name="id_classe">
                            <option value="">-- Sélectionner une classe --</option>
                            <?php foreach ($classes as $classe): ?>
                                <option value="<?= (int) $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom'] . ' - ' . $classe['filiere'] . ' (' . $classe['niveau'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>

                    <div class="mb-3">
                        <label for="edit_adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="edit_adresse" name="adresse" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Modifier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal - Supprimer un étudiant -->
<div class="modal fade" id="modalSupprimerEtudiant" tabindex="-1" aria-labelledby="labelModalSupprimer" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="labelModalSupprimer">
                    <i class="bi bi-trash me-2"></i>Supprimer un étudiant
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer <strong id="deleteEtudiantNom"></strong> ?</p>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Cette action est irréversible et supprimera toutes les données associées.
                </div>
            </div>
            <form action="" method="POST">
                <div class="modal-footer">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id_etudiant" id="delete_id_etudiant" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Supprimer définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function genererMatriculeAuto() {
        const dernier = <?= json_encode(genererMatriculeEtudiant($connexion)); ?>;
        const input = document.getElementById('matricule');
        if (input && !input.value) {
            input.value = dernier;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modalAjout = document.getElementById('modalAjouterEtudiant');
        if (modalAjout) {
            modalAjout.addEventListener('show.bs.modal', function () {
                const input = document.getElementById('matricule');
                if (input && !input.value) {
                    input.value = <?= json_encode(genererMatriculeEtudiant($connexion)); ?>;
                }
            });
        }
    });

    // Aperçu de la photo avant enregistrement (lu localement, rien n'est envoyé au serveur ici)
    function previewPhoto(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Remplir le formulaire d'édition
    function editEtudiant(etudiant) {
        document.getElementById('edit_id_etudiant').value = etudiant.id_etudiant;
        document.getElementById('edit_matricule').value = etudiant.matricule;
        document.getElementById('edit_nom').value = etudiant.nom;
        document.getElementById('edit_prenom').value = etudiant.prenom;
        document.getElementById('edit_date_naissance').value = etudiant.date_naissance || '';
        document.getElementById('edit_sexe').value = etudiant.sexe || '';
        document.getElementById('edit_id_classe').value = etudiant.classe_id || '';
        document.getElementById('edit_email').value = etudiant.email || '';
        document.getElementById('edit_adresse').value = etudiant.adresse || '';
        document.getElementById('photo_modifier').value = '';
        document.getElementById('previewModifier').src = '../uploads/' + (etudiant.photo || 'default.png');
    }

    // Afficher modal de suppression
    function deleteEtudiant(id, nom) {
        document.getElementById('delete_id_etudiant').value = id;
        document.getElementById('deleteEtudiantNom').textContent = nom;
        const modal = new bootstrap.Modal(document.getElementById('modalSupprimerEtudiant'));
        modal.show();
    }
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
