<?php
// ============================================
// MIGRATION - Nouvelle structure versioning
// Migrer de version colonne à table doc_version
// ============================================

header('Content-Type: application/json');

try {
    require_once 'config.php';
    require_once 'db.php';
    
    $conn = Database::getConnection();
    
    // Étape 1: Vérifier si la table doc_version existe
    $check = $conn->query("SHOW TABLES LIKE 'doc_version'");
    $tableExists = $check && $check->rowCount() > 0;
    
    $migrationSteps = [];
    
    // Étape 2: Si la table n'existe pas, la créer
    if (!$tableExists) {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS doc_version (
                id INT PRIMARY KEY AUTO_INCREMENT,
                id_doc INT NOT NULL COMMENT 'Référence au document parent',
                version DECIMAL(3,1) NOT NULL COMMENT 'Numéro de version (1.0, 2.0, etc)',
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
            COMMENT='Versions des documents'
        ");
        
        $migrationSteps[] = 'Table doc_version créée';
    } else {
        $migrationSteps[] = 'Table doc_version existe déjà';
    }
    
    // Étape 2b: Créer la table commentaire si elle n'existe pas
    $checkComment = $conn->query("SHOW TABLES LIKE 'commentaire'");
    $commentTableExists = $checkComment && $checkComment->rowCount() > 0;
    
    if (!$commentTableExists) {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS commentaire (
                id INT PRIMARY KEY AUTO_INCREMENT,
                id_user INT NOT NULL,
                id_docversion INT NOT NULL,
                text LONGTEXT NOT NULL,
                date DATETIME DEFAULT CURRENT_TIMESTAMP,
                
                CONSTRAINT fk_commentaire_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_commentaire_docversion FOREIGN KEY (id_docversion) REFERENCES doc_version(id) ON DELETE CASCADE,
                
                INDEX idx_id_user (id_user),
                INDEX idx_id_docversion (id_docversion),
                INDEX idx_date (date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Commentaires sur les versions de documents'
        ");
        
        $migrationSteps[] = 'Table commentaire créée';
    } else {
        $migrationSteps[] = 'Table commentaire existe déjà';
    }
    
    // Étape 3: Migrer les données existantes de documents
    // Récupérer tous les documents qui ne sont pas des versions (parent_document_id IS NULL)
    $oldDocs = $conn->query("
        SELECT id, version, url_fichier, created_at 
        FROM documents 
        WHERE parent_document_id IS NULL 
        ORDER BY id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $migratedCount = 0;
    
    foreach ($oldDocs as $doc) {
        $docId = $doc['id'];
        $version = $doc['version'] ?? 1.0;
        $urlFichier = $doc['url_fichier'];
        $createdAt = $doc['created_at'];
        
        // Vérifier si la version existe déjà
        $check = $conn->prepare("
            SELECT COUNT(*) as cnt FROM doc_version 
            WHERE id_doc = ? AND version = ?
        ");
        $check->execute([$docId, $version]);
        $exists = $check->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        
        if (!$exists) {
            $stmt = $conn->prepare("
                INSERT INTO doc_version (id_doc, version, url_fichier, created_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$docId, $version, $urlFichier, $createdAt]);
            $migratedCount++;
        }
    }
    
    if ($migratedCount > 0) {
        $migrationSteps[] = "Migré $migratedCount documents vers doc_version";
    } else {
        $migrationSteps[] = "Aucun document à migrer (déjà fait ou aucun document)";
    }
    
    // Étape 4: Optionnel - Nettoyer les colonnes anciennes (décommenter si nécessaire)
    // $conn->exec("ALTER TABLE documents DROP COLUMN version");
    // $conn->exec("ALTER TABLE documents DROP COLUMN url_fichier");
    // $conn->exec("ALTER TABLE documents DROP COLUMN parent_document_id");
    // $migrationSteps[] = "Colonnes anciennes supprimées";
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Migration versioning réussie',
        'steps' => $migrationSteps,
        'migratedDocuments' => $migratedCount,
        'nextStep' => 'Les anciens documents sont maintenant accessible via doc_version'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>
