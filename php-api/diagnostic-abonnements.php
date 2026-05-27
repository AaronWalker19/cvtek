<?php
/**
 * diagnostic-abonnements.php
 * Diagnostic complet pour les problèmes d'abonnements et d'emails
 */

require_once __DIR__ . '/db.php';

$db = Database::getConnection();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC - ABONNEMENTS ET EMAILS                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════
// 1. VÉRIFIER LES UTILISATEURS
// ═══════════════════════════════════════════════════════════════════
echo "📋 UTILISATEURS\n";
echo "─────────────────────────────────────────────────────────────\n";

$users = $db->query("SELECT id, username, email, role FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($users) . " utilisateurs\n\n";
foreach ($users as $u) {
    echo "  ID " . str_pad($u['id'], 3) . " | {$u['username']:<20} | {$u['email']:<30} | {$u['role']}\n";
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 2. VÉRIFIER LES ABONNEMENTS
// ═══════════════════════════════════════════════════════════════════
echo "📌 ABONNEMENTS\n";
echo "─────────────────────────────────────────────────────────────\n";

$abonnements = $db->query(
    "SELECT a.id, a.id_prof, a.id_user, a.created_at, 
            prof.username AS prof_name, prof.email AS prof_email,
            etud.username AS etud_name, etud.email AS etud_email
     FROM abonnement a
     LEFT JOIN users prof ON a.id_prof = prof.id
     LEFT JOIN users etud ON a.id_user = etud.id
     ORDER BY a.id"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($abonnements)) {
    echo "❌ AUCUN ABONNEMENT TROUVÉ!\n";
} else {
    echo "✅ Total: " . count($abonnements) . " abonnement(s)\n\n";
    foreach ($abonnements as $a) {
        echo "  ID {$a['id']} | Prof: {$a['prof_name']} ({$a['prof_email']}) → Étudiant: {$a['etud_name']} ({$a['etud_email']})\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 3. VÉRIFIER LES DOCUMENTS
// ═══════════════════════════════════════════════════════════════════
echo "📄 DOCUMENTS\n";
echo "─────────────────────────────────────────────────────────────\n";

$documents = $db->query(
    "SELECT d.id, d.user_id, d.nom_fichier, d.titre, u.username, u.email
     FROM documents d
     LEFT JOIN users u ON d.user_id = u.id
     ORDER BY d.id DESC LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($documents)) {
    echo "❌ AUCUN DOCUMENT!\n";
} else {
    echo "✅ Derniers documents:\n\n";
    foreach ($documents as $d) {
        echo "  ID {$d['id']} | user_id={$d['user_id']} | {$d['nom_fichier']} | Propriétaire: {$d['username']} ({$d['email']})\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 4. VÉRIFIER LES VERSIONS
// ═══════════════════════════════════════════════════════════════════
echo "📌 VERSIONS RÉCENTES\n";
echo "─────────────────────────────────────────────────────────────\n";

$versions = $db->query(
    "SELECT dv.id, dv.id_doc, dv.version, dv.url_fichier, d.nom_fichier
     FROM doc_version dv
     LEFT JOIN documents d ON dv.id_doc = d.id
     ORDER BY dv.id DESC LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($versions)) {
    echo "❌ AUCUNE VERSION!\n";
} else {
    echo "✅ Dernières versions:\n\n";
    foreach ($versions as $v) {
        echo "  Version ID {$v['id']} | Doc ID {$v['id_doc']} v{$v['version']} | {$v['url_fichier']}\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 5. VÉRIFIER LES COMMENTAIRES
// ═══════════════════════════════════════════════════════════════════
echo "💬 COMMENTAIRES RÉCENTS\n";
echo "─────────────────────────────────────────────────────────────\n";

$comments = $db->query(
    "SELECT c.id, c.id_user, c.id_docversion, c.text, u.username, u.email,
            dv.id_doc, dv.version
     FROM commentaire c
     LEFT JOIN users u ON c.id_user = u.id
     LEFT JOIN doc_version dv ON c.id_docversion = dv.id
     ORDER BY c.id DESC LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($comments)) {
    echo "❌ AUCUN COMMENTAIRE!\n";
} else {
    echo "✅ Derniers commentaires:\n\n";
    foreach ($comments as $c) {
        echo "  Comment ID {$c['id']} | Auteur: {$c['username']} | DocVersion: {$c['id_docversion']} (Doc #{$c['id_doc']} v{$c['version']})\n";
        echo "    Texte: " . substr($c['text'], 0, 50) . "...\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 6. TESTER LA REQUÊTE getProfInfoByUser
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST: getProfInfoByUser(16) - Récupérer les profs abonnés à l'étudiant 16\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $db->prepare(
    "SELECT u.id, u.email, u.username
     FROM abonnement a
     LEFT JOIN users u ON a.id_prof = u.id
     WHERE a.id_user = ?"
);
$stmt->execute([16]);
$profs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($profs)) {
    echo "❌ AUCUN PROFESSEUR ABONNÉ À L'ÉTUDIANT 16\n";
} else {
    echo "✅ Professeurs abonnés:\n";
    foreach ($profs as $p) {
        echo "  - {$p['username']} ({$p['email']})\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 7. RÉSUMÉ DU PROBLÈME
// ═══════════════════════════════════════════════════════════════════
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ                                                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$userCount = count($users);
$abonnementCount = count($abonnements);
$docCount = count($documents);
$versionCount = count($versions);
$commentCount = count($comments);

echo "✅ Utilisateurs: $userCount\n";
echo ($abonnementCount > 0 ? "✅" : "❌") . " Abonnements: $abonnementCount\n";
echo ($docCount > 0 ? "✅" : "❌") . " Documents: $docCount\n";
echo ($versionCount > 0 ? "✅" : "❌") . " Versions: $versionCount\n";
echo ($commentCount > 0 ? "✅" : "❌") . " Commentaires: $commentCount\n\n";

if ($abonnementCount === 0) {
    echo "🔴 PROBLÈME: Aucun abonnement en base de données!\n";
    echo "   Raison possible: L'action \"suivre l'étudiant\" ne crée pas d'abonnement\n";
    echo "   Solution: Vérifier le endpoint de création d'abonnement\n";
} else {
    echo "🟢 Abonnements présents\n";
}

echo "\n";
?>
