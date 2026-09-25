-- ============================================================
-- Base de données : gestion_academique
-- Projet : SupTech Business School
-- ============================================================

CREATE DATABASE IF NOT EXISTS gestion_academique
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gestion_academique;

-- ============================================================
-- 1. ROLES
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE  -- 'administrateur', 'scolarite', 'enseignant', 'etudiant'
) ENGINE=InnoDB;

-- ============================================================
-- 2. UTILISATEURS
-- Table de connexion (login). Un enseignant a un compte utilisateur
-- lié via id_enseignant (nullable, car admin/scolarité n'en ont pas besoin)
-- ============================================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,  -- password_hash() en PHP, jamais en clair
    id_role INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_role) REFERENCES roles(id_role)
) ENGINE=InnoDB;

-- ============================================================
-- 3. ANNEES_UNIVERSITAIRES
-- ============================================================
CREATE TABLE IF NOT EXISTS annees_universitaires (
    id_annee INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(20) NOT NULL UNIQUE, -- ex: '2025-2026'
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    active TINYINT(1) DEFAULT 0 -- année en cours
) ENGINE=InnoDB;

INSERT IGNORE INTO annees_universitaires (libelle, date_debut, date_fin, active)
VALUES ('2025-2026', '2025-09-01', '2026-06-30', 1);

-- ============================================================
-- 4. CLASSES
-- ============================================================
CREATE TABLE IF NOT EXISTS classes (
    id_classe INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    filiere VARCHAR(100) NOT NULL,
    niveau VARCHAR(20) NOT NULL, -- L1, L2, L3...
    effectif_max INT NOT NULL
) ENGINE=InnoDB;

-- ============================================================
-- 5. ENSEIGNANTS
-- ============================================================
CREATE TABLE IF NOT EXISTS enseignants (
    id_enseignant INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    grade VARCHAR(50),
    telephone VARCHAR(20),
    email VARCHAR(150),
    specialite VARCHAR(100)
) ENGINE=InnoDB;

