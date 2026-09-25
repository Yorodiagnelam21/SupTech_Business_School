<?php
// includes/sidebar.php
// Menu latéral - à adapter selon le rôle de l'utilisateur connecté
// (ex: cacher certains liens si $_SESSION['role'] == 'enseignant')
$role_actuel = isset($_SESSION['role_libelle']) ? $_SESSION['role_libelle'] : '';
$id_enseignant_session = isset($_SESSION['id_enseignant']) ? $_SESSION['id_enseignant'] : 0;
$id_etudiant_session = isset($_SESSION['id_etudiant']) ? $_SESSION['id_etudiant'] : 0;
?>
<nav id="sidebarMenu" class="col-md-2 bg-light sidebar collapse">
    <div class="position-sticky">
        <!-- Menu de navigation -->
        <ul class="nav flex-column">
            <?php if ($role_actuel === 'etudiant'): ?>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/etudiants/espace.php"><i class="bi bi-person-vcard me-2"></i>Mon espace étudiant</a></li>
            <?php elseif ($role_actuel === 'enseignant'): ?>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/enseignants/profil.php?id_enseignant=<?= (int) $id_enseignant_session ?>"><i class="bi bi-person-lines-fill me-2"></i>Mon profil</a></li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/etudiants/"><i class="bi bi-people-fill me-2"></i>Étudiants</a></li>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/enseignants/"><i class="bi bi-person-badge me-2"></i>Enseignants</a></li>
                <?php if ($role_actuel === 'administrateur' || $role_actuel === 'scolarite'): ?>
                    <li class="nav-item"><a class="nav-link" href="/gestion_academique/classes/"><i class="bi bi-building me-2"></i>Classes</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/modules/"><i class="bi bi-book-fill me-2"></i>Modules</a></li>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/notes/"><i class="bi bi-file-earmark-text me-2"></i>Notes</a></li>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/paiements/"><i class="bi bi-cash-coin me-2"></i>Paiements</a></li>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/emplois/"><i class="bi bi-calendar3 me-2"></i>Emploi du temps</a></li>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/salles/"><i class="bi bi-door-closed me-2"></i>Salles</a></li>
                <li class="nav-item"><a class="nav-link" href="/gestion_academique/statistiques/"><i class="bi bi-pie-chart me-2"></i>Statistiques</a></li>
            <?php endif; ?>
        </ul>
        <hr>
        <ul class="nav flex-column mt-auto">
            <li class="nav-item">
                <a href="/gestion_academique/logout.php" class="nav-link text-danger" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');">
                    <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                </a>
            </li>
        </ul>
    </div>
</nav>
