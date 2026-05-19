<?php
// ============================================
// MIGRATION - Déplacer version 1.0 vers doc_version
// Enlever url_fichier de documents
// ============================================

header('Content-Type: application/json');

try {
    require_once 'config.php';
    require_once 'db.php';
    
    $conn = Database::getConnection();
    
    $migrationSteps = [];
    
    // Étape 1: Vérifier si url_fichier existe dans documents
    $columnsCheck = $conn->query("SHOW COLUMNS FROM documents LIKE 'url_fichier'");
    $urlFichierExists = $columnsCheck && $columnsCheck->rowCount() > 0;
    
    if ($urlFichierExists) {
        // Migrer les URLs de documents vers doc_version (version 1.0)
        $docsWithUrl = $conn->query("
            SELECT id, url_fichier, created_at
            FROM documents 
            WHERE url_fichier IS NOT NULL AND url_fichier != ''
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $migratedCount = 0;
        foreach ($docsWithUrl as $doc) {
            // Vérifier si version 1.0 existe déjà
            $check = $conn->prepare("
                SELECT COUNT(*) as cnt FROM doc_version 
                WHERE id_doc = ? AND version = 1.0
            ");
            $check->execute([$doc['id']]);
            $exists = $check->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
            
            if (!$exists) {
                $stmt = $conn->prepare("
                    INSERT INTO doc_version (id_doc, version, url_fichier, created_at)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$doc['id'], 1.0, $doc['url_fichier'], $doc['created_at']]);
                $migratedCount++;
            }
        }
        
        if ($migratedCount > 0) {
            $migrationSteps[] = "Migré $migratedCount URLs de documents vers doc_version (version 1.0)";
        } else {
            $migrationSteps[] = "Aucune URL à migrer (déjà fait ou aucune URL)";
        }
        
        // Supprimer la colonne url_fichier de documents
        $conn->exec("ALTER TABLE documents DROP COLUMN url_fichier");
        $migrationSteps[] = "Colonne url_fichier supprimée de documents";
    } else {
        $migrationSteps[] = "Colonne url_fichier n'existe pas dans documents";
    }
    
    // Étape 2: Vérifier si version existe dans documents (ancienne structure)
    $versionCheck = $conn->query("SHOW COLUMNS FROM documents LIKE 'version'");
    $versionExists = $versionCheck && $versionCheck->rowCount() > 0;
    
    if ($versionExists) {
        // Supprimer la colonne version
        $conn->exec("ALTER TABLE documents DROP COLUMN version");
        $migrationSteps[] = "Colonne version supprimée de documents";
    } else {
        $migrationSteps[] = "Colonne version n'existe pas";
    }
    
    // Étape 3: Vérifier l'intégrité
    $docCount = $conn->query("SELECT COUNT(*) as cnt FROM documents")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $versionCount = $conn->query("SELECT COUNT(*) as cnt FROM doc_version")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    // Vérifier les documents sans version (risque d'orphelins)
    $docsWithoutVersion = $conn->query("
        SELECT COUNT(*) as cnt FROM documents d
        LEFT JOIN doc_version dv ON d.id = dv.id_doc
        WHERE dv.id IS NULL
    ")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    if ($docsWithoutVersion > 0) {
        $migrationSteps[] = "⚠️ ATTENTION: $docsWithoutVersion document(s) sans version!";
    }
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Migration réussie: structure normalisée',
        'steps' => $migrationSteps,
        'stats' => [
            'documents_count' => $docCount,
            'versions_count' => $versionCount,
            'documents_without_version' => $docsWithoutVersion
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
}
?>
