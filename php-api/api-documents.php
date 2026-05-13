<?php
/**
 * api-documents.php - Endpoint direct pour les documents
 * 
 * Accès direct: https://mmi.unilim.fr/~valin6/cvtek/api/api-documents.php
 * Sans besoin de URL rewriting
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
setCorsHeaders();
handleCorsPreFlight();

try {
    // Récupérer l'action
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    // Récupérer l'utilisateur (optionnel en dev - sans authentification requise)
    $user = [
        'userId' => (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0),
        'username' => 'guest',
        'role' => 'guest'
    ];
    
    switch ($action) {
        case 'all':
            // GET /api/api-documents.php?action=all
            handleGetAllDocuments($user);
            break;
            
        case 'get':
            // GET /api/api-documents.php?action=get&user_id=123
            handleGetDocuments($user);
            break;
            
        case 'create':
            // POST /api/api-documents.php?action=create
            handleCreateDocument($user);
            break;
            
        case 'update':
            // PUT /api/api-documents.php?action=update&id=123
            handleUpdateDocument($user);
            break;
            
        case 'delete':
            // DELETE /api/api-documents.php?action=delete&id=123
            handleDeleteDocument($user);
            break;
            
        default:
            jsonSuccess([
                'message' => 'Bienvenue sur l\'API Documents CVTEK',
                'actions' => [
                    'all' => 'GET /api-documents.php?action=all (tous les documents)',
                    'get' => 'GET /api-documents.php?action=get&user_id=X (documents d\'un utilisateur)',
                    'create' => 'POST /api-documents.php?action=create (créer un document)',
                    'update' => 'PUT /api-documents.php?action=update&id=X (modifier un document)',
                    'delete' => 'DELETE /api-documents.php?action=delete&id=X (supprimer un document)',
                ]
            ]);
    }
    
} catch (Exception $e) {
    logAction("DOCUMENTS API ERROR", ['error' => $e->getMessage()]);
    jsonError($e->getMessage(), 500);
}

// ===============================================
// Fonctions des endpoints
// ===============================================

function handleGetAllDocuments(array $user): void {
    // ✅ DÉVELOPPEMENT: sans authentification
    logAction("GET ALL DOCUMENTS", ['requester' => $user['userId'] ?? 'anonymous']);
    
    $documents = Database::fetchAll(
        "SELECT d.id, d.user_id, d.nom_fichier, d.titre, d.type_fichier, d.url_fichier, d.description, d.version, d.created_at,
                u.username, u.email
         FROM documents d
         LEFT JOIN users u ON d.user_id = u.id
         ORDER BY d.created_at DESC"
    );
    
    foreach ($documents as &$doc) {
        $doc['id'] = (int)$doc['id'];
        $doc['user_id'] = (int)$doc['user_id'];
        $doc['version'] = (int)$doc['version'];
    }
    
    jsonSuccess([
        'count' => count($documents),
        'documents' => $documents,
    ]);
}

function handleGetDocuments(array $user): void {
    $user_id = (int)($_GET['user_id'] ?? getParam('user_id', (string)($user['userId'] ?? 0)));
    
    if (!$user_id) {
        jsonError('user_id requis', 400);
    }
    
    logAction("GET DOCUMENTS", ['userId' => $user_id, 'requester' => $user['userId'] ?? 'anonymous']);
    
    $documents = Database::fetchAll(
        "SELECT * FROM documents WHERE user_id = ? ORDER BY created_at DESC",
        [$user_id]
    );
    
    foreach ($documents as &$doc) {
        $doc['id'] = (int)$doc['id'];
        $doc['user_id'] = (int)$doc['user_id'];
        $doc['version'] = (int)$doc['version'];
    }
    
    jsonSuccess([
        'count' => count($documents),
        'documents' => $documents,
    ]);
}

function handleCreateDocument(array $user): void {
    $data = getJsonBody();
    
    $required = ['nom_fichier', 'type_fichier', 'url_fichier'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonError("Champ requis: {$field}", 400);
        }
    }
    
    $user_id = (int)($user['userId'] ?? 0);
    $nom_fichier = sanitizeString($data['nom_fichier']);
    $titre = sanitizeString($data['titre'] ?? '');
    $type_fichier = sanitizeString($data['type_fichier']);
    $url_fichier = sanitizeString($data['url_fichier']);
    $description = sanitizeString($data['description'] ?? '');
    
    $query = "INSERT INTO documents (user_id, nom_fichier, titre, type_fichier, url_fichier, description, version, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
    
    Database::execute($query, [$user_id, $nom_fichier, $titre, $type_fichier, $url_fichier, $description]);
    
    jsonSuccess(['message' => 'Document créé avec succès'], 201);
}

function handleUpdateDocument(array $user): void {
    $id = (int)($_GET['id'] ?? 0);
    $data = getJsonBody();
    
    if (!$id) {
        jsonError('id requis', 400);
    }
    
    $titre = sanitizeString($data['titre'] ?? '');
    $description = sanitizeString($data['description'] ?? '');
    
    $query = "UPDATE documents SET titre = ?, description = ?, updated_at = NOW() WHERE id = ?";
    Database::execute($query, [$titre, $description, $id]);
    
    jsonSuccess(['message' => 'Document modifié avec succès']);
}

function handleDeleteDocument(array $user): void {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonError('id requis', 400);
    }
    
    $query = "DELETE FROM documents WHERE id = ?";
    Database::execute($query, [$id]);
    
    jsonSuccess(['message' => 'Document supprimé avec succès']);
}

?>
