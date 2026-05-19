<?php
// ============================================
// DIAG - Diagnostiquer l'état de la BD
// ============================================

header('Content-Type: application/json');

try {
    require_once 'config.php';
    require_once 'db.php';
    
    $conn = Database::getConnection();
    
    $diagnosis = [];
    
    // 1. Structure des tables
    $diagnosis['tables'] = [];
    
    // Vérifier documents
    $docsStructure = $conn->query("DESCRIBE documents")->fetchAll(PDO::FETCH_ASSOC);
    $diagnosis['tables']['documents'] = [
        'exists' => true,
        'columns' => array_column($docsStructure, 'Field')
    ];
    
    // Vérifier doc_version
    try {
        $versionStructure = $conn->query("DESCRIBE doc_version")->fetchAll(PDO::FETCH_ASSOC);
        $diagnosis['tables']['doc_version'] = [
            'exists' => true,
            'columns' => array_column($versionStructure, 'Field')
        ];
    } catch (Exception $e) {
        $diagnosis['tables']['doc_version'] = [
            'exists' => false,
            'error' => 'Table n\'existe pas'
        ];
    }
    
    // 2. Données
    $diagnosis['data'] = [];
    
    // Utilisateurs
    $usersCount = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $diagnosis['data']['total_users'] = (int)$usersCount;
    
    // Documents
    $docsCount = $conn->query("SELECT COUNT(*) as cnt FROM documents WHERE parent_document_id IS NULL")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $diagnosis['data']['total_documents'] = (int)$docsCount;
    
    // Versions (si table existe)
    try {
        $versionsCount = $conn->query("SELECT COUNT(*) as cnt FROM doc_version")->fetch(PDO::FETCH_ASSOC)['cnt'];
        $diagnosis['data']['total_versions'] = (int)$versionsCount;
    } catch (Exception $e) {
        $diagnosis['data']['total_versions'] = 'N/A - table n\'existe pas';
    }
    
    // 3. Détails utilisateurs avec documents
    $users = $conn->query("
        SELECT u.id, u.username, u.email, u.role, COUNT(d.id) as doc_count
        FROM users u
        LEFT JOIN documents d ON u.id = d.user_id AND d.parent_document_id IS NULL
        GROUP BY u.id
        ORDER BY u.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $diagnosis['users_details'] = $users;
    
    // 4. Vérifier les documents orphelins
    try {
        $orphans = $conn->query("
            SELECT COUNT(*) as cnt FROM documents 
            WHERE parent_document_id IS NOT NULL
        ")->fetch(PDO::FETCH_ASSOC)['cnt'];
        $diagnosis['orphaned_child_documents'] = (int)$orphans;
    } catch (Exception $e) {
        $diagnosis['orphaned_child_documents'] = 'N/A';
    }
    
    // 5. État de la migration
    $diagnosis['migration_status'] = [];
    
    // Vérifier s'il manque des versions
    try {
        $missing = $conn->query("
            SELECT COUNT(*) as cnt FROM documents d
            WHERE parent_document_id IS NULL
            AND NOT EXISTS (SELECT 1 FROM doc_version dv WHERE dv.id_doc = d.id)
        ")->fetch(PDO::FETCH_ASSOC)['cnt'];
        $diagnosis['migration_status']['documents_without_version'] = (int)$missing;
    } catch (Exception $e) {
        $diagnosis['migration_status']['error'] = $e->getMessage();
    }
    
    echo json_encode([
        'status' => 'ok',
        'diagnosis' => $diagnosis
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>
