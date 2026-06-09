<?php
/**
 * Debug endpoint: vérifier un document et ses versions
 * Accès: GET /api/debug-document?id=1
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';

$docId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$docId) {
    http_response_code(400);
    echo json_encode(['error' => 'Document ID manquant']);
    exit;
}

try {
    $db = Database::getConnection();
    $docRepo = new DocumentRepository();
    
    echo json_encode([
        'debug_info' => '=== Vérification du document ' . $docId . ' ===',
        'step_1' => 'Récupération directe du document',
        'document_direct' => $docRepo->findById($docId),
        'step_2' => 'Récupération des versions',
        'versions' => $docRepo->findVersions($docId),
        'step_3' => 'Récupération du document avec versions (findByIdWithVersions)',
        'document_with_versions' => $docRepo->findByIdWithVersions($docId),
        'step_4' => 'Requête SQL brute',
        'raw_query' => $db->query("SELECT id, id_doc, version, url_fichier FROM doc_version WHERE id_doc = $docId ORDER BY version DESC")->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
