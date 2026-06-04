<?php
include 'php-api/db.php';

try {
    $conn = Database::getConnection();
    
    // Vérifier les utilisateurs existants
    $users = $conn->query('SELECT id, username, role, email FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    echo "=== Utilisateurs actuels ===\n";
    foreach($users as $u) {
        printf("ID: %d, Username: %-15s, Role: %-10s, Email: %s\n", 
            $u['id'], $u['username'], $u['role'], $u['email']);
    }
    
    echo "\n=== Création/vérification des utilisateurs démo ===\n";
    
    // Créer/vérifier chaque utilisateur
    $demoUsers = [
        ['id' => 16, 'username' => 'mael', 'email' => 'mael@mael.fr', 'role' => 'student', 'password' => 'mael123'],
        ['id' => 17, 'username' => 'professor', 'email' => 'professor@cvtek.fr', 'role' => 'professor', 'password' => 'professor123'],
        ['id' => 18, 'username' => 'admin', 'email' => 'admin@cvtek.fr', 'role' => 'admin', 'password' => 'admin123'],
        ['id' => 19, 'username' => 'eleve2', 'email' => 'eleve2@gmail.com', 'role' => 'student', 'password' => 'eleve2123'],
        ['id' => 20, 'username' => 'prof2', 'email' => 'prof2@gmail.com', 'role' => 'professor', 'password' => 'prof2123']
    ];
    
    foreach ($demoUsers as $user) {
        $existing = $conn->query(
            "SELECT id FROM users WHERE username = '" . $conn->quote($user['username']) . "'"
        )->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            echo "✅ {$user['username']} existe déjà (ID: {$existing['id']})\n";
        } else {
            $passwordHash = password_hash($user['password'], PASSWORD_BCRYPT);
            try {
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
                echo "✅ {$user['username']} créé (ID: {$user['id']})\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false && strpos($e->getMessage(), 'id') !== false) {
                    // L'ID existe, créer avec auto-increment
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
                    echo "⚠️  {$user['username']} créé avec auto-ID {$newId} (ID {$user['id']} était occupé)\n";
                } else {
                    echo "❌ Erreur création {$user['username']}: {$e->getMessage()}\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
