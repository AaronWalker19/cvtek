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
    // ========== ÉTAPE 0: S'assurer que les tables existent ==========
    $conn = Database::getConnection();
    
    // Vérifier la table users
    $checkUsers = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$checkUsers || $checkUsers->rowCount() === 0) {
        // Créer la table users
        $conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(255) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) DEFAULT NULL,
                role VARCHAR(50) DEFAULT 'student' CHECK(role IN ('admin', 'professor', 'student')),
                parcour VARCHAR(255) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_username (username),
                INDEX idx_email (email),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    // Vérifier la table documents
    $checkDocs = $conn->query("SHOW TABLES LIKE 'documents'");
    if (!$checkDocs || $checkDocs->rowCount() === 0) {
        // Créer la table documents
        $conn->exec("
            CREATE TABLE IF NOT EXISTS documents (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                nom_fichier VARCHAR(255) NOT NULL,
                titre TEXT DEFAULT NULL,
                type_fichier VARCHAR(50) NOT NULL,
                description TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                
                INDEX idx_user_id (user_id),
                INDEX idx_type_fichier (type_fichier),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    // ========== ÉTAPE 1: S'assurer que la table doc_version existe ==========
    $conn = Database::getConnection();
    
    $check = $conn->query("SHOW TABLES LIKE 'doc_version'");
    $tableExists = $check && $check->rowCount() > 0;
    
    if (!$tableExists) {
        // Créer la table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS doc_version (
                id INT PRIMARY KEY AUTO_INCREMENT,
                id_doc INT NOT NULL COMMENT 'Référence au document parent',
                version DECIMAL(3,1) NOT NULL COMMENT 'Numéro de version (1.0, 2.0, etc)',
                url_fichier VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                
                CONSTRAINT fk_doc_version FOREIGN KEY (id_doc) REFERENCES documents(id) ON DELETE CASCADE,
                
                INDEX idx_id_doc (id_doc),
                INDEX idx_version (version),
                INDEX idx_created_at (created_at),
                UNIQUE KEY uk_doc_version (id_doc, version)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    // ========== ÉTAPE 2: Vérifier que la table documents existe ==========
    $checkDocs = $conn->query("SHOW TABLES LIKE 'documents'");
    if ($checkDocs && $checkDocs->rowCount() > 0) {
        // Créer les versions par défaut pour les documents qui n'en ont pas
        $missingVersions = $conn->query("
            SELECT d.id, d.nom_fichier, d.created_at
            FROM documents d
            WHERE NOT EXISTS (
                SELECT 1 FROM doc_version dv WHERE dv.id_doc = d.id
            )
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($missingVersions as $doc) {
            // Créer une première version par défaut
            $stmt = $conn->prepare("
                INSERT INTO doc_version (id_doc, version, url_fichier, created_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $doc['id'],
                1.0,
                '/~valin6/cvtek/uploads/' . $doc['nom_fichier'],
                $doc['created_at']
            ]);
        }
    }
    
    // ========== ÉTAPE 3: Chercher l'utilisateur mael ==========
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
            "INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())",
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
    
    // Inclure le stack trace en développement
    $errorData = [
        'success' => false,
        'error' => $e->getMessage(),
    ];
    
    if (APP_DEBUG) {
        $errorData['trace'] = $e->getTraceAsString();
    }
    
    echo json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

?>
