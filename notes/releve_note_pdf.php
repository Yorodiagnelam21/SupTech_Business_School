<?php
// ========================================
// Génération du relevé de notes en PDF
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../libs/fpdf/fpdf.php';
require_role(['administrateur', 'scolarite', 'etudiant']);

$id_inscription = intval(isset($_GET['id_inscription']) ? $_GET['id_inscription'] : 0);
if (!$id_inscription) {
    die("Inscription non spécifiée.");
}

if ((isset($_SESSION['role_libelle']) ? $_SESSION['role_libelle'] : '') === 'etudiant') {
    $stmt_access = mysqli_prepare($connexion, "SELECT 1 FROM inscriptions i JOIN utilisateurs u ON u.id_etudiant = i.id_etudiant WHERE i.id_inscription = ? AND u.id_utilisateur = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_access, "ii", $id_inscription, $_SESSION['id_utilisateur']);
    mysqli_stmt_execute($stmt_access);
    $access = mysqli_stmt_get_result($stmt_access)->fetch_assoc();
    mysqli_stmt_close($stmt_access);
    if (!$access) {
        http_response_code(403);
        die("Accès interdit.");
    }
}

$info_query = "SELECT e.matricule, e.nom, e.prenom, c.nom AS classe, a.libelle AS annee
               FROM inscriptions i
               JOIN etudiants e ON i.id_etudiant = e.id_etudiant
               JOIN classes c ON i.id_classe = c.id_classe
               JOIN annees_universitaires a ON i.id_annee = a.id_annee
               WHERE i.id_inscription = ?";
$stmt = mysqli_prepare($connexion, $info_query);
mysqli_stmt_bind_param($stmt, "i", $id_inscription);
mysqli_stmt_execute($stmt);
$etudiant = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$etudiant) {
    die("Étudiant introuvable pour cette inscription.");
}

$notes_query = "SELECT mo.nom AS module, mo.coefficient, n.cc, n.tp, n.examen, n.moyenne, n.mention
                FROM notes n
                JOIN modules mo ON n.id_module = mo.id_module
                WHERE n.id_inscription = ?
                ORDER BY mo.nom";
$stmt = mysqli_prepare($connexion, $notes_query);
mysqli_stmt_bind_param($stmt, "i", $id_inscription);
mysqli_stmt_execute($stmt);
$notes = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$somme_pond = 0;
$somme_coef = 0;
foreach ($notes as $n) {
    if ($n['moyenne'] !== null) {
        $somme_pond += $n['moyenne'] * $n['coefficient'];
        $somme_coef += $n['coefficient'];
    }
}
$moyenne_generale = $somme_coef > 0 ? round($somme_pond / $somme_coef, 2) : null;

function nettoyer($texte) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', isset($texte) ? $texte : '');
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, nettoyer('SupTech Business School'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, nettoyer('Relevé de notes'), 0, 1, 'C');
$pdf->Ln(6);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, nettoyer('Étudiant : ' . $etudiant['nom'] . ' ' . $etudiant['prenom'] . ' (' . $etudiant['matricule'] . ')'), 0, 1);
$pdf->Cell(0, 7, nettoyer('Classe : ' . $etudiant['classe']), 0, 1);
$pdf->Cell(0, 7, nettoyer('Année universitaire : ' . $etudiant['annee']), 0, 1);
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(30, 60, 114);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(60, 8, nettoyer('Module'), 1, 0, 'C', true);
$pdf->Cell(24, 8, 'CC', 1, 0, 'C', true);
$pdf->Cell(24, 8, 'TP', 1, 0, 'C', true);
$pdf->Cell(24, 8, 'Examen', 1, 0, 'C', true);
$pdf->Cell(24, 8, 'Moyenne', 1, 0, 'C', true);
$pdf->Cell(30, 8, nettoyer('Mention'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);
foreach ($notes as $n) {
    $pdf->Cell(60, 8, nettoyer($n['module']), 1);
    $pdf->Cell(24, 8, $n['cc'] !== null ? $n['cc'] : '-', 1, 0, 'C');
    $pdf->Cell(24, 8, $n['tp'] !== null ? $n['tp'] : '-', 1, 0, 'C');
    $pdf->Cell(24, 8, $n['examen'] !== null ? $n['examen'] : '-', 1, 0, 'C');
    $pdf->Cell(24, 8, $n['moyenne'] !== null ? $n['moyenne'] : '-', 1, 0, 'C');
    $pdf->Cell(30, 8, nettoyer(isset($n['mention']) ? $n['mention'] : '-'), 1, 1, 'C');
}

$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, nettoyer('Moyenne générale : ' . ($moyenne_generale !== null ? $moyenne_generale . '/20' : 'Non calculée')), 0, 1);

$mode_pdf = isset($_GET['apercu']) && $_GET['apercu'] === '1' ? 'I' : 'D';
$pdf->Output($mode_pdf, 'releve_notes_' . $etudiant['matricule'] . '.pdf');
exit;