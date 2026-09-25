<?php
// notes/saisie.php
session_start();

// 1. Vérification de l'authentification
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$role = isset($_SESSION['role_libelle']) ? $_SESSION['role_libelle'] : '';
$id_enseignant = isset($_SESSION['id_enseignant']) ? $_SESSION['id_enseignant'] : null;
$id_module = isset($_GET['id_module']) ? intval($_GET['id_module']) : 0;

if (($role !== 'enseignant' && $role !== 'administrateur') || ($role === 'enseignant' && !$id_enseignant) || $id_module <= 0) {
    header('Location: index.php');
    exit;
}

// 2. Récupérer les détails du module et de la classe
$query_mod = "SELECT m.*, c.nom as classe_nom, c.id_classe 
              FROM modules m 
              JOIN classes c ON m.id_classe = c.id_classe 
              WHERE m.id_module = ? LIMIT 1";
$stmt_mod = mysqli_prepare($connexion, $query_mod);
mysqli_stmt_bind_param($stmt_mod, "i", $id_module);
mysqli_stmt_execute($stmt_mod);
$res_mod = mysqli_stmt_get_result($stmt_mod);
$module = mysqli_fetch_assoc($res_mod);
mysqli_stmt_close($stmt_mod);

if (!$module) {
    header('Location: index.php');
    exit;
}

// 3. Chaque enseignant ne peut saisir que les notes de son module attribué.
$query_verif = "SELECT 1 FROM enseignant_module WHERE id_enseignant = ? AND id_module = ? LIMIT 1";
$stmt_verif = mysqli_prepare($connexion, $query_verif);
mysqli_stmt_bind_param($stmt_verif, "ii", $id_enseignant, $id_module);
mysqli_stmt_execute($stmt_verif);
$res_verif = mysqli_stmt_get_result($stmt_verif);
$is_assigned = mysqli_fetch_assoc($res_verif);
mysqli_stmt_close($stmt_verif);

if ($role === 'enseignant' && !$is_assigned) {
    header('Location: index.php');
    exit;
}

// 4. Traitement du formulaire de sauvegarde (POST)
$message_success = '';
$message_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes_post = isset($_POST['notes']) ? $_POST['notes'] : [];
    $saved_count = 0;
    $deleted_count = 0;

    foreach ($notes_post as $id_inscription => $data) {
        $id_inscription = intval($id_inscription);

        $cc_raw = isset($data['cc']) ? trim((string) $data['cc']) : '';
        $tp_raw = isset($data['tp']) ? trim((string) $data['tp']) : '';
        $examen_raw = isset($data['examen']) ? trim((string) $data['examen']) : '';

        $cc = ($cc_raw !== '') ? floatval(str_replace(',', '.', $cc_raw)) : null;
        $tp = ($tp_raw !== '') ? floatval(str_replace(',', '.', $tp_raw)) : null;
        $examen = ($examen_raw !== '') ? floatval(str_replace(',', '.', $examen_raw)) : null;

        if ($cc_raw === '' && $tp_raw === '' && $examen_raw === '') {
            if (mysqli_query($connexion, "DELETE FROM notes WHERE id_inscription = $id_inscription AND id_module = $id_module")) {
                $deleted_count++;
            }
            continue;
        }

        if (($cc !== null && ($cc < 0 || $cc > 20)) || 
            ($tp !== null && ($tp < 0 || $tp > 20)) || 
            ($examen !== null && ($examen < 0 || $examen > 20))) {
            $message_error = "Les notes doivent être comprises entre 0 et 20.";
            break;
        }

        $moyenne = null;
        $mention = null;
        if ($cc !== null && $tp !== null && $examen !== null) {
            $moyenne = round(($cc * 0.2) + ($tp * 0.3) + ($examen * 0.5), 2);

            if ($moyenne >= 16) $mention = 'Très Bien';
            elseif ($moyenne >= 14) $mention = 'Bien';
            elseif ($moyenne >= 12) $mention = 'Assez Bien';
            elseif ($moyenne >= 10) $mention = 'Passable';
            else $mention = 'Insuffisant';
        }

        $query_save = "INSERT INTO notes (id_inscription, id_module, cc, tp, examen, moyenne, mention) 
                       VALUES (?, ?, ?, ?, ?, ?, ?) 
                       ON DUPLICATE KEY UPDATE cc = ?, tp = ?, examen = ?, moyenne = ?, mention = ?";

        if ($stmt_save = mysqli_prepare($connexion, $query_save)) {
            mysqli_stmt_bind_param($stmt_save, "iiddddsdddds",
                $id_inscription, $id_module, $cc, $tp, $examen, $moyenne, $mention,
                $cc, $tp, $examen, $moyenne, $mention
            );

            if (mysqli_stmt_execute($stmt_save)) {
                $saved_count++;
            }
            mysqli_stmt_close($stmt_save);
        }
    }

    if (empty($message_error)) {
        $message_success = "$saved_count note(s) enregistrées et $deleted_count note(s) supprimées avec succès.";
    }
}

