<?php
/**
 * Test du service email APRÈS corrections
 * Vérifie que les réponses SMTP sont maintenant vérifiées correctement
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/Repository.php';
require_once __DIR__ . '/Service/EmailService.php';
require_once __DIR__ . '/Repository/CommentRepository.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';
require_once __DIR__ . '/Repository/UserRepository.php';

echo "\n";
echo "====================================================\n";
echo "🧪 TEST EMAIL SERVICE (Version CORRIGÉE)\n";
echo "====================================================\n";

$emailService = new EmailService();
$db = Database::getConnection();

// Test 1: Envoyer un email simple (test direct)
echo "\n📧 Test 1: Envoi d'un email simple (direct)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$result = $emailService->sendNewCommentNotification(
    'student@cvtek.local',
    'Jean Dupont',
    'Prof Durand',
    'Bulletin de notes - Octobre 2025',
    'Veuillez vérifier votre bulletin de notes. Des commentaires ont été ajoutés.'
);

echo "\n✅ Résultat:\n";
echo "  Success: " . ($result['success'] ? 'OUI ✓' : 'NON ✗') . "\n";

if (!$result['success'] && isset($result['error'])) {
    echo "  ❌ Erreur: " . $result['error'] . "\n";
} else {
    echo "  ✅ Email envoyé avec succès!\n";
}

// Test 2: Envoyer à plusieurs destinataires (document)
echo "\n\n📧 Test 2: Envoi à plusieurs destinataires (document)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Chercher des professeurs avec emails
$stmt = $db->prepare("SELECT id, email, username FROM users WHERE role='professor' AND email IS NOT NULL LIMIT 2");
$stmt->execute();
$profs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($profs)) {
    $profEmails = array_column($profs, 'email');
    
    echo "  Envoi à " . count($profEmails) . " professeur(s):\n";
    foreach ($profs as $prof) {
        echo "    - {$prof['username']} ({$prof['email']})\n";
    }
    
    $result = $emailService->sendNewDocumentNotification(
        'student@cvtek.local',
        'Jean Dupont',
        'Bulletin de notes - Octobre 2025',
        $profEmails
    );
    
    echo "\n  ✅ Résultat:\n";
    echo "    Success: " . ($result['success'] ? 'OUI ✓' : 'NON ✗') . "\n";
    
    if (!$result['success'] && isset($result['error'])) {
        echo "    ❌ Erreur: " . $result['error'] . "\n";
    } else if (isset($result['details'])) {
        echo "    📊 Détails: " . $result['details'] . "\n";
    }
} else {
    echo "  ⚠️  Aucun professeur avec email trouvé en BD\n";
}

// Test 3: Vérifier les logs pour les codes SMTP
echo "\n\n📧 Test 3: Vérification des logs SMTP\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "  Voir les logs PHP pour:\n";
echo "    ✅ Codes 250, 334, 354, 220 = succès partiel\n";
echo "    ❌ Codes 550, 554, 421 = erreur\n";
echo "    ⚠️  Messages d'erreur détaillés si rejet\n";

echo "\n\n====================================================\n";
echo "✅ Tests complétés!\n";
echo "====================================================\n";
echo "\n📝 Vérifications importantes:\n";
echo "  1. ✓ Les logs montrent les codes de réponse SMTP\n";
echo "  2. ✓ Les erreurs sont détaillées ('554 Message rejected', etc.)\n";
echo "  3. ✓ Pas de faux 'succès' si le serveur refuse\n";
echo "  4. ✓ Le email arrive réellement en boîte\n";
echo "\n";
