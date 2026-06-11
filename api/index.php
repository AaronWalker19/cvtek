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
require_once __DIR__ . '/Controller/AdminController.php';
require_once __DIR__ . '/Controller/ExportController.php';
require_once __DIR__ . '/Repository/AuthRepository.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';
require_once __DIR__ . '/Repository/UploadRepository.php';
require_once __DIR__ . '/Repository/CommentRepository.php';
require_once __DIR__ . '/Repository/AbonnementRepository.php';
require_once __DIR__ . '/Repository/UserRepository.php';

// ===== CONFIGURATION DE SESSION =====
// Doit être fait AVANT tout session_start()

// Déterminer si on est en développement ou production
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8000', 'localhost:3000', '127.0.0.1:8000', '127.0.0.1:3000']);
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Configuration adaptative pour dev/prod
$cookieConfig = [
    'lifetime' => 0,           // Session cookie (expire à la fermeture du navigateur)
    'path' => $isLocalhost ? '/' : '/cvtek',  // En dev: /, en prod: /cvtek
    'domain' => $isLocalhost ? '' : '.unilim.fr',  // En dev: vide (cookies domaine simple), en prod: .unilim.fr
    'secure' => !$isLocalhost,  // En dev: HTTP OK, en prod: HTTPS
    'httponly' => true,        // JavaScript ne peut pas accéder au cookie
    'samesite' => $isLocalhost ? 'Lax' : 'None'  // En dev: Lax OK, en prod: None (OIDC cross-site)
];

error_log("🔧 Configuration SESSION pour: " . $host);
error_log("🔧 Mode: " . ($isLocalhost ? 'DÉVELOPPEMENT' : 'PRODUCTION'));
error_log("🔧 Cookie params: " . json_encode($cookieConfig));

session_set_cookie_params($cookieConfig);

// ===== HEADERS =====

header('Content-Type: application/json; charset=utf-8');
setCorsHeaders();
handleCorsPreFlight();

// ===== DÉMARRAGE DE SESSION =====
// Démarrer la session PHP UNE SEULE FOIS, avant tout traitement
ensureSessionStarted();

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
        'admin' => new AdminController(),
        'export' => new ExportController(),
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
