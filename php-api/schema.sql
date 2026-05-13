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
  password VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt du mot de passe',
  role VARCHAR(50) DEFAULT 'student' COMMENT 'student, professor, admin'
    CHECK(role IN ('admin', 'professor', 'student')),
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
  url_fichier VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier: /uploads/filename.ext',
  description TEXT COMMENT 'Description optionnelle',
  version INT DEFAULT 1 COMMENT 'Numéro de version',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Contraintes
  CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  
  -- Indexes
  INDEX idx_user_id (user_id),
  INDEX idx_type_fichier (type_fichier),
  INDEX idx_created_at (created_at)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Documents/fichiers uploadés par les utilisateurs';

-- ============================================
-- Table: comments (optionnel - mentionné dans Express)
-- ============================================
CREATE TABLE IF NOT EXISTS comments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  document_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_doc_comment FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_comment FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  
  INDEX idx_document_id (document_id),
  INDEX idx_user_id (user_id)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Commentaires sur les documents (optionnel)';

-- ============================================
-- Utilisateurs de test
-- ============================================

-- Admin (password: admin123)
INSERT IGNORE INTO users (username, email, password, role) VALUES 
  ('admin', 'admin@cvtek.local', '$2y$10$zNO1l.BDHtM.sVKKy9n.0OJlx5bC6VvgRVKmxGMLdgDOVJeWzYsXu', 'admin');

-- Professor (password: prof123)
INSERT IGNORE INTO users (username, email, password, role) VALUES 
  ('professor1', 'prof@cvtek.local', '$2y$10$8/LD0r2PKz3gJXQJ2I5Efe0XhKc9QVzJKW/MYZjGqHVFAV0A6VlIi', 'professor');

-- Student (password: student123)
INSERT IGNORE INTO users (username, email, password, role) VALUES 
  ('student1', 'student@cvtek.local', '$2y$10$SZPj1V8H5UdHOlj7L6YSz.ZYfVrL7uUzMvB6AK3C9MYrLs5Yw7pJm', 'student');

-- ============================================
-- Documents de test
-- ============================================

INSERT IGNORE INTO documents (user_id, nom_fichier, titre, type_fichier, url_fichier, description, version) 
VALUES 
  (3, 'cv.pdf', 'Mon CV', 'pdf', '/uploads/cv-test.pdf', 'CV de test', 1),
  (3, 'projet.doc', 'Projet Final', 'doc', '/uploads/projet-test.doc', 'Projet de fin d\'études', 2);

-- ============================================
-- Affichage des tables créées
-- ============================================

SHOW TABLES;
SELECT COUNT(*) as users_count FROM users;
SELECT COUNT(*) as documents_count FROM documents;
