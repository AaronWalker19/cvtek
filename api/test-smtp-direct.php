<?php
/**
 * TEST SMTP DIRECT - Pour Documents
 * Teste l'envoi réel via SMTP Gmail
 */

require_once __DIR__ . '/Service/EmailService.php';

echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                   TEST SMTP DIRECT POUR DOCUMENTS                               ║\n";
echo "║                    (Debug Email Notifications)                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

// Test avec email de test
$test_recipient = "mael.valin@unilim.fr";
$test_subject = "TEST CVTEK - Document Notification (" . date('H:i:s') . ")";
$test_body = "
<html>
<head><meta charset='UTF-8'></head>
<body>
<h2>Ceci est un test d'envoi d'email de document</h2>
<p>Si tu reçois cet email, c'est que SMTP fonctionne! 🎉</p>
<p>Date du test: " . date('Y-m-d H:i:s') . "</p>
</body>
</html>
";

error_log("════════════════════════════════════════════════════════════════");
error_log("TEST SMTP DIRECT - Document Notification");
error_log("════════════════════════════════════════════════════════════════");
error_log("");

// Créer le service email en mode debug
$emailService = new EmailService(true); // Debug activé

error_log("📧 Test: Envoi à $test_recipient");
error_log("📝 Sujet: $test_subject");
error_log("");
error_log("Tentative d'envoi via SMTP...");
error_log("");

// Appeler directement sendViaSMTP() via Reflection pour tester
$reflectionMethod = new ReflectionMethod($emailService, 'sendViaSMTP');
$reflectionMethod->setAccessible(true);

$result = $reflectionMethod->invoke($emailService, $test_recipient, $test_subject, $test_body);

error_log("");
error_log("════════════════════════════════════════════════════════════════");
error_log("RÉSULTATS");
error_log("════════════════════════════════════════════════════════════════");

if ($result['success']) {
    error_log("✅ EMAIL ENVOYÉ AVEC SUCCÈS VIA SMTP!");
    error_log("");
    error_log("Vérification:");
    error_log("1. Vérifier le courrier du professeur");
    error_log("2. Si reçu → SMTP fonctionne, utiliser pour documents!");
    error_log("3. Si non reçu → Problème d'authentification Gmail");
} else {
    error_log("❌ ÉCHEC DE L'ENVOI VIA SMTP");
    error_log("Erreur: " . ($result['error'] ?? 'Inconnue'));
}

// Afficher les logs
$reflectionLogs = new ReflectionMethod($emailService, 'getLogs');
$reflectionLogs->setAccessible(true);
$logs = $reflectionLogs->invoke($emailService);

if (!empty($logs)) {
    error_log("");
    error_log("════════════════════════════════════════════════════════════════");
    error_log("LOGS DÉTAILLÉS");
    error_log("════════════════════════════════════════════════════════════════");
    foreach ($logs as $log) {
        error_log($log);
    }
}

error_log("");
error_log("════════════════════════════════════════════════════════════════");

echo "Test terminé. Vérifiez les logs PHP (error_log).\n";
echo "Le test affiche tous les détails de ce qui s'est passé.\n";
?>
