<?php
/**
 * Script pour insérer des données de test avec commentaires
 * Utilisation: php insert-test-data.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';

echo "📊 Insertion de données de test...\n\n";

try {
    $db = Database::getConnection();
    $docs = new DocumentRepository();
    
    // 1. Vérifier les utilisateurs de test
    echo "👤 Utilisateurs:\n";
    $stmt = $db->query("SELECT id, username, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo "   ID {$user['id']}: {$user['username']} ({$user['role']})\n";
    }
    
    // 2. Vérifier les documents
    echo "\n📄 Documents:\n";
    $stmt = $db->query("SELECT id, user_id, nom_fichier FROM documents");
    $docs_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($docs_data as $doc) {
        echo "   ID {$doc['id']}: {$doc['nom_fichier']} (user_id={$doc['user_id']})\n";
    }
    
    // 3. Vérifier les versions
    echo "\n📦 Versions de documents:\n";
    $stmt = $db->query("SELECT id, id_doc, version FROM doc_version ORDER BY id");
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($versions as $v) {
        echo "   Version ID {$v['id']}: Doc {$v['id_doc']}, v{$v['version']}\n";
    }
    
    // 4. Vérifier les commentaires
    echo "\n💬 Commentaires:\n";
    $stmt = $db->query("SELECT id, id_user, id_docversion, text FROM commentaire");
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($comments)) {
        echo "   Aucun commentaire trouvé\n";
    } else {
        foreach ($comments as $c) {
            echo "   ID {$c['id']}: Utilisateur {$c['id_user']} sur Version {$c['id_docversion']}\n";
            echo "      \"{$c['text']}\"\n";
        }
    }
    
    // 5. Insérer des données de test si vide
    if (empty($versions) || count($versions) < 5) {
        echo "\n⚙️  Ajout de versions de test...\n";
        
        // Créer plus de versions
        $docs_to_use = !empty($docs_data) ? $docs_data : [];
        
        if (!empty($docs_to_use)) {
            $doc_id = $docs_to_use[0]['id'];
            
            // Ajouter des versions
            for ($v = 1; $v <= 5; $v++) {
                $version = number_format($v, 1);
                $url = "/cvtek/uploads/test-doc-v{$v}.pdf";
                
                try {
                    $stmt = $db->prepare("INSERT IGNORE INTO doc_version (id_doc, version, url_fichier) VALUES (?, ?, ?)");
                    $stmt->execute([$doc_id, $version, $url]);
                    echo "   ✅ Version {$version} créée\n";
                } catch (Exception $e) {
                    echo "   ⚠️  Version {$version} déjà existante\n";
                }
            }
            
            // Ajouter des commentaires
            echo "\n⚙️  Ajout de commentaires de test...\n";
            
            // Récupérer les IDs des versions
            $stmt = $db->query("SELECT id FROM doc_version ORDER BY id DESC LIMIT 5");
            $version_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($version_ids)) {
                $prof_id = !empty($users) && count($users) > 1 ? $users[1]['id'] : 2;
                
                $texts = [
                    'Très bon travail! Continue ainsi.',
                    'Il faudrait améliorer cette partie.',
                    'Excellente présentation.',
                    'À revoir selon les commentaires précédents.',
                    'Parfait! Prêt pour la soumission.'
                ];
                
                foreach ($version_ids as $idx => $version_id) {
                    try {
                        $text = $texts[$idx] ?? 'Commentaire de test';
                        $stmt = $db->prepare("INSERT IGNORE INTO commentaire (id_user, id_docversion, text) VALUES (?, ?, ?)");
                        $stmt->execute([$prof_id, $version_id, $text]);
                        echo "   ✅ Commentaire sur version {$version_id} créé\n";
                    } catch (Exception $e) {
                        echo "   ⚠️  Commentaire version {$version_id} non créé\n";
                    }
                }
            }
        }
    }
    
    echo "\n✅ Données de test prêtes!\n";
    echo "\nPour tester:\n";
    echo "1. Se connecter en tant qu'admin ou professeur\n";
    echo "2. Cliquer sur un professeur pour voir ses commentaires\n";
    echo "3. Cliquer sur un commentaire pour naviguer vers le fichier\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: {$e->getMessage()}\n";
    exit(1);
}
?>
