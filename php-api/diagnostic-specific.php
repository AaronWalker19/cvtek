<?php
/**
 * diagnostic-specific.php
 * Debug les problèmes spécifiques mentionnés dans les logs
 */

require_once __DIR__ . '/db.php';

$db = Database::getConnection();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC SPÉCIFIQUE                                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════
// PROBLÈME 1: Version ID 22 - Chercher le document
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST 1: Version ID 22 (Commentaire qui échoue)\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $db->prepare(
    "SELECT dv.id, dv.id_doc, dv.version, d.id, d.user_id, d.nom_fichier, u.email, u.username
     FROM doc_version dv
     LEFT JOIN documents d ON dv.id_doc = d.id
     LEFT JOIN users u ON d.user_id = u.id
     WHERE dv.id = ?"
);
$stmt->execute([22]);
$version = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$version) {
    echo "❌ Version ID 22 N'EXISTE PAS!\n";
} else {
    echo "✅ Version trouvée:\n";
    echo "  Version ID: " . $version['id'] . "\n";
    echo "  Doc ID: " . $version['id_doc'] . "\n";
    echo "  Version num: " . $version['version'] . "\n";
    echo "  Document: " . $version['nom_fichier'] . "\n";
    echo "  user_id: " . $version['user_id'] . "\n";
    echo "  Étudiant: " . $version['username'] . " ({$version['email']})\n";
    
    // Vérifier l'étudiant
    if (!$version['user_id']) {
        echo "  ⚠️ user_id est NULL - document mal créé!\n";
    }
    if (!$version['email']) {
        echo "  ⚠️ email est NULL - étudiant sans email!\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// PROBLÈME 2: User ID 16 - Vérifier si abonnements existent
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST 2: Abonnements de l'étudiant ID 16\n";
echo "─────────────────────────────────────────────────────────────\n";

// Vérifier que l'utilisateur 16 existe
$stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
$stmt->execute([16]);
$user16 = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user16) {
    echo "❌ Utilisateur ID 16 N'EXISTE PAS!\n";
} else {
    echo "✅ Utilisateur trouvé:\n";
    echo "  ID: " . $user16['id'] . "\n";
    echo "  Username: " . $user16['username'] . "\n";
    echo "  Email: " . $user16['email'] . "\n";
}
echo "\n";

// Vérifier les abonnements
$stmt = $db->prepare(
    "SELECT a.id, a.id_prof, a.id_user, prof.username AS prof_name, prof.email AS prof_email
     FROM abonnement a
     LEFT JOIN users prof ON a.id_prof = prof.id
     WHERE a.id_user = ?"
);
$stmt->execute([16]);
$abonnements16 = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($abonnements16)) {
    echo "❌ AUCUN ABONNEMENT POUR ÉTUDIANT 16!\n";
} else {
    echo "✅ Abonnements trouvés: " . count($abonnements16) . "\n";
    foreach ($abonnements16 as $ab) {
        echo "  - Prof ID {$ab['id_prof']}: {$ab['prof_name']} ({$ab['prof_email']})\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// TEST 3: Professeur ID 17 (celui qui poste le commentaire)
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST 3: Professeur ID 17 (qui poste le commentaire)\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->execute([17]);
$prof17 = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prof17) {
    echo "❌ Professeur ID 17 N'EXISTE PAS!\n";
} else {
    echo "✅ Professeur trouvé:\n";
    echo "  ID: " . $prof17['id'] . "\n";
    echo "  Username: " . $prof17['username'] . "\n";
    echo "  Email: " . $prof17['email'] . "\n";
    echo "  Role: " . $prof17['role'] . "\n";
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// TEST 4: Vérifier si prof 17 est abonné à étudiant 16
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST 4: Prof 17 suivi-il l'étudiant 16?\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $db->prepare(
    "SELECT id FROM abonnement WHERE id_prof = ? AND id_user = ?"
);
$stmt->execute([17, 16]);
$isAbonne = $stmt->fetch(PDO::FETCH_ASSOC);

if ($isAbonne) {
    echo "✅ OUI, l'abonnement existe (ID: " . $isAbonne['id'] . ")\n";
} else {
    echo "❌ NON, l'abonnement N'EXISTE PAS\n";
    echo "   → Cela explique pourquoi aucun email n'est envoyé!\n";
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// TEST 5: Tous les documents de l'étudiant 16
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST 5: Tous les documents de l'étudiant 16\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $db->prepare(
    "SELECT d.id, d.user_id, d.nom_fichier, d.titre
     FROM documents d
     WHERE d.user_id = ?"
);
$stmt->execute([16]);
$docs16 = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($docs16)) {
    echo "❌ Aucun document pour étudiant 16\n";
} else {
    echo "✅ Documents trouvés: " . count($docs16) . "\n";
    foreach ($docs16 as $d) {
        echo "  Doc ID {$d['id']}: {$d['nom_fichier']}\n";
        
        // Vérifier les versions
        $stmt = $db->prepare(
            "SELECT id, version FROM doc_version WHERE id_doc = ?"
        );
        $stmt->execute([$d['id']]);
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($versions)) {
            echo "    ⚠️ Pas de versions!\n";
        } else {
            foreach ($versions as $v) {
                echo "    Version ID {$v['id']}: v{$v['version']}\n";
            }
        }
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// RÉSUMÉ DES PROBLÈMES
// ═══════════════════════════════════════════════════════════════════
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ                                                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$problemes = [];

if (!$version) {
    $problemes[] = "❌ Version ID 22 n'existe pas (commentaire échoue)";
} elseif (!$version['user_id']) {
    $problemes[] = "❌ Version ID 22 pointe vers un document sans user_id";
} elseif (!$version['email']) {
    $problemes[] = "❌ Version ID 22 pointe vers étudiant sans email ({$version['username']})";
}

if (empty($abonnements16)) {
    $problemes[] = "❌ Aucun abonnement pour étudiant 16 (emails de doc ne sont pas envoyés)";
}

if (!$isAbonne) {
    $problemes[] = "❌ Prof 17 ne suit pas étudiant 16 (raison: aucun abonnement créé)";
}

if (empty($problemes)) {
    echo "✅ Pas de problèmes détectés!\n";
} else {
    echo "Problèmes trouvés:\n";
    foreach ($problemes as $p) {
        echo $p . "\n";
    }
}

echo "\n";
?>
