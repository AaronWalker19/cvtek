<?php
/**
 * check-student-emails.php
 * Vérifie les emails des étudiants en base
 */

require_once __DIR__ . '/db.php';

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║              VÉRIFICATION DES EMAILS ÉTUDIANTS                     ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    
    $conn = Database::getConnection();
    
    echo "👥 TOUS LES UTILISATEURS AVEC RÔLE 'student':\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE role = 'student' ORDER BY id");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $students = [];
    foreach ($result as $row) {
        $students[] = $row;
        echo sprintf(
            "ID: %d | Nom: %-20s | Email: %s | Rôle: %s\n",
            $row['id'],
            $row['username'],
            $row['email'] ?: '(VIDE)',
            $row['role']
        );
    }
    
    echo "\n📊 RÉSUMÉ:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $withEmail = array_filter($students, fn($s) => !empty($s['email']));
    $withoutEmail = array_filter($students, fn($s) => empty($s['email']));
    
    echo sprintf("Total étudiants: %d\n", count($students));
    echo sprintf("Avec email: %d ✅\n", count($withEmail));
    echo sprintf("Sans email: %d ❌\n", count($withoutEmail));
    
    echo "\n📌 ÉTUDIANT ID 25 (celui qui devrait recevoir l'email du commentaire):\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = 25");
    $stmt->execute();
    $student25 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student25) {
        echo sprintf("ID: %d\n", $student25['id']);
        echo sprintf("Nom: %s\n", $student25['username']);
        echo sprintf("Email: %s\n", $student25['email'] ?: '(VIDE - ⚠️  PAS D\'EMAIL!)');
        echo sprintf("Rôle: %s\n", $student25['role']);
    } else {
        echo "❌ Étudiant ID 25 non trouvé!\n";
    }
    
    echo "\n📄 DOCUMENTS PROPRIÉTÉ DE L'UTILISATEUR 25:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT id, user_id, nom_fichier, titre FROM documents WHERE user_id = 25 ORDER BY id");
    $stmt->execute();
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($docs)) {
        echo "❌ Aucun document trouvé pour l'utilisateur 25!\n";
    } else {
        foreach ($docs as $doc) {
            echo sprintf("  ID: %d | Propriétaire: %d | Titre: %s\n", $doc['id'], $doc['user_id'], $doc['titre'] ?: $doc['nom_fichier']);
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

?>
