<?php
/**
 * helpers.php - Fonctions utilitaires pour l'API PHP
 * 
 * CORS, réponses JSON, validation, sécurité
 */

// ===============================================
// CORS & Headers de sécurité
// ===============================================

function setCorsHeaders(): void {
    // Origines autorisées
    $allowed_origins = [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'https://mmi.unilim.fr',
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Vérifier si l'origine est autorisée
    $is_allowed = in_array($origin, $allowed_origins) || 
                  preg_match('/^https:\/\/.*\.mmi\.unilim\.fr$/', $origin);
    
    if ($is_allowed) {
        header("Access-Control-Allow-Origin: {$origin}");
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 3600');
}

/**
 * Traite les requêtes OPTIONS (CORS preflight)
 */
function handleCorsPreFlight(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// ===============================================
// Réponses JSON
// ===============================================

/**
 * Envoie une réponse JSON de succès
 */
function jsonSuccess(array $data = [], int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => true,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    exit;
}

/**
 * Envoie une réponse JSON d'erreur
 */
function jsonError(string $message, int $status = 400, array $details = []): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => false,
        'error' => $message,
    ];
    
    if (!empty($details)) {
        $response['details'] = $details;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    exit;
}

// ===============================================
// Validation des inputs
// ===============================================

/**
 * Valide un email
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide un nom d'utilisateur
 */
function isValidUsername(string $username): bool {
    // 3-100 caractères, alphanumérique + underscore
    return preg_match('/^[a-zA-Z0-9_]{3,100}$/', $username) === 1;
}

/**
 * Valide un mot de passe
 */
function isValidPassword(string $password): bool {
    // Minimum 8 caractères
    return strlen($password) >= 8;
}

/**
 * Nettoie une chaîne de caractères
 */
function sanitizeString(string $input): string {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Nettoie un nom de fichier
 */
function sanitizeFileName(string $filename): string {
    // Supprimer les caractères spéciaux, garder seulement alphanumérique, - et .
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    // Éviter les chemins relatifs
    $filename = str_replace(['/', '\\', '..'], '', $filename);
    return $filename;
}

// ===============================================
// Authentification
// ===============================================

/**
 * Récupère le token Bearer depuis les headers
 */
function getBearerToken(): ?string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Récupère l'utilisateur depuis la session
 */
function getSessionUser(): ?array {
    if (!isset($_SESSION['user'])) {
        return null;
    }
    
    return $_SESSION['user'];
}

/**
 * Définit l'utilisateur en session
 */
function setSessionUser(array $user): void {
    session_start();
    $_SESSION['user'] = $user;
}

/**
 * Déconnecte l'utilisateur
 */
function logoutUser(): void {
    session_start();
    unset($_SESSION['user']);
    session_destroy();
}

/**
 * Vérifie que l'utilisateur est authentifié
 */
function requireAuth(): array {
    $user = getSessionUser();
    
    if (!$user) {
        jsonError('Authentification requise', 401);
    }
    
    return $user;
}

/**
 * Vérifie que l'utilisateur est admin
 */
function requireAdmin(): array {
    $user = requireAuth();
    
    if ($user['role'] !== 'admin') {
        jsonError('Accès refusé - Admin requis', 403);
    }
    
    return $user;
}

/**
 * Vérifie que l'utilisateur est professeur ou admin
 */
function requireProfessor(): array {
    $user = requireAuth();
    
    if (!in_array($user['role'], ['admin', 'professor'])) {
        jsonError('Accès refusé - Professeur ou Admin requis', 403);
    }
    
    return $user;
}

// ===============================================
// Gestion des requêtes
// ===============================================

/**
 * Récupère les données JSON du corps de la requête
 */
function getJsonBody(): array {
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        return [];
    }
    
    $data = json_decode($input, true);
    
    if (!is_array($data)) {
        jsonError('Corps JSON invalide', 400);
    }
    
    return $data;
}

/**
 * Récupère un paramètre GET avec validation optionnelle
 */
function getParam(string $key, string $default = '', ?callable $validator = null): string {
    $value = $_GET[$key] ?? $default;
    
    if ($validator && !$validator($value)) {
        jsonError("Paramètre invalide: {$key}", 400);
    }
    
    return sanitizeString($value);
}

// ===============================================
// Hash de mots de passe (bcrypt)
// ===============================================

/**
 * Hache un mot de passe avec bcrypt
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Vérifie un mot de passe contre un hash bcrypt
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// ===============================================
// Logging
// ===============================================

/**
 * Enregistre une action
 */
function logAction(string $action, array $context = []): void {
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] {$action}";
    
    if (!empty($context)) {
        $message .= " | " . json_encode($context);
    }
    
    error_log($message);
}

// ===============================================
// Initialisation
// ===============================================

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le fuseau horaire
date_default_timezone_set('UTC');

// Activer le display des erreurs (développement)
if (getenv('APP_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

?>
