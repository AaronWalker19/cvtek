<?php
/**
 * index.php - Point d'entrée UNIQUE pour l'API REST
 * 
 * Architecture: Router → Controller → Repository → Database
 * 
 * URLs:
 * POST   /api/auth/login
 * POST   /api/auth/register
 * GET    /api/auth/user
 * POST   /api/auth/logout
 * GET    /api/documents
 * GET    /api/documents?user_id=X
 * GET    /api/documents/{id}
 * POST   /api/documents
 * PUT    /api/documents/{id}
 * DELETE /api/documents/{id}
 * GET    /api/comments?doc_version_id=X
 * GET    /api/comments?user_id=X
 * GET    /api/comments/{id}
 * POST   /api/comments (body: {id_docversion, text})
 * PUT    /api/comments/{id} (body: {text})
 * DELETE /api/comments/{id}
 */

// ===== CONFIGURATION & CHARGEMENTS =====

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Class/HttpRequest.php';
require_once __DIR__ . '/Controller/Controller.php';
require_once __DIR__ . '/Repository/Repository.php';
require_once __DIR__ . '/Controller/AuthController.php';
require_once __DIR__ . '/Controller/DocumentController.php';
require_once __DIR__ . '/Controller/UploadController.php';
require_once __DIR__ . '/Controller/SystemController.php';
require_once __DIR__ . '/Controller/CommentController.php';
require_once __DIR__ . '/Controller/AbonnementController.php';
require_once __DIR__ . '/Repository/AuthRepository.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';
require_once __DIR__ . '/Repository/UploadRepository.php';
require_once __DIR__ . '/Repository/CommentRepository.php';
require_once __DIR__ . '/Repository/AbonnementRepository.php';

// ===== HEADERS =====

header('Content-Type: application/json; charset=utf-8');
setCorsHeaders();
handleCorsPreFlight();

// ===== ERROR HANDLERS - Capture TOUTES les erreurs PHP =====

// Gestionnaire d'erreurs
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[PHP ERROR] $errno - $errstr in $errfile:$errline");
    
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Pour les erreurs critiques, retourner du JSON
    if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_COMPILE_ERROR) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur PHP critique',
            'details' => "$errstr in $errfile:$errline"
        ]);
        exit;
    }
    
    return true;
});

// Gestionnaire d'erreur fatale (shutdown)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        error_log("[PHP FATAL] " . $error['type'] . " - " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur PHP fatale',
            'details' => $error['message'] . " in " . $error['file'] . ":" . $error['line']
        ]);
    }
});

// ===== ROUTER =====

try {
    $request = new HttpRequest();
    $resource = $request->getResource();

    error_log("📍 API Request: " . $_SERVER['REQUEST_METHOD'] . " " . $resource . " " . $request->getId());

    // Mapper les ressources aux contrôleurs
    $router = [
        'auth' => new AuthController(),
        'documents' => new DocumentController(),
        'upload' => new UploadController(),
        'system' => new SystemController(),
        'comments' => new CommentController(),
        'abonnement' => new AbonnementController(),
    ];

    // Vérifier si le contrôleur existe
    if (!isset($router[$resource])) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Ressource non trouvée',
            'available' => array_keys($router),
        ]);
        exit;
    }

    // Invoquer le contrôleur
    $controller = $router[$resource];
    $json = $controller->jsonResponse($request);

    if ($json) {
        // Réponse réussie
        http_response_code(200);
        echo $json;
    } else {
        // Erreur lors du traitement
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors du traitement de la requête',
        ]);
    }

} catch (Exception $e) {
    error_log("❌ API Exception: " . $e->getMessage());
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage(),
    ]);
}

exit;
