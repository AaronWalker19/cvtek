<?php
/**
 * Debug endpoint: vérifier l'existence d'une version et du document associé
 * Accès: GET /api/debug-version?id=6
 */

require_once __DIR__ . '/db.php';

$versionId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$versionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Version ID manquant']);
    exit;
}

try {
    $db = Database::getConnection();
    
    // 1. Chercher la version
    $stmt = $db->prepare("SELECT id, id_doc, version, url_fichier FROM doc_version WHERE id = ?");
    $stmt->execute([$versionId]);
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'version_id' => $versionId,
        'version_exists' => !!$version,
        'version_data' => $version
    ];
    
    if ($version) {
        // 2. Chercher le document
        $stmt = $db->prepare("SELECT id, user_id, nom_fichier, titre FROM documents WHERE id = ?");
        $stmt->execute([$version['id_doc']]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response['document_exists'] = !!$doc;
        $response['document_data'] = $doc;
        
        if ($doc) {
            // 3. Chercher le propriétaire
            $stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $stmt->execute([$doc['user_id']]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['owner_data'] = $owner;
        }
        
        // 4. Chercher les commentaires
        $stmt = $db->prepare("SELECT id, id_user, text FROM commentaire WHERE id_docversion = ?");
        $stmt->execute([$versionId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['comments_count'] = count($comments);
        $response['comments'] = $comments;
    }
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
