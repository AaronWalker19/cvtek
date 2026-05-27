-- ============================================
-- CVTEK - Schema MySQL pour API PHP
-- ============================================
-- Exécuter avec: mysql -u root -p cvtek < schema.sql

-- Vérifier que la BD existe
CREATE DATABASE IF NOT EXISTS cvtek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cvtek;

-- ============================================
-- Table: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) UNIQUE NOT NULL COMMENT 'Identifiant unique',
  email VARCHAR(255) UNIQUE NOT NULL COMMENT 'Email unique',
  password_hash VARCHAR(255) NULLABLE COMMENT 'Hash bcrypt du mot de passe (NULL si authentification externe)',
  role VARCHAR(50) DEFAULT 'student' COMMENT 'student, professor, admin'
    CHECK(role IN ('admin', 'professor', 'student')),
  parcour VARCHAR(255) NULLABLE COMMENT 'Parcours/cursus de l\'étudiant',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX idx_username (username),
  INDEX idx_email (email),
  INDEX idx_role (role)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Utilisateurs de CVTEK';

-- ============================================
-- Table: documents
-- ============================================
CREATE TABLE IF NOT EXISTS documents (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL COMMENT 'Référence utilisateur',
  nom_fichier VARCHAR(255) NOT NULL COMMENT 'Nom original du fichier',
  titre TEXT COMMENT 'Titre du document (donné par l\'utilisateur)',
  type_fichier VARCHAR(50) NOT NULL COMMENT 'Type: pdf, doc, video, etc',
  description TEXT COMMENT 'Description optionnelle',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Contraintes
  CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  
  -- Indexes
  INDEX idx_user_id (user_id),
  INDEX idx_type_fichier (type_fichier),
  INDEX idx_created_at (created_at)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Métadonnées des documents (les fichiers/versions sont dans doc_version)';

-- ============================================
-- Table: doc_version
-- ============================================
CREATE TABLE IF NOT EXISTS doc_version (
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_doc INT NOT NULL COMMENT 'Référence au document parent',
  version DECIMAL(3,1) NOT NULL COMMENT 'Numéro de version (1.0, 2.0, 3.0, etc)',
  url_fichier VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier: /cvtek/uploads/filename.ext',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  -- Contraintes
  CONSTRAINT fk_doc_version FOREIGN KEY (id_doc) REFERENCES documents(id) ON DELETE CASCADE,
  
  -- Indexes
  INDEX idx_id_doc (id_doc),
  INDEX idx_version (version),
  INDEX idx_created_at (created_at),
  UNIQUE KEY uk_doc_version (id_doc, version)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Toutes les versions des documents (1.0, 2.0, 3.0, etc)';

-- ============================================
-- Table: commentaire
-- ============================================
CREATE TABLE IF NOT EXISTS commentaire (
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_user INT NOT NULL COMMENT 'Référence utilisateur',
  id_docversion INT NOT NULL COMMENT 'Référence version du document',
  text LONGTEXT NOT NULL COMMENT 'Contenu du commentaire',
  date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création du commentaire',
  
  -- Contraintes
  CONSTRAINT fk_commentaire_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_commentaire_docversion FOREIGN KEY (id_docversion) REFERENCES doc_version(id) ON DELETE CASCADE,
  
  -- Indexes
  INDEX idx_id_user (id_user),
  INDEX idx_id_docversion (id_docversion),
  INDEX idx_date (date)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Commentaires sur les versions de documents';

-- ============================================
-- Table: abonnement
-- ============================================
CREATE TABLE IF NOT EXISTS abonnement (
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_prof INT NOT NULL COMMENT 'Référence professeur',
  id_user INT NOT NULL COMMENT 'Référence utilisateur/étudiant',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d\'abonnement',
  
  -- Contraintes
  CONSTRAINT fk_abonnement_prof FOREIGN KEY (id_prof) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_abonnement_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
  
  -- Indexes
  INDEX idx_id_prof (id_prof),
  INDEX idx_id_user (id_user),
  INDEX idx_created_at (created_at),
  UNIQUE KEY uk_prof_user (id_prof, id_user)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Abonnements entre professeurs et étudiants';

-- ============================================
-- Utilisateurs de test
-- ============================================

-- Admin (password: admin123)
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
  ('admin', 'admin@cvtek.local', '$2y$10$zNO1l.BDHtM.sVKKy9n.0OJlx5bC6VvgRVKmxGMLdgDOVJeWzYsXu', 'admin');

-- Professor (password: prof123)
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
  ('professor1', 'prof@cvtek.local', '$2y$10$8/LD0r2PKz3gJXQJ2I5Efe0XhKc9QVzJKW/MYZjGqHVFAV0A6VlIi', 'professor');

-- Student (password: student123)
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES 
  ('student1', 'student@cvtek.local', '$2y$10$SZPj1V8H5UdHOlj7L6YSz.ZYfVrL7uUzMvB6AK3C9MYrLs5Yw7pJm', 'student');

-- ============================================
-- Documents de test
-- ============================================

INSERT IGNORE INTO documents (user_id, nom_fichier, titre, type_fichier, description) 
VALUES 
  (3, 'cv.pdf', 'Mon CV', 'pdf', 'CV de test'),
  (3, 'projet.doc', 'Projet Final', 'doc', 'Projet de fin d\'études');

-- ============================================
-- Versions de test
-- ============================================

INSERT IGNORE INTO doc_version (id_doc, version, url_fichier)
VALUES 
  (1, 1.0, '/cvtek/uploads/cv-test.pdf'),
  (2, 1.0, '/cvtek/uploads/projet-test.doc');

-- ============================================
-- Affichage des tables créées
-- ============================================

SHOW TABLES;
SELECT COUNT(*) as users_count FROM users;
SELECT COUNT(*) as documents_count FROM documents;
SELECT COUNT(*) as doc_version_count FROM doc_version;
