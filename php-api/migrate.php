<?php
// ============================================
// MIGRATION - Ajouter colonne parcour
// ============================================

header('Content-Type: application/json');

try {
    require_once 'config.php';
    require_once 'db.php';
    
    $conn = Database::getConnection();
    
    // Vérifier si la colonne parcour existe déjà
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'parcour'");
    if ($check && $check->rowCount() > 0) {
        echo json_encode([
            'status' => 'ok',
            'message' => 'Colonne parcour existe déjà',
        ]);
        exit;
    }
    
    // Ajouter la colonne parcour
    $conn->exec("ALTER TABLE users ADD COLUMN parcour VARCHAR(255) COMMENT 'Parcours/cursus de l\'étudiant' AFTER role");
    
    // Rendre password_hash nullable
    $conn->exec("ALTER TABLE users MODIFY password_hash VARCHAR(255) COMMENT 'Hash bcrypt du mot de passe (NULL si authentification externe)'");
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Migration réussie',
        'changes' => [
            'added_parcour_column',
            'made_password_nullable',
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
    ]);
}
?>
