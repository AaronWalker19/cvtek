<?php
/**
 * init-db.php - Script d'initialisation de la base de données
 * 
 * À appeler une fois au démarrage pour s'assurer que tout est en place:
 * - Base de données existe
 * - Tables existent avec bonnes structures
 * - Utilisateurs de test existent
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Connexion sans sélectionner de BD d'abord
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $port = getenv('DB_PORT') ?: 3306;
    $dbname = getenv('DB_NAME') ?: 'cvtek';
    
    // Créer la connexion sans DB
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Créer la BD si elle n'existe pas
    $conn->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Sélectionner la BD
    $conn->exec("USE `{$dbname}`");
    
    // Créer les tables
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
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS doc_version (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_doc INT NOT NULL,
            version DECIMAL(3,1) NOT NULL,
            url_fichier VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_doc_version FOREIGN KEY (id_doc) REFERENCES documents(id) ON DELETE CASCADE,
            
            INDEX idx_id_doc (id_doc),
            INDEX idx_version (version),
            INDEX idx_created_at (created_at),
            UNIQUE KEY uk_doc_version (id_doc, version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS uploads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            unique_name VARCHAR(255) NOT NULL UNIQUE,
            file_size BIGINT,
            mime_type VARCHAR(100),
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_upload_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            
            INDEX idx_user_id (user_id),
            INDEX idx_uploaded_at (uploaded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS commentaire (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_user INT NOT NULL,
            id_docversion INT NOT NULL,
            text LONGTEXT NOT NULL,
            date DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_commentaire_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_commentaire_docversion FOREIGN KEY (id_docversion) REFERENCES doc_version(id) ON DELETE CASCADE,
            
            INDEX idx_id_user (id_user),
            INDEX idx_id_docversion (id_docversion),
            INDEX idx_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS abonnement (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_prof INT NOT NULL,
            id_user INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_abonnement_prof FOREIGN KEY (id_prof) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_abonnement_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
            
            INDEX idx_id_prof (id_prof),
            INDEX idx_id_user (id_user),
            INDEX idx_created_at (created_at),
            UNIQUE KEY uk_prof_user (id_prof, id_user)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Créer l'utilisateur démo mael s'il n'existe pas
    $check = $conn->query("SELECT id FROM users WHERE username = 'mael'");
    if ($check->rowCount() === 0) {
        $passwordHash = password_hash('mael123', PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['mael', 'mael@mael.fr', $passwordHash, 'student']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Base de données initialisée avec succès',
            'created_user' => 'mael',
            'database' => $dbname
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Base de données déjà initialisée',
            'database' => $dbname
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur base de données: ' . $e->getMessage(),
        'code' => $e->getCode(),
        'config' => [
            'host' => $host ?? 'unknown',
            'database' => $dbname ?? 'unknown',
            'user' => $user ?? 'unknown',
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

?>