// 5. Récupérer l'année universitaire active pour les inscriptions
$res_annee = mysqli_query($connexion, "SELECT id_annee, libelle FROM annees_universitaires WHERE active = 1 LIMIT 1");
$annee_active = mysqli_fetch_assoc($res_annee);
$id_annee_active = $annee_active ? $annee_active['id_annee'] : 0;

// 6. Récupérer les étudiants inscrits dans la classe de ce module + leurs notes existantes
$etudiants_notes = [];
if ($id_annee_active > 0) {
    $query_etud = "SELECT e.id_etudiant, e.matricule, e.nom, e.prenom, e.photo, i.id_inscription,
                          n.cc, n.tp, n.examen, n.moyenne, n.mention
                   FROM inscriptions i
                   JOIN etudiants e ON i.id_etudiant = e.id_etudiant
                   LEFT JOIN notes n ON n.id_inscription = i.id_inscription AND n.id_module = ?
                   WHERE i.id_classe = ? AND i.id_annee = ?
                   ORDER BY e.nom, e.prenom";
    
    if ($stmt_etud = mysqli_prepare($connexion, $query_etud)) {
        mysqli_stmt_bind_param($stmt_etud, "iii", $id_module, $module['id_classe'], $id_annee_active);
        mysqli_stmt_execute($stmt_etud);
        $res_etud = mysqli_stmt_get_result($stmt_etud);
        while ($row = mysqli_fetch_assoc($res_etud)) {
            $etudiants_notes[] = $row;
        }
        mysqli_stmt_close($stmt_etud);
    }
}

// Inclusion de l'entête et du menu latéral
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Saisie des notes - <?= htmlspecialchars($module['nom']) ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">
            <i class="bi bi-arrow-left"></i> Retour aux modules
        </a>
        <span class="badge bg-info text-dark px-3 py-2 fs-6">
            Classe : <?= htmlspecialchars($module['classe_nom']) ?>
        </span>
    </div>
</div>

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

<?php if (!$annee_active): ?>
    <div class="alert alert-warning py-4 text-center">
        <i class="bi bi-exclamation-octagon-fill fs-1 d-block mb-3 text-warning"></i>
        <h4>Aucune année universitaire n'est configurée comme active.</h4>
        <p class="text-muted mb-0">La saisie des notes requiert une année universitaire en cours d'activité pour lister les étudiants inscrits.</p>
    </div>
<?php elseif (empty($etudiants_notes)): ?>
    <div class="alert alert-info py-4 text-center">
        <i class="bi bi-info-circle-fill fs-1 d-block mb-3 text-info"></i>
        <h4>Aucun étudiant inscrit dans cette classe</h4>
        <p class="text-muted mb-0">Il n'y a aucun étudiant inscrit dans la classe <strong><?= htmlspecialchars($module['classe_nom']) ?></strong> pour l'année universitaire active (<?= htmlspecialchars($annee_active['libelle']) ?>).</p>
    </div>
