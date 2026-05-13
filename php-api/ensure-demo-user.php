<?php
/**
 * ensure-demo-user.php - S'assurer que l'utilisateur démo existe
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Chercher l'utilisateur mael
    $mael = Database::fetchOne(
        "SELECT id, username, email FROM users WHERE username = ?",
        ['mael']
    );
    
    if ($mael) {
        // L'utilisateur existe
        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur mael trouvé',
            'user' => [
                'id' => (int)$mael['id'],
                'username' => $mael['username'],
                'email' => $mael['email']
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        // Créer l'utilisateur mael
        $passwordHash = password_hash('mael123', PASSWORD_BCRYPT);
        
        Database::execute(
            "INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())",
            ['mael', 'mael@mael.fr', $passwordHash, 'student']
        );
        
        // Récupérer l'ID nouvellement créé
        $newUser = Database::fetchOne(
            "SELECT id, username, email FROM users WHERE username = ?",
            ['mael']
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur mael créé',
            'user' => [
                'id' => (int)$newUser['id'],
                'username' => $newUser['username'],
                'email' => $newUser['email']
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

?>
