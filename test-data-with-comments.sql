-- ============================================
-- Données de test complètes avec commentaires
-- ============================================

USE cvtek;

-- Ajouter des versions supplémentaires aux documents
INSERT IGNORE INTO doc_version (id_doc, version, url_fichier)
VALUES 
  (1, 2.0, '/cvtek/uploads/cv-test-v2.pdf'),
  (1, 3.0, '/cvtek/uploads/cv-test-v3.pdf'),
  (2, 2.0, '/cvtek/uploads/projet-test-v2.doc'),
  (2, 3.0, '/cvtek/uploads/projet-test-v3.doc');

-- Vérifier les IDs des versions créées
SELECT id, id_doc, version, url_fichier FROM doc_version ORDER BY id;

-- Ajouter des commentaires sur les versions
-- Le professeur (ID 2) commente les documents de l'étudiant (ID 3)
INSERT IGNORE INTO commentaire (id_user, id_docversion, text, date)
VALUES 
  (2, 1, 'Très bon CV! Quelques suggestions de format.', NOW()),
  (2, 2, 'Version 2 du CV est mieux. Ajoute une section "compétences".', NOW()),
  (2, 3, 'Parfait! Prêt pour les candidatures.', NOW()),
  (2, 4, 'Bon projet. Explique mieux les choix techniques.', NOW()),
  (2, 5, 'Excellent travail de documentation!', NOW());

-- Créer une seconde version du prof pour tester
INSERT IGNORE INTO users (username, email, password_hash, role) 
VALUES ('professor2', 'prof2@cvtek.local', '$2y$10$8/LD0r2PKz3gJXQJ2I5Efe0XhKc9QVzJKW/MYZjGqHVFAV0A6VlIi', 'professor');

-- Créer une seconde version de l'étudiant
INSERT IGNORE INTO users (username, email, password_hash, role) 
VALUES ('student2', 'student2@cvtek.local', '$2y$10$SZPj1V8H5UdHOlj7L6YSz.ZYfVrL7uUzMvB6AK3C9MYrLs5Yw7pJm', 'student');

-- Créer des abonnements pour tester
INSERT IGNORE INTO abonnement (id_prof, id_user)
VALUES 
  (2, 3),  -- Prof 1 abonné à Étudiant 1
  (2, 4),  -- Prof 1 abonné à Étudiant 2
  (5, 3),  -- Prof 2 abonné à Étudiant 1
  (5, 4);  -- Prof 2 abonné à Étudiant 2

-- Vérifier les données
SELECT 'Users:' as '';
SELECT id, username, role FROM users;

SELECT 'Documents:' as '';
SELECT id, user_id, nom_fichier FROM documents;

SELECT 'Doc Versions:' as '';
SELECT id, id_doc, version FROM doc_version ORDER BY id;

SELECT 'Comments:' as '';
SELECT id, id_user, id_docversion, text FROM commentaire;