-- Lien enseignant <-> utilisateur (compte de connexion)
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateurs'
      AND COLUMN_NAME = 'id_enseignant'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE utilisateurs ADD COLUMN id_enseignant INT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists_etudiant := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateurs'
      AND COLUMN_NAME = 'id_etudiant'
);
SET @sql := IF(@col_exists_etudiant = 0,
    'ALTER TABLE utilisateurs ADD COLUMN id_etudiant INT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateurs'
      AND CONSTRAINT_NAME = 'fk_utilisateurs_enseignants'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE utilisateurs ADD CONSTRAINT fk_utilisateurs_enseignants FOREIGN KEY (id_enseignant) REFERENCES enseignants(id_enseignant)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 6. ETUDIANTS
-- Pas de id_classe ici : la classe dépend de l'année (voir inscriptions)
-- ============================================================
CREATE TABLE IF NOT EXISTS etudiants (
    id_etudiant INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    sexe ENUM('M', 'F') NOT NULL,
    telephone VARCHAR(20),
    email VARCHAR(150),
    adresse VARCHAR(255),
    photo VARCHAR(255) DEFAULT 'default.png'
) ENGINE=InnoDB;

-- ============================================================
-- 7. MODULES
-- ============================================================
CREATE TABLE IF NOT EXISTS modules (
    id_module INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    id_classe INT NOT NULL,
    coefficient DECIMAL(3,1) NOT NULL,
    nb_heures INT NOT NULL,
    FOREIGN KEY (id_classe) REFERENCES classes(id_classe)
) ENGINE=InnoDB;

-- ============================================================
-- 8. ENSEIGNANT_MODULE (table de liaison many-to-many)
-- La contrainte "max 2 modules par enseignant" est vérifiée en PHP
-- avant l'INSERT, pas possible nativement en SQL.
-- ============================================================
CREATE TABLE IF NOT EXISTS enseignant_module (
    id_enseignant INT NOT NULL,
    id_module INT NOT NULL,
    PRIMARY KEY (id_enseignant, id_module),
    FOREIGN KEY (id_enseignant) REFERENCES enseignants(id_enseignant),
    FOREIGN KEY (id_module) REFERENCES modules(id_module)
) ENGINE=InnoDB;

-- ============================================================
-- 9. INSCRIPTIONS
-- Un étudiant = une inscription par année (donc une classe par année)
-- ============================================================
CREATE TABLE IF NOT EXISTS inscriptions (
    id_inscription INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NOT NULL,
    id_classe INT NOT NULL,
    id_annee INT NOT NULL,
    date_inscription DATE NOT NULL,
    FOREIGN KEY (id_etudiant) REFERENCES etudiants(id_etudiant),
    FOREIGN KEY (id_classe) REFERENCES classes(id_classe),
    FOREIGN KEY (id_annee) REFERENCES annees_universitaires(id_annee),
    UNIQUE KEY unique_inscription_annee (id_etudiant, id_annee) -- empêche double inscription
) ENGINE=InnoDB;

-- ============================================================
-- 10. PAIEMENTS
-- Liés à l'inscription (donc à une année précise)
-- ============================================================
CREATE TABLE IF NOT EXISTS paiements (
    id_paiement INT AUTO_INCREMENT PRIMARY KEY,
    id_inscription INT NOT NULL,
    montant_paye DECIMAL(10,2) NOT NULL,
    montant_total DECIMAL(10,2) NOT NULL, -- montant dû pour l'année, pour calculer le reste
    date_paiement DATE NOT NULL,
    mode_paiement VARCHAR(50),
    FOREIGN KEY (id_inscription) REFERENCES inscriptions(id_inscription)
) ENGINE=InnoDB;

-- ============================================================
-- 11. NOTES
-- Liées à l'inscription + au module
-- ============================================================
CREATE TABLE IF NOT EXISTS notes (
    id_note INT AUTO_INCREMENT PRIMARY KEY,
    id_inscription INT NOT NULL,
    id_module INT NOT NULL,
    cc DECIMAL(4,2),
    tp DECIMAL(4,2),
    examen DECIMAL(4,2),
    moyenne DECIMAL(4,2), -- calculée en PHP à l'enregistrement
    mention VARCHAR(30),
    FOREIGN KEY (id_inscription) REFERENCES inscriptions(id_inscription),
    FOREIGN KEY (id_module) REFERENCES modules(id_module),
    UNIQUE KEY unique_note_module (id_inscription, id_module) -- une note par module par inscription
) ENGINE=InnoDB;

-- ============================================================
-- 12. SALLES
-- ============================================================
CREATE TABLE IF NOT EXISTS salles (
    id_salle INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    capacite INT NOT NULL
) ENGINE=InnoDB;

-- ============================================================
-- 13. EMPLOI_TEMPS
-- Contraintes anti-collision (enseignant, salle, classe) gérées en PHP
-- ============================================================
CREATE TABLE IF NOT EXISTS emploi_temps (
    id_seance INT AUTO_INCREMENT PRIMARY KEY,
    jour ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    id_salle INT NOT NULL,
    id_classe INT NOT NULL,
    id_enseignant INT NOT NULL,
    id_module INT NOT NULL,
    id_annee INT NOT NULL,
    FOREIGN KEY (id_salle) REFERENCES salles(id_salle),
    FOREIGN KEY (id_classe) REFERENCES classes(id_classe),
    FOREIGN KEY (id_enseignant) REFERENCES enseignants(id_enseignant),
    FOREIGN KEY (id_module) REFERENCES modules(id_module),
    FOREIGN KEY (id_annee) REFERENCES annees_universitaires(id_annee)
) ENGINE=InnoDB;

-- ============================================================
-- Données de base (rôles obligatoires pour l'authentification)
-- ============================================================
INSERT IGNORE INTO roles (libelle) VALUES ('administrateur'), ('scolarite'), ('enseignant'), ('etudiant');
