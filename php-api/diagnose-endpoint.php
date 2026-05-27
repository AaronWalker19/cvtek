<?php
/**
 * diagnose-endpoint.php
 * Point d'entrée de diagnostic accessible via HTTP
 * 
 * Accès: http://localhost:8000/cvtek/api/../diagnose-endpoint.php
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    
    $conn = Database::getConnection();
    
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║              DIAGNOSTIC - EMAILS & COMMENTAIRES                    ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    
    // 1. Tous les étudiants
    echo "👥 ÉTUDIANTS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE role = 'student' ORDER BY id");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        echo "❌ AUCUN ÉTUDIANT TROUVÉ!\n\n";
    } else {
        foreach ($students as $s) {
            $hasEmail = !empty($s['email']) ? '✅' : '❌';
            echo sprintf("%s ID: %3d | Nom: %-25s | Email: %s\n", 
                $hasEmail, $s['id'], $s['username'], $s['email'] ?: '(VIDE)');
        }
        echo "\n";
    }
    
    // 2. Tous les documents
    echo "📄 DOCUMENTS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare(
        "SELECT d.id, d.user_id, d.nom_fichier, d.titre, u.username FROM documents d 
         LEFT JOIN users u ON d.user_id = u.id 
         ORDER BY d.id"
    );
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($documents)) {
        echo "❌ AUCUN DOCUMENT TROUVÉ!\n\n";
    } else {
        foreach ($documents as $d) {
            echo sprintf("ID: %3d | Propriétaire: ID %3d (%s) | Titre: %s\n",
                $d['id'], $d['user_id'], $d['username'] ?: '?', 
                $d['titre'] ?: $d['nom_fichier']);
        }
        echo "\n";
    }
    
    // 3. Tous les commentaires
    echo "💬 COMMENTAIRES:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare(
        "SELECT c.id, c.id_user, c.id_docversion, c.text, u.username, u.email,
                dv.id_doc, d.nom_fichier
         FROM commentaire c
         LEFT JOIN users u ON c.id_user = u.id
         LEFT JOIN doc_version dv ON c.id_docversion = dv.id
         LEFT JOIN documents d ON dv.id_doc = d.id
         ORDER BY c.id DESC"
    );
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($comments)) {
        echo "❌ AUCUN COMMENTAIRE TROUVÉ!\n\n";
    } else {
        foreach ($comments as $c) {
            echo sprintf("ID: %2d | Auteur: %s (ID %d, email: %s) | Doc: %s (ID %d)\n",
                $c['id'], $c['username'] ?: '?', $c['id_user'], 
                $c['email'] ?: '(VIDE)', $c['nom_fichier'] ?: '?', $c['id_doc']);
            echo sprintf("      Texte: %s...\n", substr($c['text'], 0, 60));
        }
        echo "\n";
    }
    
    // 4. Diagnostique du commentaire ID 12
    echo "🔍 DIAGNOSTIC - COMMENTAIRE ID 12:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare(
        "SELECT c.id, c.id_user, c.id_docversion, c.text, u.username, u.email as prof_email,
                dv.id_doc, d.user_id as student_id, d.nom_fichier, s.username as student_name, s.email as student_email
         FROM commentaire c
         LEFT JOIN users u ON c.id_user = u.id
         LEFT JOIN doc_version dv ON c.id_docversion = dv.id
         LEFT JOIN documents d ON dv.id_doc = d.id
         LEFT JOIN users s ON d.user_id = s.id
         WHERE c.id = 12"
    );
    $stmt->execute();
    $diag = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$diag) {
        echo "❌ Commentaire ID 12 non trouvé!\n";
    } else {
        echo "✅ Commentaire ID 12 trouvé:\n";
        echo sprintf("   Auteur (Prof): %s (ID %d, email: %s)\n", 
            $diag['username'] ?: '?', $diag['id_user'], $diag['prof_email'] ?: '❌ VIDE');
        echo sprintf("   Destinataire (Étudiant): %s (ID %d, email: %s)\n",
            $diag['student_name'] ?: '?', $diag['student_id'], 
            $diag['student_email'] ?: '❌ VIDE');
        echo sprintf("   Document: %s (ID %d)\n", $diag['nom_fichier'] ?: '?', $diag['id_doc']);
        echo sprintf("   Texte: %s...\n", substr($diag['text'], 0, 60));
        
        if (!$diag['student_email']) {
            echo "\n   ⚠️  PROBLÈME: L'ÉTUDIANT N'A PAS D'EMAIL CONFIGURÉ!\n";
            echo "       Le commentaire a été créé, mais l'email ne peut pas être envoyé.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

?>
