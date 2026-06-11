<?php
/**
 * debug-export-test.php - Teste l'export pour un étudiant
 * Accès: https://mmi.unilim.fr/cvtek/api/system?action=debug-export
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';

// ID de test (change selon tes besoins)
$testStudentId = 1; // à adapter

$cnx = Database::getConnection();
$userRepo = new UserRepository();
$docRepo = new DocumentRepository();

echo "<h2>DEBUG EXPORT POUR ÉTUDIANT #$testStudentId</h2>\n";

// 1. Vérifier l'étudiant
echo "<h3>1. ÉTUDIANT</h3>\n";
$student = $userRepo->findById($testStudentId);
if ($student) {
    echo "✅ Trouvé: {$student['username']}\n";
    echo "<pre>" . json_encode($student, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
} else {
    echo "❌ Étudiant non trouvé\n";
    exit;
}

// 2. Récupérer les documents
echo "<h3>2. DOCUMENTS</h3>\n";
$docs = $docRepo->findByUserId($testStudentId);
echo "Trouvé: " . count($docs) . " documents\n";
foreach ($docs as $doc) {
    echo "<pre>" . json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
}

if (empty($docs)) {
    echo "❌ AUCUN DOCUMENT TROUVÉ!\n";
    exit;
}

// 3. Pour chaque document, récupérer les versions
echo "<h3>3. VERSIONS</h3>\n";
$uploadDir = __DIR__ . '/../uploads';
echo "Upload dir: $uploadDir\n";

foreach ($docs as $doc) {
    echo "\n<strong>Document #{$doc['id']}: {$doc['nom_fichier']}</strong>\n";
    
    $versions = $docRepo->findVersions($doc['id']);
    echo "  Versions trouvées: " . count($versions) . "\n";
    
    if (empty($versions)) {
        echo "  ❌ AUCUNE VERSION!\n";
        continue;
    }
    
    $latestVersion = $versions[0];
    echo "  Dernière version: {$latestVersion['version']}\n";
    echo "  URL fichier: {$latestVersion['url_fichier']}\n";
    
    $fileName = basename($latestVersion['url_fichier']);
    echo "  Basename: $fileName\n";
    
    // Chercher le fichier
    $path1 = $uploadDir . '/' . $fileName;
    $path2 = $uploadDir . '/' . str_replace('/cvtek/uploads/', '', $latestVersion['url_fichier']);
    
    echo "  Essai 1 ($path1): " . (file_exists($path1) ? "✅ EXISTE" : "❌ N'existe pas") . "\n";
    echo "  Essai 2 ($path2): " . (file_exists($path2) ? "✅ EXISTE" : "❌ N'existe pas") . "\n";
    
    // Lister tous les fichiers du dossier uploads
    if (is_dir($uploadDir) && count($docs) === 1) {
        echo "\n  Fichiers dans uploads:\n";
        $files = scandir($uploadDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "    - $file\n";
            }
        }
    }
}
?>
