<?php
/**
 * diagnose-document-25.php
 * Examine le document 25 et son propriétaire
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    
    $conn = Database::getConnection();
    
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║                DIAGNOSTIC - DOCUMENT ID 25                         ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    
    // 1. Récupérer le document 25
    echo "📄 DOCUMENT ID 25:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare(
        "SELECT d.id, d.user_id, d.nom_fichier, d.titre, d.type_fichier, 
                d.description, d.created_at, u.username, u.email, u.role
         FROM documents d
         LEFT JOIN users u ON d.user_id = u.id
         WHERE d.id = 25"
    );
    $stmt->execute();
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doc) {
        echo "❌ Document ID 25 NON TROUVÉ!\n\n";
    } else {
        echo sprintf("ID: %d\n", $doc['id']);
        echo sprintf("Propriétaire (user_id): %d\n", $doc['user_id']);
        echo sprintf("Propriétaire (nom): %s\n", $doc['username'] ?: '❌ NULL!');
        echo sprintf("Propriétaire (email): %s\n", $doc['email'] ?: '❌ VIDE!');
        echo sprintf("Propriétaire (rôle): %s\n", $doc['role'] ?: '❌ NULL!');
        echo sprintf("Titre: %s\n", $doc['titre'] ?: $doc['nom_fichier']);
        echo sprintf("Créé: %s\n\n", $doc['created_at']);
        
        // 2. Vérifier les versions
        echo "📌 VERSIONS DU DOCUMENT 25:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        $stmt = $conn->prepare(
            "SELECT id, id_doc, version, url_fichier, created_at FROM doc_version WHERE id_doc = 25 ORDER BY version DESC"
        );
        $stmt->execute();
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($versions)) {
            echo "❌ AUCUNE VERSION TROUVÉE!\n\n";
        } else {
            foreach ($versions as $v) {
                echo sprintf("Version ID: %d | Numéro: %.1f | URL: %s | Créé: %s\n",
                    $v['id'], $v['version'], $v['url_fichier'], $v['created_at']);
            }
            echo "\n";
        }
        
        // 3. Vérifier les commentaires du document
        echo "💬 COMMENTAIRES DU DOCUMENT 25:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        $stmt = $conn->prepare(
            "SELECT c.id, c.id_user, c.id_docversion, c.date, u.username, u.email, c.text
             FROM commentaire c
             LEFT JOIN users u ON c.id_user = u.id
             WHERE c.id_docversion IN (
                SELECT id FROM doc_version WHERE id_doc = 25
             )
             ORDER BY c.date DESC"
        );
        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($comments)) {
            echo "❌ AUCUN COMMENTAIRE TROUVÉ!\n\n";
        } else {
            foreach ($comments as $c) {
                echo sprintf("Commentaire ID: %d | Auteur: %s (ID %d, email: %s) | Texte: %s...\n",
                    $c['id'], $c['username'] ?: '?', $c['id_user'], $c['email'] ?: '(VIDE)',
                    substr($c['text'], 0, 40));
            }
            echo "\n";
        }
    }
    
    // RÉSUMÉ
    echo "🔍 RÉSUMÉ:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($doc) {
        $issues = [];
        
        if (!$doc['user_id']) {
            $issues[] = "❌ Document sans propriétaire (user_id = NULL)";
        } elseif (!$doc['username']) {
            $issues[] = "❌ Propriétaire n'existe pas en base (user_id = {$doc['user_id']})";
        } elseif (!$doc['email']) {
            $issues[] = "❌ Propriétaire a pas d'email ({$doc['username']})";
        } else {
            $issues[] = "✅ Propriétaire existe et a un email: {$doc['username']} ({$doc['email']})";
        }
        
        if (empty($versions)) {
            $issues[] = "❌ Document sans versions";
        } else {
            $issues[] = sprintf("✅ Document a %d version(s)", count($versions));
        }
        
        if (empty($comments)) {
            $issues[] = "ℹ️  Document sans commentaires (normal)";
        } else {
            $issues[] = sprintf("✅ Document a %d commentaire(s)", count($comments));
        }
        
        foreach ($issues as $issue) {
            echo "$issue\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

?>
