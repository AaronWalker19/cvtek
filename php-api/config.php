<?php
/**
 * config.php - Configuration de l'API
 * 
 * Charger les variables d'environnement et définir les constantes
 */

// Charger les variables d'environnement depuis .env
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '#') === 0) continue; // Ignorer les commentaires
        if (strpos($line, '=') === false) continue;
        
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Nettoyer les valeurs entre guillemets
        $value = trim($value, '\'"');
        
        putenv("{$key}={$value}");
    }
}

// ===============================================
// Configuration
// ===============================================

// Environnement
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true');

// Base de données
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_NAME', getenv('DB_NAME') ?: 'cvtek');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

// CORS
define('CORS_ORIGIN', getenv('CORS_ORIGIN') ?: 'https://mmi.unilim.fr');

// API
define('API_BASE_URL', getenv('API_BASE_URL') ?: '/~valin6/cvtek/api');

// Sessions
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 86400));
define('SESSION_NAME', getenv('SESSION_NAME') ?: 'CVTEK_SESSION');

// Upload
define('MAX_UPLOAD_SIZE', (int)(getenv('MAX_UPLOAD_SIZE') ?: 52428800));
define('UPLOAD_DIR', getenv('UPLOAD_DIR') ?: __DIR__ . '/../uploads');
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'txt', 'mp4', 'mov', 'avi', 'jpg', 'jpeg', 'png', 'gif']);

// JWT (optionnel)
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change-me-in-production');
define('JWT_EXPIRATION', (int)(getenv('JWT_EXPIRATION') ?: 604800));

// Paths
define('PROJECT_ROOT', dirname(__DIR__));
define('UPLOADS_PATH', PROJECT_ROOT . '/uploads');
define('LOGS_PATH', PROJECT_ROOT . '/logs');

// Vérifier les répertoires nécessaires
if (!is_dir(UPLOADS_PATH)) {
    @mkdir(UPLOADS_PATH, 0755, true);
}

if (!is_dir(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0755, true);
}

// Logging
ini_set('error_log', LOGS_PATH . '/php-errors.log');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
}

?>
