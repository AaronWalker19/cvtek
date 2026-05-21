<?php
/**
 * init-demo-users.php - Initialiser les utilisateurs démo avec les bons IDs
 * 
 * Cet endpoint s'assure que les trois utilisateurs démo existent:
 * - mael (ID 16, student)
 * - professor (ID 17, professor)
 * - admin (ID 18, admin)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = Database::getConnection();
    $results = [];

    // Définir les utilisateurs démo avec leurs IDs
    $demoUsers = [
        [
            'id' => 16,
            'username' => 'mael',
            'email' => 'mael@mael.fr',
            'role' => 'student',
            'password' => 'mael123'
        ],
        [
            'id' => 17,
            'username' => 'professor',
            'email' => 'professor@cvtek.fr',
            'role' => 'professor',
            'password' => 'professor123'
        ],
        [
            'id' => 18,
            'username' => 'admin',
            'email' => 'admin@cvtek.fr',
            'role' => 'admin',
            'password' => 'admin123'
        ]
    ];

    // Créer les utilisateurs
    foreach ($demoUsers as $user) {
        $existing = Database::fetchOne(
            "SELECT id FROM users WHERE username = ?",
            [$user['username']]
        );

        if ($existing) {
            $results[] = [
                'username' => $user['username'],
                'status' => 'exists',
                'id' => (int)$existing['id'],
                'email' => $user['email']
            ];
            error_log("✅ Utilisateur {$user['username']} existe avec ID {$existing['id']}");
        } else {
            try {
                $passwordHash = password_hash($user['password'], PASSWORD_BCRYPT);
                
                // Essayer d'insérer avec l'ID spécifique
                $stmt = $conn->prepare(
                    "INSERT INTO users (id, username, email, password_hash, role, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $passwordHash,
                    $user['role']
                ]);

                $results[] = [
                    'username' => $user['username'],
                    'status' => 'created',
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                error_log("✅ Utilisateur {$user['username']} créé avec ID {$user['id']}");
            } catch (PDOException $e) {
                // Si l'ID existe mais le username est différent, créer avec auto-increment
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $passwordHash = password_hash($user['password'], PASSWORD_BCRYPT);
                    $stmt = $conn->prepare(
                        "INSERT INTO users (username, email, password_hash, role, created_at) 
                         VALUES (?, ?, ?, ?, NOW())"
                    );
                    $stmt->execute([
                        $user['username'],
                        $user['email'],
                        $passwordHash,
                        $user['role']
                    ]);

                    $newId = $conn->lastInsertId();
                    $results[] = [
                        'username' => $user['username'],
                        'status' => 'created_with_auto_id',
                        'id' => (int)$newId,
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'note' => "ID spécifique {$user['id']} n'a pas pu être utilisé"
                    ];
                    error_log("⚠️ Utilisateur {$user['username']} créé avec ID auto-généré: {$newId}");
                } else {
                    throw $e;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Utilisateurs démo vérifiés/créés',
        'users' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    error_log("❌ Erreur init-demo-users: " . $e->getMessage());
}
?>
