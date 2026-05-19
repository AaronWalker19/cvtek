<?php
// ============================================
// SEED - Crée les 3 utilisateurs démo
// ============================================

header('Content-Type: application/json');

try {
    require_once 'config.php';
    require_once 'db.php';
    
    $conn = Database::getConnection();
    
    // Vider les utilisateurs existants
    $conn->exec("DELETE FROM users");
    
    // Créer les 3 utilisateurs démo
    $users = [
        [
            'username' => 'mael',
            'email' => 'mael@mael.fr',
            'role' => 'student',
            'parcour' => 'Informatique',
        ],
        [
            'username' => 'professor',
            'email' => 'professor@cvtek.fr',
            'role' => 'professor',
            'parcour' => NULL,
        ],
        [
            'username' => 'admin',
            'email' => 'admin@cvtek.fr',
            'role' => 'admin',
            'parcour' => NULL,
        ],
    ];
    
    $stmt = $conn->prepare(
        "INSERT INTO users (username, email, role, parcour, password_hash, created_at) VALUES (?, ?, ?, ?, NULL, NOW())"
    );
    
    $created = [];
    foreach ($users as $user) {
        $stmt->execute([
            $user['username'],
            $user['email'],
            $user['role'],
            $user['parcour'],
        ]);
        $created[] = [
            'id' => $conn->lastInsertId(),
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'parcour' => $user['parcour'],
        ];
    }
    
    echo json_encode([
        'status' => 'ok',
        'message' => '3 utilisateurs créés',
        'users' => $created,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
    ]);
}
?>
