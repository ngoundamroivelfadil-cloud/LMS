-- ============================================
--  EduLearn LMS - Base de donnees v3
--  Pour usage LOCAL (XAMPP) : garde CREATE DATABASE + USE
--  Pour usage EN LIGNE (FreeSQLDatabase/PlanetScale) :
--    supprimez les 2 premieres lignes avant import
-- ============================================
CREATE DATABASE IF NOT EXISTS edulearn_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE edulearn_db;

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('promoteur','enseignant','etudiant') NOT NULL DEFAULT 'etudiant',
    avatar VARCHAR(500) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS connexions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    ip VARCHAR(45),
    navigateur VARCHAR(255),
    date_connexion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    id_promoteur INT NOT NULL,
    note_validation INT DEFAULT 50,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_promoteur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    id_module INT DEFAULT NULL,
    id_enseignant INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_module) REFERENCES modules(id) ON DELETE SET NULL,
    FOREIGN KEY (id_enseignant) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lecons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    type_contenu ENUM('pdf','video_url','video_fichier') NOT NULL DEFAULT 'pdf',
    fichier_pdf VARCHAR(500) DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT NULL,
    video_fichier VARCHAR(500) DEFAULT NULL,
    ordre INT DEFAULT 1,
    id_cours INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cours) REFERENCES cours(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    id_lecon INT NOT NULL UNIQUE,
    duree_minutes INT DEFAULT 30,
    note_passage INT DEFAULT 50,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_lecon) REFERENCES lecons(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_evaluation INT NOT NULL,
    type ENUM('qcm','ouverte') NOT NULL DEFAULT 'qcm',
    question TEXT NOT NULL,
    reponse_a VARCHAR(300) DEFAULT NULL,
    reponse_b VARCHAR(300) DEFAULT NULL,
    reponse_c VARCHAR(300) DEFAULT NULL,
    reponse_d VARCHAR(300) DEFAULT NULL,
    bonne_reponse ENUM('a','b','c','d') DEFAULT NULL,
    points INT DEFAULT 1,
    FOREIGN KEY (id_evaluation) REFERENCES evaluations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NOT NULL,
    id_cours INT NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_inscription (id_etudiant, id_cours),
    FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_cours) REFERENCES cours(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lecons_vues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NOT NULL,
    id_lecon INT NOT NULL,
    date_vue TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vue (id_etudiant, id_lecon),
    FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_lecon) REFERENCES lecons(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS resultats_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NOT NULL,
    id_evaluation INT NOT NULL,
    score DECIMAL(5,2) DEFAULT 0,
    score_max DECIMAL(5,2) DEFAULT 0,
    pourcentage DECIMAL(5,2) DEFAULT 0,
    reussi TINYINT(1) DEFAULT 0,
    date_passage TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_evaluation) REFERENCES evaluations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reponses_ouvertes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_resultat INT NOT NULL,
    id_question INT NOT NULL,
    reponse TEXT,
    note_obtenue DECIMAL(5,2) DEFAULT NULL,
    corrige TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_resultat) REFERENCES resultats_evaluations(id) ON DELETE CASCADE,
    FOREIGN KEY (id_question) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS certificats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant INT NOT NULL,
    id_module INT NOT NULL,
    code_unique VARCHAR(50) NOT NULL UNIQUE,
    date_obtention TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_certificat (id_etudiant, id_module),
    FOREIGN KEY (id_etudiant) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_expediteur INT NOT NULL,
    id_destinataire INT NOT NULL,
    sujet VARCHAR(200) NOT NULL,
    contenu TEXT NOT NULL,
    lu TINYINT(1) DEFAULT 0,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_expediteur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_destinataire) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS commentaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_lecon INT NOT NULL,
    id_auteur INT NOT NULL,
    contenu TEXT NOT NULL,
    date_commentaire TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_lecon) REFERENCES lecons(id) ON DELETE CASCADE,
    FOREIGN KEY (id_auteur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
