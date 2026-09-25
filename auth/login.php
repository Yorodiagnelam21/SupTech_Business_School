<?php
// auth/login.php
session_start();

// Si déjà connecté, rediriger vers index.php (qui gère la redirection par rôle)
if (isset($_SESSION['id_utilisateur'])) {
    header('Location: ../index.php');
    exit;
}

// Connexion BDD
require_once __DIR__ . '/../config/database.php';

$id_etudiant_column = mysqli_query($connexion, "SHOW COLUMNS FROM utilisateurs LIKE 'id_etudiant'");
if ($id_etudiant_column && mysqli_num_rows($id_etudiant_column) === 0) {
    mysqli_query($connexion, "ALTER TABLE utilisateurs ADD COLUMN id_etudiant INT NULL");
}
mysqli_query($connexion, "INSERT IGNORE INTO roles (libelle) VALUES ('administrateur'), ('scolarite'), ('enseignant'), ('etudiant')");

$default_role_map = [
    'administrateur' => 'admin@suptech.sn',
    'scolarite' => 'scolarite@suptech.sn',
    'enseignant' => 'enseignant@suptech.sn',
    'etudiant' => 'etudiant@suptech.sn',
];

foreach ($default_role_map as $role_libelle => $email) {
    $role_result = mysqli_query($connexion, "SELECT id_role FROM roles WHERE libelle = '$role_libelle' LIMIT 1");
    $role_row = $role_result ? mysqli_fetch_assoc($role_result) : null;
    if ($role_row) {
        $role_id = (int) $role_row['id_role'];
        $password = '123456';
        switch ($role_libelle) {
            case 'administrateur':
                $password = 'admin123';
                break;
            case 'scolarite':
                $password = 'scolarite123';
                break;
            case 'enseignant':
                $password = 'enseignant123';
                break;
            case 'etudiant':
                $password = 'etudiant123';
                break;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($connexion, "INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, actif)
            VALUES ('Default', '$role_libelle', '$email', '$hash', $role_id, 1)");
    }
}

// ============================================================
// SEEDING AUTOMATIQUE POUR LES TESTS (Si la BDD est vide ou si
// des enseignants n'ont pas encore de compte utilisateur)
// ============================================================
$check_users = mysqli_query($connexion, "SELECT COUNT(*) as count FROM utilisateurs");
if ($check_users) {
    $row_users = mysqli_fetch_assoc($check_users);
    if ($row_users['count'] == 0) {
        // S'assurer que les rôles existent
        mysqli_query($connexion, "INSERT IGNORE INTO roles (libelle) VALUES ('administrateur'), ('scolarite'), ('enseignant'), ('etudiant')");

        // Récupérer les ID des rôles
        $res_admin = mysqli_query($connexion, "SELECT id_role FROM roles WHERE libelle = 'administrateur'");
        $res_scol = mysqli_query($connexion, "SELECT id_role FROM roles WHERE libelle = 'scolarite'");
        $res_ens = mysqli_query($connexion, "SELECT id_role FROM roles WHERE libelle = 'enseignant'");

        $id_role_admin = ($r = mysqli_fetch_assoc($res_admin)) ? $r['id_role'] : 1;
        $id_role_scol = ($r = mysqli_fetch_assoc($res_scol)) ? $r['id_role'] : 2;
        $id_role_ens = ($r = mysqli_fetch_assoc($res_ens)) ? $r['id_role'] : 3;

        // Créer plusieurs enseignants de test pour vérifier qu'une pluralité d'enseignants peut se connecter
        $teachers = [
            ['ENS-001', 'Diop', 'Moussa', 'Professeur', '771234567', 'moussa.diop@suptech.sn', 'Algorithmique'],
            ['ENS-002', 'Fall', 'Amina', 'Maître de conférences', '761234567', 'amina.fall@suptech.sn', 'Base de données'],
            ['ENS-003', 'Sarr', 'Ibrahima', 'Professeur associé', '781234567', 'ibrahima.sarr@suptech.sn', 'Réseaux']
        ];

        $teacher_ids = [];
        foreach ($teachers as $teacher) {
            mysqli_query($connexion, "INSERT IGNORE INTO enseignants (matricule, nom, prenom, grade, telephone, email, specialite) 
                                      VALUES ('{$teacher[0]}', '{$teacher[1]}', '{$teacher[2]}', '{$teacher[3]}', '{$teacher[4]}', '{$teacher[5]}', '{$teacher[6]}')");

            $res_teacher = mysqli_query($connexion, "SELECT id_enseignant FROM enseignants WHERE matricule = '{$teacher[0]}' LIMIT 1");
            if ($res_teacher && $t_row = mysqli_fetch_assoc($res_teacher)) {
                $teacher_ids[] = (int) $t_row['id_enseignant'];
            }
        }

        // Hachage des mots de passe
        $pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
        $pass_scol = password_hash('scolarite123', PASSWORD_DEFAULT);

        // Insérer les utilisateurs
        mysqli_query($connexion, "INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, actif) 
                                  VALUES ('System', 'Admin', 'admin@suptech.sn', '$pass_admin', $id_role_admin, 1)");

        mysqli_query($connexion, "INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, actif) 
                                  VALUES ('Service', 'Scolarité', 'scolarite@suptech.sn', '$pass_scol', $id_role_scol, 1)");

        $pass_ens_default = password_hash('enseignant123', PASSWORD_DEFAULT);
        mysqli_query($connexion, "INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, actif)
                                  VALUES ('Professeur', 'Default', 'enseignant@suptech.sn', '$pass_ens_default', $id_role_ens, 1)");

        $pass_etud_default = password_hash('etudiant123', PASSWORD_DEFAULT);
        mysqli_query($connexion, "INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, actif)
                                  VALUES ('Etudiant', 'Default', 'etudiant@suptech.sn', '$pass_etud_default', (SELECT id_role FROM roles WHERE libelle = 'etudiant'), 1)");

        foreach ($teachers as $index => $teacher) {
            $pass_ens = password_hash('enseignant123', PASSWORD_DEFAULT);
            $teacher_email = $teacher[5];
            $teacher_id = isset($teacher_ids[$index]) ? $teacher_ids[$index] : 0;

            if ($teacher_id) {
                mysqli_query($connexion, "INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, actif, id_enseignant) 
                                          VALUES ('{$teacher[1]}', '{$teacher[2]}', '{$teacher_email}', '$pass_ens', $id_role_ens, 1, $teacher_id)");
            }
        }

        // 1. Année universitaire de démonstration
        mysqli_query($connexion, "INSERT IGNORE INTO annees_universitaires (libelle, date_debut, date_fin, active) 
                  VALUES ('2025-2026', '2025-09-01', '2026-06-30', 1)");
        $res_annee = mysqli_query($connexion, "SELECT id_annee FROM annees_universitaires WHERE active = 1 LIMIT 1");
        $id_annee = ($r = mysqli_fetch_assoc($res_annee)) ? $r['id_annee'] : 1;

        // 2. Classe de test
        mysqli_query($connexion, "INSERT IGNORE INTO classes (nom, filiere, niveau, effectif_max) 
                                  VALUES ('L3 - Dev Web', 'Développement Web', 'L3', 30)");
        $res_classe = mysqli_query($connexion, "SELECT id_classe FROM classes WHERE nom = 'L3 - Dev Web' LIMIT 1");
        $id_classe = ($r = mysqli_fetch_assoc($res_classe)) ? $r['id_classe'] : 1;

        // 3. Module de test
        mysqli_query($connexion, "INSERT IGNORE INTO modules (nom, id_classe, coefficient, nb_heures) 
                                  VALUES ('Algorithmique', $id_classe, 3.0, 40)");
        $res_module = mysqli_query($connexion, "SELECT id_module FROM modules WHERE nom = 'Algorithmique' AND id_classe = $id_classe LIMIT 1");
        $id_module = ($r = mysqli_fetch_assoc($res_module)) ? $r['id_module'] : 1;

        // 4. Associer les enseignants au module
        foreach ($teacher_ids as $teacher_id) {
            if ($teacher_id && $id_module) {
                mysqli_query($connexion, "INSERT IGNORE INTO enseignant_module (id_enseignant, id_module) 
                                          VALUES ($teacher_id, $id_module)");
            }
        }

        // 5. Étudiants et inscriptions
        $students = [
            ['ETU-001', 'Sow', 'Fatoumata', '2002-05-15', 'F', 'fatou.sow@suptech.sn', 'Dakar'],
            ['ETU-002', 'Ndiaye', 'Cheikh', '2003-09-20', 'M', 'cheikh.ndiaye@suptech.sn', 'Thies'],
            ['ETU-003', 'Diallo', 'Aissatou', '2002-12-01', 'F', 'aissatou.diallo@suptech.sn', 'Dakar']
        ];

        foreach ($students as $stud) {
            mysqli_query($connexion, "INSERT IGNORE INTO etudiants (matricule, nom, prenom, date_naissance, sexe, email, adresse) 
                                      VALUES ('{$stud[0]}', '{$stud[1]}', '{$stud[2]}', '{$stud[3]}', '{$stud[4]}', '{$stud[5]}', '{$stud[6]}')");

            $res_s = mysqli_query($connexion, "SELECT id_etudiant FROM etudiants WHERE matricule = '{$stud[0]}' LIMIT 1");
            if ($res_s && $s_row = mysqli_fetch_assoc($res_s)) {
                $id_etudiant = $s_row['id_etudiant'];
                mysqli_query($connexion, "INSERT IGNORE INTO inscriptions (id_etudiant, id_classe, id_annee, date_inscription) 
                                          VALUES ($id_etudiant, $id_classe, $id_annee, '2025-10-02')");
            }
        }
    }
}

// Vérifier que tous les enseignants du système disposent bien d'un compte utilisateur
$teacher_accounts_query = mysqli_query($connexion, "SELECT e.id_enseignant, e.nom, e.prenom, e.email
                                                  FROM enseignants e
                                                  LEFT JOIN utilisateurs u ON u.id_enseignant = e.id_enseignant
                                                       AND u.id_role = (SELECT id_role FROM roles WHERE libelle = 'enseignant')
                                                  WHERE u.id_utilisateur IS NULL");
if ($teacher_accounts_query) {
    while ($teacher = mysqli_fetch_assoc($teacher_accounts_query)) {
        $teacher_id = (int) $teacher['id_enseignant'];
        $teacher_name = trim($teacher['nom']);
        $teacher_firstname = trim($teacher['prenom']);
        $teacher_email = trim($teacher['email']);
        if ($teacher_email === '') {
            $teacher_email = strtolower($teacher_firstname . '.' . $teacher_name . '@suptech.sn');
        }
        $teacher_email = str_replace(' ', '', $teacher_email);

        $teacher_pass = password_hash('enseignant123', PASSWORD_DEFAULT);
        mysqli_query($connexion, "INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, id_role, actif, id_enseignant)
                                  VALUES ('{$teacher_name}', '{$teacher_firstname}', '{$teacher_email}', '$teacher_pass', (SELECT id_role FROM roles WHERE libelle = 'enseignant'), 1, $teacher_id)");
    }
}

// ============================================================
// TRAITEMENT DE LA CONNEXION (POST)
// ============================================================
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';

    if ($email && !empty($mot_de_passe)) {
        // Préparer la requête SQL pour récupérer l'utilisateur et son libellé de rôle
        $query = "SELECT u.*, r.libelle as role_libelle 
                  FROM utilisateurs u 
                  JOIN roles r ON u.id_role = r.id_role 
                  WHERE u.email = ? LIMIT 1";
        
        if ($stmt = mysqli_prepare($connexion, $query)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user) {
                // Vérifier si le compte est actif
                if ($user['actif'] == 0) {
                    $erreur = "Votre compte a été désactivé. Veuillez contacter l'administrateur.";
                } elseif (password_verify($mot_de_passe, $user['mot_de_passe'])) {
                    // Authentification réussie - Régénérer l'ID de session pour la sécurité
                    session_regenerate_id(true);

                    $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['prenom'] = $user['prenom'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['id_role'] = $user['id_role'];
                    $_SESSION['role_libelle'] = $user['role_libelle'];
                    $_SESSION['id_enseignant'] = $user['id_enseignant'];
                    $_SESSION['id_etudiant'] = $user['id_etudiant'];

                    // Redirection vers l'index pour routage par rôle
                    header('Location: ../index.php');
                    exit;
                } else {
                    $erreur = "Adresse e-mail ou mot de passe incorrect.";
                }
            } else {
                $erreur = "Adresse e-mail ou mot de passe incorrect.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $erreur = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
        }
    } else {
        $erreur = "Veuillez remplir correctement tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SupTech Business School</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --card-bg: rgba(255, 255, 255, 0.85);
            --text-main: #2b3a4a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }

        .brand-header {
            background: rgba(30, 60, 114, 0.05);
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .brand-title {
            color: #1e3c72;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .login-form-body {
            padding: 30px;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: #1e3c72;
        }

        .form-control:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 0.25rem rgba(42, 82, 152, 0.25);
        }

        .btn-login {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: filter 0.2s ease, transform 0.1s ease;
        }

        .btn-login:hover {
            filter: brightness(1.1);
            color: white;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: #6c757d;
            font-size: 1.2rem;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #1e3c72;
        }

        .form-floating {
            position: relative;
        }

        .alert {
            font-size: 0.9rem;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-footer {
            font-size: 0.75rem;
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.7);
        }

        .demo-credentials {
            font-size: 0.8rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
            padding: 10px;
            margin-top: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="brand-header">
            <h1 class="brand-title">SupTech Business School</h1>
            <p class="brand-subtitle">Gestion Académique & Administrative</p>
        </div>

        <div class="login-form-body">
            <!-- Message d'erreur -->
            <?php if (!empty($erreur)): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($erreur) ?></div>
                </div>
            <?php endif; ?>

            <!-- Alerte JS dynamique -->
            <div id="js-alert" class="alert alert-warning d-none align-items-center" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <span id="js-alert-text"></span>
            </div>

            <form action="" method="POST" id="loginForm">
                <!-- E-mail -->
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required autocomplete="email">
                    <label for="email"><i class="bi bi-envelope-fill me-2"></i>Adresse e-mail</label>
                </div>

                <!-- Mot de passe -->
                <div class="form-floating mb-4 position-relative">
                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" placeholder="Mot de passe" required autocomplete="current-password">
                    <label for="mot_de_passe"><i class="bi bi-lock-fill me-2"></i>Mot de passe</label>
                    <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                </div>

                <!-- Bouton se connecter -->
                <button type="submit" class="btn btn-login w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </button>
            </form>

            <div class="text-center mb-3">
                <a href="register.php">Créer un compte étudiant</a>
            </div>

            <!-- Comptes de démonstration / de test -->
            <div class="demo-credentials">
                <div class="text-center font-weight-bold mb-2 text-secondary"><strong>Comptes de test (Seeding automatique) :</strong></div>
                <ul class="list-unstyled mb-2 text-secondary" style="font-size: 0.75rem;">
                    <li><i class="bi bi-person-fill-lock"></i> Admin : <code>admin@suptech.sn</code> / <code>admin123</code></li>
                    <li><i class="bi bi-people-fill"></i> Scolarité : <code>scolarite@suptech.sn</code> / <code>scolarite123</code></li>
                    <li><i class="bi bi-journal-check"></i> Enseignant : <code>enseignant@suptech.sn</code> / <code>enseignant123</code></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="info-footer">
        &copy; 2026 - SupTech Business School. Tous droits réservés.
    </div>
</div>

<!-- JavaScript pour l'interactivité et la validation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#mot_de_passe');
        const loginForm = document.querySelector('#loginForm');
        const emailInput = document.querySelector('#email');
        const jsAlert = document.querySelector('#js-alert');
        const jsAlertText = document.querySelector('#js-alert-text');

        // Basculer l'affichage du mot de passe
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Changer l'icône
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // Validation dynamique côté client
        loginForm.addEventListener('submit', function(event) {
            let errors = [];
            jsAlert.classList.add('d-none');

            // Validation de l'email
            const emailValue = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailValue) {
                errors.push("L'adresse e-mail est obligatoire.");
            } else if (!emailRegex.test(emailValue)) {
                errors.push("Veuillez saisir une adresse e-mail valide.");
            }

            // Validation du mot de passe
            const passwordValue = passwordInput.value;
            if (!passwordValue) {
                errors.push("Le mot de passe est obligatoire.");
            }

            if (errors.length > 0) {
                event.preventDefault(); // Bloquer l'envoi du formulaire
                jsAlertText.innerText = errors.join(" ");
                jsAlert.classList.remove('d-none');
                jsAlert.classList.add('d-flex');
            }
        });
    });
</script>
</body>
</html>
