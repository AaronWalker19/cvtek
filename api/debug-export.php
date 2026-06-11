<?php
/**
 * debug-export.php - Debug l'export des documents
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';

$cnx = Database::getConnection();

echo "========== DEBUG EXPORT ==========\n\n";

// 1. Lister tous les users
echo "👥 USERS:\n";
$userStmt = $cnx->query("SELECT id, username, role, email FROM users ORDER BY id");
foreach ($userStmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
    echo "  [{$user['id']}] {$user['username']} ({$user['role']}) - {$user['email']}\n";
}

echo "\n📄 DOCUMENTS et VERSIONS:\n";
// 2. Lister tous les documents avec leurs versions
$docStmt = $cnx->query("
    SELECT 
        d.id, 
        d.user_id, 
        d.nom_fichier,
        d.titre,
        u.username,
        u.parcour,
        dv.id as version_id,
        dv.version,
        dv.url_fichier
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN doc_version dv ON dv.id_doc = d.id
    ORDER BY d.id, dv.created_at DESC
");

$docs = $docStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($docs as $doc) {
    echo "\n  📋 Doc #{$doc['id']}: {$doc['nom_fichier']}\n";
    echo "     Utilisateur: {$doc['username']} (id {$doc['user_id']}, parcour: {$doc['parcour']})\n";
    if ($doc['version_id']) {
        echo "     Version {$doc['version']}: {$doc['url_fichier']}\n";
        $filePath = __DIR__ . '/../uploads/' . basename($doc['url_fichier']);
        echo "       Fichier existe? " . (file_exists($filePath) ? "✅ OUI" : "❌ NON") . " ($filePath)\n";
    }
}

echo "\n📊 STATISTIQUES:\n";
echo "  Total documents: " . $cnx->query("SELECT COUNT(*) as cnt FROM documents")->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";
echo "  Total versions: " . $cnx->query("SELECT COUNT(*) as cnt FROM doc_version")->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";
echo "  Total étudiants (role=student): " . $cnx->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'")->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";

echo "\n🔍 TEST: Récupérer documents pour étudiant ID 1:\n";
$testStmt = $cnx->prepare("
    SELECT 
        d.id, 
        d.nom_fichier,
        u.username,
        u.parcour,
        (SELECT url_fichier FROM doc_version WHERE id_doc = d.id ORDER BY created_at DESC LIMIT 1) as url_fichier
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    WHERE d.user_id = ?
    ORDER BY d.created_at DESC
");
$testStmt->execute([1]);
$testDocs = $testStmt->fetchAll(PDO::FETCH_ASSOC);
echo "  Résultat: " . count($testDocs) . " documents\n";
foreach ($testDocs as $doc) {
    echo "    - {$doc['nom_fichier']} (version: {$doc['url_fichier']})\n";
}
?>
