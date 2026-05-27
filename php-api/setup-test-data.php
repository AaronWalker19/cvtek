<?php
/**
 * setup-test-data.php
 * Crée/corrige les données de test nécessaires pour que le système fonctionne
 */

require_once __DIR__ . '/db.php';

$db = Database::getConnection();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  SETUP DONNÉES DE TEST                                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════
// ÉTAPE 1: Vérifier/créer les utilisateurs de test
// ═══════════════════════════════════════════════════════════════════
echo "📝 ÉTAPE 1: Vérifier/créer les utilisateurs\n";
echo "─────────────────────────────────────────────────────────────\n";

// Étudiant 16
$stmt = $db->prepare("SELECT * FROM users WHERE id = 16");
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    echo "❌ Étudiant 16 n'existe pas, création...\n";
    $db->prepare(
        "INSERT IGNORE INTO users (id, username, email, password_hash, role) VALUES (16, 'student_test', 'student@cvtek.local', ?, 'student')"
    )->execute(['$2y$10$SZPj1V8H5UdHOlj7L6YSz.ZYfVrL7uUzMvB6AK3C9MYrLs5Yw7pJm']);
    echo "✅ Étudiant créé: student_test (student@cvtek.local)\n";
} elseif (!$student['email'] || $student['email'] === 'null') {
    echo "⚠️ Étudiant 16 existe mais sans email, mise à jour...\n";
    $db->prepare("UPDATE users SET email = ? WHERE id = 16")->execute(['student@cvtek.local']);
    echo "✅ Email défini: student@cvtek.local\n";
} else {
    echo "✅ Étudiant 16 existe: {$student['username']} ({$student['email']})\n";
}

// Professeur 17
$stmt = $db->prepare("SELECT * FROM users WHERE id = 17");
$stmt->execute();
$prof = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prof) {
    echo "❌ Professeur 17 n'existe pas, création...\n";
    $db->prepare(
        "INSERT IGNORE INTO users (id, username, email, password_hash, role) VALUES (17, 'professor_test', 'professor@cvtek.local', ?, 'professor')"
    )->execute(['$2y$10$8/LD0r2PKz3gJXQJ2I5Efe0XhKc9QVzJKW/MYZjGqHVFAV0A6VlIi']);
    echo "✅ Professeur créé: professor_test (professor@cvtek.local)\n";
} elseif (!$prof['email'] || $prof['email'] === 'null') {
    echo "⚠️ Professeur 17 existe mais sans email, mise à jour...\n";
    $db->prepare("UPDATE users SET email = ? WHERE id = 17")->execute(['professor@cvtek.local']);
    echo "✅ Email défini: professor@cvtek.local\n";
} else {
    echo "✅ Professeur 17 existe: {$prof['username']} ({$prof['email']})\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// ÉTAPE 2: Vérifier/créer les documents et versions
// ═══════════════════════════════════════════════════════════════════
echo "📄 ÉTAPE 2: Vérifier/créer les documents de test\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $db->prepare("SELECT * FROM documents WHERE id = 36");
$stmt->execute();
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) {
    echo "❌ Document 36 n'existe pas, création...\n";
    $db->prepare(
        "INSERT INTO documents (id, user_id, nom_fichier, titre, type_fichier, description) VALUES (36, 16, 'test-document.pdf', 'Test Document', 'pdf', 'Document de test')"
    )->execute();
    echo "✅ Document créé: test-document.pdf\n";
    
    // Créer la première version
    $db->prepare(
        "INSERT INTO doc_version (id_doc, version, url_fichier) VALUES (36, 1.0, '/cvtek/uploads/test-document.pdf')"
    )->execute();
    echo "✅ Version 1.0 créée\n";
} else {
    echo "✅ Document 36 existe: {$doc['nom_fichier']}\n";
    
    // Vérifier la version
    $stmt = $db->prepare("SELECT * FROM doc_version WHERE id_doc = 36");
    $stmt->execute();
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$version) {
        echo "❌ Version 1.0 n'existe pas, création...\n";
        $db->prepare(
            "INSERT INTO doc_version (id_doc, version, url_fichier) VALUES (36, 1.0, '/cvtek/uploads/test-document.pdf')"
        )->execute();
        echo "✅ Version 1.0 créée\n";
    } else {
        echo "✅ Versions existent\n";
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// ÉTAPE 3: Tester la création d'abonnement
// ═══════════════════════════════════════════════════════════════════
echo "📌 ÉTAPE 3: Créer/vérifier abonnement\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $db->prepare(
    "SELECT * FROM abonnement WHERE id_prof = 17 AND id_user = 16"
);
$stmt->execute();
$abonnement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$abonnement) {
    echo "❌ Abonnement (Prof 17 → Étudiant 16) n'existe pas, création...\n";
    $result = $db->prepare(
        "INSERT INTO abonnement (id_prof, id_user, created_at) VALUES (17, 16, NOW())"
    )->execute();
    if ($result) {
        echo "✅ Abonnement créé\n";
    } else {
        echo "❌ Erreur lors de la création\n";
    }
} else {
    echo "✅ Abonnement existe (depuis {$abonnement['created_at']})\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// ÉTAPE 4: Vérifier les requêtes
// ═══════════════════════════════════════════════════════════════════
echo "🔍 ÉTAPE 4: Vérifier les requêtes PHP\n";
echo "─────────────────────────────────────────────────────────────\n";

// Test: getProfInfoByUser(16)
$profs = $db->prepare(
    "SELECT u.id, u.email, u.username
     FROM abonnement a
     INNER JOIN users u ON a.id_prof = u.id
     WHERE a.id_user = ? AND u.email IS NOT NULL"
)->fetchAll(PDO::FETCH_ASSOC, [16]);

echo "getProfInfoByUser(16):\n";
if (empty($profs)) {
    echo "  ❌ Retourne 0 professeurs\n";
} else {
    echo "  ✅ Retourne " . count($profs) . " professeur(s):\n";
    foreach ($profs as $p) {
        echo "     - {$p['username']} ({$p['email']})\n";
    }
}

// Test: findDocByVersionId(X)
$versions = $db->prepare("SELECT id FROM doc_version WHERE id_doc = 36")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($versions)) {
    $versionId = $versions[0]['id'];
    $doc_result = $db->prepare(
        "SELECT d.id, d.user_id, d.nom_fichier, d.titre
         FROM documents d
         INNER JOIN doc_version dv ON dv.id_doc = d.id
         WHERE dv.id = ?"
    )->fetch(PDO::FETCH_ASSOC, [$versionId]);
    
    echo "findDocByVersionId($versionId):\n";
    if (!$doc_result) {
        echo "  ❌ Document non trouvé\n";
    } else {
        echo "  ✅ Document trouvé:\n";
        echo "     ID: {$doc_result['id']}\n";
        echo "     user_id: {$doc_result['user_id']}\n";
        echo "     nom_fichier: {$doc_result['nom_fichier']}\n";
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// RÉSUMÉ
// ═══════════════════════════════════════════════════════════════════
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ                                                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";
echo "✅ Les données de test ont été vérifiées/créées\n";
echo "   Vous pouvez maintenant tester le système:\n";
echo "   1. Accédez à /professor/file/36 avec professeur 17\n";
echo "   2. Cliquez sur 'Suivre l'étudiant' (devrait déjà être coché)\n";
echo "   3. Ajoutez un commentaire\n";
echo "   4. Un email devrait être envoyé à student@cvtek.local\n";
echo "\n";
?>
