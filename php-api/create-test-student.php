<?php
/**
 * create-test-student.php
 * Crée un utilisateur étudiant de test avec email pour recevoir les notifications
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once 'config.php';
    require_once 'db.php';
    
    $conn = Database::getConnection();
    
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║        CRÉATION D'UTILISATEUR ÉTUDIANT DE TEST AVEC EMAIL           ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    
    // Créer un étudiant de test
    $testStudent = [
        'username' => 'etudiant_test',
        'email' => 'etudiant@test.fr',
        'role' => 'student',
        'password' => 'test123'
    ];
    
    echo "📝 Création de l'étudiant de test...\n";
    echo "   Nom: {$testStudent['username']}\n";
    echo "   Email: {$testStudent['email']}\n";
    echo "   Rôle: {$testStudent['role']}\n\n";
    
    $passwordHash = password_hash($testStudent['password'], PASSWORD_BCRYPT);
    
    try {
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password_hash, role, created_at) 
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $testStudent['username'],
            $testStudent['email'],
            $passwordHash,
            $testStudent['role']
        ]);
        
        $studentId = (int)$conn->lastInsertId();
        
        echo "✅ SUCCÈS! Étudiant créé avec ID: $studentId\n\n";
        
        // Vérifier qu'il a été créé
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "📌 Vérification:\n";
        echo "   ID: {$student['id']}\n";
        echo "   Nom: {$student['username']}\n";
        echo "   Email: {$student['email']}\n";
        echo "   Rôle: {$student['role']}\n\n";
        
        // Afficher tous les étudiants
        echo "📊 TOUS LES ÉTUDIANTS:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE role = 'student' ORDER BY id");
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($students as $s) {
            echo sprintf("ID: %d | Nom: %-20s | Email: %s\n", $s['id'], $s['username'], $s['email'] ?: '(VIDE)');
        }
        
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "⚠️  L'utilisateur existe déjà.\n";
            echo "   Cherchons son ID...\n\n";
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$testStudent['username']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                echo "   ID: {$existing['id']}\n";
                echo "   Nom: {$existing['username']}\n";
                echo "   Email: {$existing['email']}\n";
                echo "   Rôle: {$existing['role']}\n";
            }
        } else {
            throw $e;
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

?>
