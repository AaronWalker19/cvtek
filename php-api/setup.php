<?php
// ============================================
// SETUP - Crée utilisateurs de test avec password connu
// ============================================

header('Content-Type: application/json');

try {
    require_once 'config.php';
    require_once 'db.php';
    require_once 'helpers.php';
    
    $conn = Database::getConnection();
    
    // Password qu'on va utiliser pour les tests
    $testPassword = 'test123456';
    $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
    
    // Mettre à jour les utilisateurs de test avec ce password
    $users = [
        ['email' => 'admin@cvtek.fr', 'username' => 'admin', 'role' => 'admin'],
        ['email' => 'mael@mael.fr', 'username' => 'mael', 'role' => 'student'],
        ['email' => 'professor@cvtek.fr', 'username' => 'professor', 'role' => 'professor'],
    ];
    
    $result = [
        'status' => 'ok',
        'test_password' => $testPassword,
        'updated' => []
    ];
    
    foreach ($users as $user) {
        $stmt = $conn->prepare(
            "UPDATE users SET password = ? WHERE email = ?"
        );
        $stmt->execute([$hashedPassword, $user['email']]);
        
        if ($stmt->rowCount() > 0) {
            $result['updated'][] = [
                'email' => $user['email'],
                'username' => $user['username'],
                'role' => $user['role'],
                'password_updated' => true
            ];
        }
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
    ]);
}
?>
