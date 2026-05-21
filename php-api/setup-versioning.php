<?php
// ============================================
// SETUP - Initialiser le système de versioning
// Appel automatique pour préparer la BD
// ============================================

header('Content-Type: application/json');

try {
    require_once 'config.php';
    require_once 'db.php';
    
    $conn = Database::getConnection();
    
    $results = [
        'migration' => null,
        'check' => null
    ];
    
    // Étape 1: Créer la table si elle n'existe pas
    try {
        $check = $conn->query("SHOW TABLES LIKE 'doc_version'");
        $tableExists = $check && $check->rowCount() > 0;
        
        if (!$tableExists) {
            $conn->exec("
                CREATE TABLE IF NOT EXISTS doc_version (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    id_doc INT NOT NULL COMMENT 'Référence au document parent',
                    version DECIMAL(3,1) NOT NULL COMMENT 'Numéro de version (1.0, 2.0, etc)',
                    url_fichier VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    
                    CONSTRAINT fk_doc_version FOREIGN KEY (id_doc) REFERENCES documents(id) ON DELETE CASCADE,
                    
                    INDEX idx_id_doc (id_doc),
                    INDEX idx_version (version),
                    INDEX idx_created_at (created_at),
                    UNIQUE KEY uk_doc_version (id_doc, version)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results['migration']['table_created'] = true;
        } else {
            $results['migration']['table_exists'] = true;
        }
    } catch (Exception $e) {
        $results['migration']['error'] = $e->getMessage();
    }
    
    // Créer la table commentaire si elle n'existe pas
    try {
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
            ");
            $results['migration']['commentaire_table_created'] = true;
        } else {
            $results['migration']['commentaire_table_exists'] = true;
        }
    } catch (Exception $e) {
        $results['migration']['commentaire_error'] = $e->getMessage();
    }
    
    // Étape 2: Migrer les données existantes
    try {
        // Récupérer les documents qui n'ont pas de version migrée
        $missingVersions = $conn->query("
            SELECT d.id, d.version, d.url_fichier, d.created_at
            FROM documents d
            WHERE d.parent_document_id IS NULL
            AND NOT EXISTS (
                SELECT 1 FROM doc_version dv WHERE dv.id_doc = d.id
            )
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $migratedCount = 0;
        foreach ($missingVersions as $doc) {
            $stmt = $conn->prepare("
                INSERT INTO doc_version (id_doc, version, url_fichier, created_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $doc['id'],
                (float)($doc['version'] ?? 1.0),
                $doc['url_fichier'],
                $doc['created_at']
            ]);
            $migratedCount++;
        }
        
        $results['migration']['documents_migrated'] = $migratedCount;
        
    } catch (Exception $e) {
        $results['migration']['migration_error'] = $e->getMessage();
    }
    
    // Étape 3: Vérifier l'état
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'doc_version'");
        $docsCheck = $conn->query("SELECT COUNT(*) as cnt FROM doc_version")->fetch(PDO::FETCH_ASSOC);
        
        $results['check'] = [
            'table_exists' => $tableCheck && $tableCheck->rowCount() > 0,
            'total_versions' => (int)$docsCheck['cnt']
        ];
    } catch (Exception $e) {
        $results['check']['error'] = $e->getMessage();
    }
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Initialisation du versioning réussie',
        'results' => $results
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
