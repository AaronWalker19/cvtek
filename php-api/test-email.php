<?php
/**
 * Test d'envoi d'email simple
 * Appel: http://localhost/cvtek/php-api/test-email.php
 */
require_once 'Service/EmailService.php';

// Mode DEBUG activé pour voir TOUS les logs
$emailService = new EmailService($debugMode = true);

echo "<h2>Test d'envoi d'email</h2>";
echo "<pre>";

// Test 1: Envoyer à maelmy19@gmail.com
echo "=== TEST 1: Envoyer à maelmy19@gmail.com ===\n\n";
$result = $emailService->sendNewDocumentNotification(
    'test.student@example.com',
    'Test Student',
    'Test Document.pdf',
    ['maelmy19@gmail.com']
);

echo "RÉSULTAT:\n";
var_dump($result);

echo "\n\n=== LOGS DÉTAILLÉS ===\n";
foreach ($emailService->getLogs() as $log) {
    echo $log . "\n";
}

echo "\n</pre>";

echo "<hr>";
echo "<h3>✅ Si tu vois '✅ Email ENVOYÉ' mais l'email n'arrive pas:</h3>";
echo "<ul>";
echo "<li>👉 Vérifie le SPAM de maelmy19@gmail.com</li>";
echo "<li>👉 Vérifie php.ini pour la config SMTP (Windows)</li>";
echo "<li>👉 Essaie avec une autre adresse Gmail</li>";
echo "</ul>";
?>
