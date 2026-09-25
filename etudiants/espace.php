<?php
session_start();
if (!isset($_SESSION['id_utilisateur']) || (isset($_SESSION['role_libelle']) ? $_SESSION['role_libelle'] : '') !== 'etudiant') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$id_utilisateur = (int) $_SESSION['id_utilisateur'];

$query = "SELECT e.id_etudiant, e.matricule, e.nom, e.prenom, i.id_inscription, c.nom AS classe, a.libelle AS annee,
                 COUNT(DISTINCT m.id_module) AS total_modules,
                 COUNT(DISTINCT CASE WHEN n.moyenne IS NOT NULL THEN n.id_module END) AS modules_evalues
          FROM utilisateurs u
          JOIN etudiants e ON e.id_etudiant = u.id_etudiant
          LEFT JOIN inscriptions i ON i.id_etudiant = e.id_etudiant
          LEFT JOIN classes c ON c.id_classe = i.id_classe
          LEFT JOIN annees_universitaires a ON a.id_annee = i.id_annee
          LEFT JOIN modules m ON m.id_classe = i.id_classe
          LEFT JOIN notes n ON n.id_inscription = i.id_inscription AND n.id_module = m.id_module
          WHERE u.id_utilisateur = ?
          GROUP BY e.id_etudiant, e.matricule, e.nom, e.prenom, i.id_inscription, c.nom, a.libelle
          ORDER BY i.id_inscription DESC LIMIT 1";
$stmt = mysqli_prepare($connexion, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_utilisateur);
mysqli_stmt_execute($stmt);
$etudiant = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

$paiements = [];
if ($etudiant && $etudiant['id_inscription']) {
    $stmt = mysqli_prepare($connexion, 'SELECT id_paiement, montant_paye, montant_total, date_paiement, mode_paiement FROM paiements WHERE id_inscription = ? ORDER BY date_paiement DESC');
    mysqli_stmt_bind_param($stmt, 'i', $etudiant['id_inscription']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) $paiements[] = $row;
    mysqli_stmt_close($stmt);
}
$bulletin_pret = $etudiant && $etudiant['total_modules'] > 0 && $etudiant['total_modules'] == $etudiant['modules_evalues'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon espace étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark"><div class="container-fluid"><span class="navbar-brand">Espace étudiant</span><a class="btn btn-outline-light btn-sm" href="../logout.php">Se déconnecter</a></div></nav>
<main class="container py-4">
    <?php if (!$etudiant): ?>
        <div class="alert alert-warning">Votre compte n’est pas encore relié à une inscription.</div>
    <?php else: ?>
        <div class="mb-4"><h1 class="h3 mb-1"><?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></h1><span class="text-muted">Matricule : <?= htmlspecialchars($etudiant['matricule']) ?> | Classe : <?= htmlspecialchars($etudiant['classe']) ?></span></div>
        <div class="row g-4">
            <div class="col-lg-6"><section class="card shadow-sm h-100"><div class="card-body"><h2 class="h5">Bulletin de notes</h2><?php if ($bulletin_pret): ?><p class="text-success">Toutes les matières ont une moyenne.</p><div class="d-flex gap-2 flex-wrap"><a target="_blank" rel="noopener" class="btn btn-outline-primary" href="../notes/releve_note_pdf.php?id_inscription=<?= (int) $etudiant['id_inscription'] ?>&apercu=1"><i class="bi bi-eye me-1"></i>Aperçu</a><a class="btn btn-primary" href="../notes/releve_note_pdf.php?id_inscription=<?= (int) $etudiant['id_inscription'] ?>"><i class="bi bi-download me-1"></i>Télécharger</a></div><?php else: ?><p class="text-muted mb-0">Le bulletin sera disponible après la saisie de toutes les notes et moyennes par les enseignants.</p><?php endif; ?></div></section></div>
            <div class="col-lg-6"><section class="card shadow-sm h-100"><div class="card-body"><h2 class="h5">Mes reçus de paiement</h2><?php if (!$paiements): ?><p class="text-muted mb-0">Aucun paiement enregistré.</p><?php else: ?><div class="list-group list-group-flush"><?php foreach ($paiements as $paiement): ?><div class="list-group-item px-0 d-flex justify-content-between align-items-center flex-wrap gap-2"><span><?= htmlspecialchars($paiement['date_paiement']) ?> - <?= number_format($paiement['montant_paye'], 0, ',', ' ') ?> FCFA</span><span class="d-flex gap-2"><a target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" href="../paiements/recu_pdf.php?id_paiement=<?= (int) $paiement['id_paiement'] ?>&apercu=1"><i class="bi bi-eye"></i> Aperçu</a><a class="btn btn-sm btn-outline-secondary" href="../paiements/recu_pdf.php?id_paiement=<?= (int) $paiement['id_paiement'] ?>"><i class="bi bi-download"></i> Télécharger</a></span></div><?php endforeach; ?></div><?php endif; ?></div></section></div>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