<?php else: ?>
    <!-- Formulaire de saisie -->
    <form action="" method="POST" id="formSaisie">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title text-dark mb-0 font-weight-bold">
                    <i class="bi bi-people-fill text-primary me-2"></i>Liste des étudiants inscrits (<?= htmlspecialchars($annee_active['libelle']) ?>)
                </h5>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> Enregistrer les notes
                </button>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tableNotes">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Photo</th>
                                <th>Matricule</th>
                                <th>Nom Complet</th>
                                <th style="width: 140px;">CC (20%)</th>
                                <th style="width: 140px;">TP (30%)</th>
                                <th style="width: 140px;">Examen (50%)</th>
                                <th style="width: 150px;" class="text-center">Moyenne</th>
                                <th style="width: 150px;">Mention</th>
                                <th style="width: 150px;">Décision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etudiants_notes as $etud): ?>
                                <?php
                                $id_ins = $etud['id_inscription'];
                                $cc_val = ($etud['cc'] !== null) ? number_format($etud['cc'], 2) : '';
                                $tp_val = ($etud['tp'] !== null) ? number_format($etud['tp'], 2) : '';
                                $exam_val = ($etud['examen'] !== null) ? number_format($etud['examen'], 2) : '';
                                $moy_val = ($etud['moyenne'] !== null) ? number_format($etud['moyenne'], 2) : '';
                                $mention_val = isset($etud['mention']) ? $etud['mention'] : '';
                                $decision_val = '';
                                if ($etud['moyenne'] !== null) {
                                    $decision_val = ($etud['moyenne'] >= 10) ? 'Admis' : 'Ajourné';
                                }
                                ?>
                                <tr data-inscription="<?= $id_ins ?>">
                                    <td>
                                        <div class="avatar-circle">
                                            <i class="bi bi-person-fill text-secondary fs-4"></i>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($etud['matricule']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($etud['nom']) ?></strong> <?= htmlspecialchars($etud['prenom']) ?></td>
                                    <td>
                                        <input type="number" step="0.25" min="0" max="20" 
                                               class="form-control form-control-sm input-note note-cc" 
                                               name="notes[<?= $id_ins ?>][cc]" 
                                               value="<?= $cc_val ?>" 
                                               placeholder="-/20">
                                    </td>
                                    <td>
                                        <input type="number" step="0.25" min="0" max="20" 
                                               class="form-control form-control-sm input-note note-tp" 
                                               name="notes[<?= $id_ins ?>][tp]" 
                                               value="<?= $tp_val ?>" 
                                               placeholder="-/20">
                                    </td>
                                    <td>
                                        <input type="number" step="0.25" min="0" max="20" 
                                               class="form-control form-control-sm input-note note-exam" 
                                               name="notes[<?= $id_ins ?>][examen]" 
                                               value="<?= $exam_val ?>" 
                                               placeholder="-/20">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2 fs-6 cell-moyenne <?= ($etud['moyenne'] !== null && $etud['moyenne'] >= 10) ? 'bg-success' : (($etud['moyenne'] !== null) ? 'bg-danger' : 'bg-light text-muted') ?>">
                                            <?= $moy_val !== '' ? $moy_val . '/20' : 'Non calculé' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted font-weight-bold cell-mention"><?= htmlspecialchars($mention_val) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge cell-decision <?= ($decision_val === 'Admis') ? 'bg-success' : (($decision_val === 'Ajourné') ? 'bg-danger' : '') ?>">
                                            <?= htmlspecialchars($decision_val) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 py-3 d-flex justify-content-end">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> Enregistrer les notes
                </button>
            </div>
        </div>
    </form>
<?php endif; ?>

<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        background-color: #e9ecef;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .input-note {
        text-align: center;
        font-weight: 500;
    }

    /* Supprime les flèches d'incrémentation dans les inputs de type number */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

<!-- Script de calcul dynamique -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('tableNotes');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const inputCC = row.querySelector('.note-cc');
        const inputTP = row.querySelector('.note-tp');
        const inputExam = row.querySelector('.note-exam');

        const cellMoyenne = row.querySelector('.cell-moyenne');
        const cellMention = row.querySelector('.cell-mention');
        const cellDecision = row.querySelector('.cell-decision');

        function recalculate() {
            const cc = parseFloat(inputCC.value);
            const tp = parseFloat(inputTP.value);
            const exam = parseFloat(inputExam.value);

            if (!isNaN(cc) && !isNaN(tp) && !isNaN(exam)) {
                const moyenne = (cc * 0.2) + (tp * 0.3) + (exam * 0.5);
                const roundedMoy = Math.round(moyenne * 100) / 100;

                cellMoyenne.innerText = roundedMoy.toFixed(2) + "/20";
                cellMoyenne.className = "badge px-3 py-2 fs-6 cell-moyenne " + (roundedMoy >= 10 ? "bg-success" : "bg-danger");

                let mention = "";
                if (roundedMoy >= 16) mention = "Très Bien";
                else if (roundedMoy >= 14) mention = "Bien";
                else if (roundedMoy >= 12) mention = "Assez Bien";
                else if (roundedMoy >= 10) mention = "Passable";
                else mention = "Insuffisant";

                cellMention.innerText = mention;
                cellMention.className = "text-muted font-weight-bold cell-mention";

                const decision = roundedMoy >= 10 ? "Admis" : "Ajourné";
                cellDecision.innerText = decision;
                cellDecision.className = "badge cell-decision " + (decision === "Admis" ? "bg-success" : "bg-danger");
            } else {
                cellMoyenne.innerText = "Non calculé";
                cellMoyenne.className = "badge px-3 py-2 fs-6 cell-moyenne bg-light text-muted";
                cellMention.innerText = "";
                cellDecision.innerText = "";
                cellDecision.className = "badge cell-decision";
            }
        }

        inputCC.addEventListener('input', function() {
            validateNote(this);
            recalculate();
        });
        inputTP.addEventListener('input', function() {
            validateNote(this);
            recalculate();
        });
        inputExam.addEventListener('input', function() {
            validateNote(this);
            recalculate();
        });
    });

    function validateNote(input) {
        let val = parseFloat(input.value);
        if (val < 0) input.value = 0;
        if (val > 20) input.value = 20;
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
