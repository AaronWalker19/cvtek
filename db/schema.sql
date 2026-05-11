-- ============================================
-- CVTEK - Base de données SQLite
-- ============================================

-- ============================================
-- Table: users (Utilisateurs)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  prenom TEXT NOT NULL,
  mail TEXT UNIQUE NOT NULL,
  role TEXT NOT NULL CHECK(role IN ('student', 'professor', 'admin')),
  licence TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Table: documents (Fichiers/Documents)
-- ============================================
CREATE TABLE IF NOT EXISTS documents (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  nom_fichier TEXT NOT NULL,
  titre TEXT,
  type_fichier TEXT NOT NULL CHECK(type_fichier IN ('cv', 'portfolio', 'autre')),
  url_fichier TEXT NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour les requêtes fréquentes
CREATE INDEX idx_documents_user_id ON documents(user_id);
CREATE INDEX idx_documents_created_at ON documents(created_at);

-- ============================================
-- Table: comments (Commentaires validés)
-- ============================================
CREATE TABLE IF NOT EXISTS comments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  document_id INTEGER NOT NULL,
  prof_id INTEGER NOT NULL,
  contenu TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  FOREIGN KEY (prof_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour les requêtes fréquentes
CREATE INDEX idx_comments_document_id ON comments(document_id);
CREATE INDEX idx_comments_prof_id ON comments(prof_id);

-- ============================================
-- Table: comments_temp (Commentaires temporaires)
-- ============================================
CREATE TABLE IF NOT EXISTS comments_temp (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  document_id INTEGER NOT NULL,
  prof_id INTEGER NOT NULL,
  contenu TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  FOREIGN KEY (prof_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour les requêtes fréquentes
CREATE INDEX idx_comments_temp_document_id ON comments_temp(document_id);
CREATE INDEX idx_comments_temp_prof_id ON comments_temp(prof_id);
