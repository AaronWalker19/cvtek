<?php
/**
 * test-email-send.php
 * Test d'envoi d'email direct
 */

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Service/EmailService.php';

echo "<h1>Email Send Test</h1>\n";

$emailService = new EmailService();

// Test: Send test email to mael
$result = $emailService->sendNewCommentNotification(
    'malpriv19@gmail.com',
    'mael',
    'professor_test',
    'Test Document',
    'This is a test comment'
);

echo "<h2>Result:</h2>\n";
echo "<pre>\n";
var_dump($result);
echo "</pre>\n";

if ($result['success']) {
    echo "<p style='color:green'>SUCCESS: Email sent!</p>\n";
} else {
    echo "<p style='color:red'>FAILED: " . ($result['error'] ?? 'Unknown error') . "</p>\n";
}

?>
