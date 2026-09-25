<?php
// ========================================
// Export de la liste des paiements (CSV / Excel)
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';
require_role(['administrateur', 'scolarite']);

$query = "SELECT p.id_paiement, e.nom, e.prenom, c.nom AS classe,
                 p.montant_paye, p.montant_total, p.date_paiement, p.mode_paiement
          FROM paiements p
          JOIN inscriptions i ON p.id_inscription = i.id_inscription
          JOIN etudiants e ON i.id_etudiant = e.id_etudiant
          JOIN classes c ON i.id_classe = c.id_classe
          ORDER BY p.date_paiement DESC";
$result = mysqli_query($connexion, $query);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="paiements_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['ID', 'Nom', 'Prénom', 'Classe', 'Montant payé', 'Montant total', 'Reste à payer', 'Date paiement', 'Mode de paiement'], ';');

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id_paiement'],
        $row['nom'],
        $row['prenom'],
        $row['classe'],
        $row['montant_paye'],
        $row['montant_total'],
        $row['montant_total'] - $row['montant_paye'],
        $row['date_paiement'],
        $row['mode_paiement'],
    ], ';');
}

fclose($output);
exit;