<?php
// ========================================
// Génération du reçu de paiement en PDF
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../libs/fpdf/fpdf.php';

$id_paiement = intval(isset($_GET['id_paiement']) ? $_GET['id_paiement'] : 0);
if (!$id_paiement) {
    die("Paiement non spécifié.");
}

if ((isset($_SESSION['role_libelle']) ? $_SESSION['role_libelle'] : '') === 'etudiant') {
    $stmt_access = mysqli_prepare($connexion, "SELECT 1 FROM paiements p JOIN inscriptions i ON i.id_inscription = p.id_inscription JOIN utilisateurs u ON u.id_etudiant = i.id_etudiant WHERE p.id_paiement = ? AND u.id_utilisateur = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_access, "ii", $id_paiement, $_SESSION['id_utilisateur']);
    mysqli_stmt_execute($stmt_access);
    $access = mysqli_stmt_get_result($stmt_access)->fetch_assoc();
    mysqli_stmt_close($stmt_access);
    if (!$access) {
        http_response_code(403);
        die("Accès interdit.");
    }
}

$query = "SELECT p.id_paiement, p.montant_paye, p.montant_total, p.date_paiement, p.mode_paiement,
                 e.matricule, e.nom, e.prenom, c.nom AS classe
          FROM paiements p
          JOIN inscriptions i ON p.id_inscription = i.id_inscription
          JOIN etudiants e ON i.id_etudiant = e.id_etudiant
          JOIN classes c ON i.id_classe = c.id_classe
          WHERE p.id_paiement = ?";
$stmt = mysqli_prepare($connexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_paiement);
mysqli_stmt_execute($stmt);
$paiement = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$paiement) {
    die("Paiement introuvable.");
}

function nettoyer($texte) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', isset($texte) ? $texte : '');
}

$reste = $paiement['montant_total'] - $paiement['montant_paye'];

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, nettoyer('SupTech Business School'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, nettoyer('Reçu de paiement N° ' . str_pad($paiement['id_paiement'], 5, '0', STR_PAD_LEFT)), 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(50, 8, nettoyer('Étudiant :'), 0, 0);
$pdf->Cell(0, 8, nettoyer($paiement['nom'] . ' ' . $paiement['prenom'] . ' (' . $paiement['matricule'] . ')'), 0, 1);

$pdf->Cell(50, 8, nettoyer('Classe :'), 0, 0);
$pdf->Cell(0, 8, nettoyer($paiement['classe']), 0, 1);

$pdf->Cell(50, 8, nettoyer('Date de paiement :'), 0, 0);
$pdf->Cell(0, 8, nettoyer($paiement['date_paiement']), 0, 1);

$pdf->Cell(50, 8, nettoyer('Mode de paiement :'), 0, 0);
$pdf->Cell(0, 8, nettoyer(isset($paiement['mode_paiement']) ? $paiement['mode_paiement'] : '-'), 0, 1);
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(90, 9, nettoyer('Montant total dû'), 1, 0, 'L', true);
$pdf->Cell(0, 9, number_format($paiement['montant_total'], 0, ',', ' ') . ' FCFA', 1, 1, 'R', true);
$pdf->Cell(90, 9, nettoyer('Montant payé'), 1, 0, 'L', true);
$pdf->Cell(0, 9, number_format($paiement['montant_paye'], 0, ',', ' ') . ' FCFA', 1, 1, 'R', true);
$pdf->Cell(90, 9, nettoyer('Reste à payer'), 1, 0, 'L', true);
$pdf->Cell(0, 9, number_format($reste, 0, ',', ' ') . ' FCFA', 1, 1, 'R', true);

$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, nettoyer('Document généré automatiquement le ' . date('d/m/Y à H:i')), 0, 1, 'C');

$mode_pdf = isset($_GET['apercu']) && $_GET['apercu'] === '1' ? 'I' : 'D';
$pdf->Output($mode_pdf, 'recu_paiement_' . $paiement['id_paiement'] . '.pdf');
exit;