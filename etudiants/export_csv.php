<?php
// ========================================
// Export de la liste des étudiants (CSV / Excel)
// ========================================
session_start();
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: /gestion_academique/auth/login.php');
    exit;
}

require_once '../config/database.php';
require_role(['administrateur', 'scolarite']);

$query = "SELECT matricule, nom, prenom, date_naissance, sexe, email, adresse
          FROM etudiants
          ORDER BY nom, prenom";
$result = mysqli_query($connexion, $query);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="etudiants_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Matricule', 'Nom', 'Prénom', 'Date de naissance', 'Sexe', 'Email', 'Adresse'], ';');

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['matricule'],
        $row['nom'],
        $row['prenom'],
        $row['date_naissance'],
        $row['sexe'],
        $row['email'],
        $row['adresse'],
    ], ';');
}

fclose($output);
exit;