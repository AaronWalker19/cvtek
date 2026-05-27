<?php
/**
 * diagnose-all-data.php
 * Diagnostic complet: tous les utilisateurs, documents, versions, commentaires
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    
    $conn = Database::getConnection();
    
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║            DIAGNOSTIC COMPLET - BASE DE DONNÉES                    ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    
    // 1. Tous les utilisateurs
    echo "👥 UTILISATEURS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ AUCUN UTILISATEUR!\n\n";
    } else {
        foreach ($users as $u) {
            $emailStatus = empty($u['email']) ? '❌ VIDE' : '✅ ' . $u['email'];
            echo sprintf("ID: %3d | %10s | %-20s | Email: %s\n",
                $u['id'], $u['role'], $u['username'], $emailStatus);
        }
        echo "\n";
    }
    
    // 2. Tous les documents
    echo "📄 DOCUMENTS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare(
        "SELECT d.id, d.user_id, d.nom_fichier, d.titre, 
                COALESCE(u.username, 'NON-TROUVÉ') as owner_name, u.email
         FROM documents d
         LEFT JOIN users u ON d.user_id = u.id
         ORDER BY d.id"
    );
    $stmt->execute();
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($docs)) {
        echo "❌ AUCUN DOCUMENT!\n\n";
    } else {
        foreach ($docs as $d) {
            $emailStatus = empty($d['email']) ? '❌ PAS EMAIL' : '✅ HAS EMAIL';
            $ownerStatus = $d['owner_name'] === 'NON-TROUVÉ' ? '❌' : '✅';
            echo sprintf("%s Doc ID: %2d | Propriétaire: ID %2d (%s) | Email: %s | Titre: %s\n",
                $ownerStatus, $d['id'], $d['user_id'] ?? 'NULL', $d['owner_name'],
                $emailStatus, $d['titre'] ?: $d['nom_fichier']);
        }
        echo "\n";
    }
    
    // 3. Toutes les versions
    echo "📌 VERSIONS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare(
        "SELECT dv.id, dv.id_doc, dv.version, dv.url_fichier FROM doc_version ORDER BY id DESC LIMIT 10"
    );
    $stmt->execute();
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($versions)) {
        echo "❌ AUCUNE VERSION!\n\n";
    } else {
        foreach ($versions as $v) {
            echo sprintf("Version ID: %2d | Doc ID: %2d | v%.1f | %s\n",
                $v['id'], $v['id_doc'], $v['version'], $v['url_fichier']);
        }
        echo "\n";
    }
    
    // 4. Tous les commentaires
    echo "💬 COMMENTAIRES:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare(
        "SELECT c.id, c.id_user, c.id_docversion, c.date,
                COALESCE(u.username, 'NON-TROUVÉ') as author_name, u.email, c.text
         FROM commentaire c
         LEFT JOIN users u ON c.id_user = u.id
         ORDER BY c.id DESC LIMIT 10"
    );
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($comments)) {
        echo "❌ AUCUN COMMENTAIRE!\n\n";
    } else {
        foreach ($comments as $c) {
            $authorStatus = $c['author_name'] === 'NON-TROUVÉ' ? '❌' : '✅';
            echo sprintf("%s Comm ID: %2d | Auteur: ID %2d (%s) | Version: %2d | Texte: %s...\n",
                $authorStatus, $c['id'], $c['id_user'], $c['author_name'],
                $c['id_docversion'], substr($c['text'], 0, 30));
        }
        echo "\n";
    }
    
    // 5. Diagnostic spécifique du commentaire 12
    echo "🔍 DIAGNOSTIC - COMMENTAIRE ID 12:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare(
        "SELECT c.id, c.id_user, c.id_docversion, c.text, c.date,
                u_author.username as author_name, u_author.email as author_email, u_author.role as author_role,
                dv.id_doc, d.user_id as student_id, d.nom_fichier,
                u_student.username as student_name, u_student.email as student_email, u_student.role as student_role
         FROM commentaire c
         LEFT JOIN users u_author ON c.id_user = u_author.id
         LEFT JOIN doc_version dv ON c.id_docversion = dv.id
         LEFT JOIN documents d ON dv.id_doc = d.id
         LEFT JOIN users u_student ON d.user_id = u_student.id
         WHERE c.id = 12"
    );
    $stmt->execute();
    $diag = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$diag) {
        echo "❌ COMMENTAIRE ID 12 NON TROUVÉ!\n";
    } else {
        echo "✅ Commentaire ID 12 trouvé:\n\n";
        
        echo "📝 COMMENTAIRE:\n";
        echo "   ID: {$diag['id']}\n";
        echo "   Texte: " . substr($diag['text'], 0, 60) . "...\n";
        echo "   Date: {$diag['date']}\n";
        echo "   Version ID: {$diag['id_docversion']}\n";
        echo "   Document ID: {$diag['id_doc']}\n\n";
        
        echo "👨‍🏫 AUTEUR (Professeur):\n";
        echo "   ID: {$diag['id_user']}\n";
        echo "   Nom: " . ($diag['author_name'] ?: 'NON-TROUVÉ') . "\n";
        echo "   Email: " . ($diag['author_email'] ?: '❌ VIDE') . "\n";
        echo "   Rôle: " . ($diag['author_role'] ?: 'NON-TROUVÉ') . "\n\n";
        
        echo "🎓 DESTINATAIRE (Étudiant propriétaire du doc):\n";
        echo "   ID: " . ($diag['student_id'] ?: '❌ NULL') . "\n";
        echo "   Nom: " . ($diag['student_name'] ?: '❌ NON-TROUVÉ') . "\n";
        echo "   Email: " . ($diag['student_email'] ?: '❌ VIDE/NON-TROUVÉ') . "\n";
        echo "   Rôle: " . ($diag['student_role'] ?: '❌ NON-TROUVÉ') . "\n";
        echo "   Document: " . ($diag['nom_fichier'] ?: '?') . "\n\n";
        
        // Diagnostic
        echo "⚠️  PROBLÈME IDENTIFIÉ:\n";
        
        if (!$diag['student_id']) {
            echo "   ❌ Le document n'a pas de propriétaire (user_id = NULL)!\n";
            echo "   → L'email ne peut pas être envoyé\n";
        } elseif (!$diag['student_name']) {
            echo "   ❌ L'étudiant ID {$diag['student_id']} n'existe pas en base!\n";
            echo "   → L'email ne peut pas être envoyé\n";
        } elseif (!$diag['student_email']) {
            echo "   ❌ L'étudiant ({$diag['student_name']}) n'a pas d'email!\n";
            echo "   → L'email ne peut pas être envoyé\n";
        } else {
            echo "   ✅ L'étudiant existe et a un email\n";
            echo "   → L'email DEVRAIT avoir été envoyé à: {$diag['student_email']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

?>
