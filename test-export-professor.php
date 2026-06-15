<?php
/**
 * test-export-professor.php
 * Test de l'export des fichiers par un professeur
 */

// Configuration
define('API_URL', 'http://localhost/cvtek/api');
define('PROF_ID', 51);      // Prof: Mael Valin (valinp01)
define('STUDENT_ID', 69);   // Student: Valin Mael (valin6)

echo "🧪 Test Export - Professor Side\n";
echo "================================\n\n";

// 1. Test de l'authentification prof
echo "1️⃣  Testing professor authentication...\n";
$session = curl_init();
curl_setopt_array($session, [
    CURLOPT_URL => API_URL . '/auth/user',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_COOKIEJAR => '/tmp/cvtek_cookies.txt',
    CURLOPT_COOKIEFILE => '/tmp/cvtek_cookies.txt',
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($session);
$httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
echo "   Response code: $httpCode\n";
echo "   Response: " . substr($response, 0, 100) . "...\n\n";

// 2. Test récupération des documents
echo "2️⃣  Getting student documents...\n";
curl_setopt_array($session, [
    CURLOPT_URL => API_URL . '/documents',
    CURLOPT_CUSTOMREQUEST => 'GET'
]);

$response = curl_exec($session);
$httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
$docs = json_decode($response, true);
echo "   Response code: $httpCode\n";
echo "   Documents found: " . (is_array($docs) ? count($docs) : 0) . "\n";
if (is_array($docs) && !empty($docs)) {
    foreach ($docs as $doc) {
        echo "   - " . $doc['titre'] . " (ID: " . $doc['id'] . ")\n";
    }
}
echo "\n";

// 3. Test de l'export
echo "3️⃣  Testing export...\n";
curl_setopt_array($session, [
    CURLOPT_URL => API_URL . '/export',
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(['student_ids' => [$STUDENT_ID]]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/zip'
    ],
    CURLOPT_BINARYTRANSFER => true
]);

$response = curl_exec($session);
$httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($session, CURLINFO_CONTENT_TYPE);

echo "   Response code: $httpCode\n";
echo "   Content-Type: $contentType\n";
echo "   Response size: " . strlen($response) . " bytes\n\n";

if ($httpCode === 200) {
    // Sauvegarder le ZIP
    file_put_contents('/tmp/export_test.zip', $response);
    echo "✅ ZIP saved to /tmp/export_test.zip\n";
} else {
    echo "❌ Export failed!\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

curl_close($session);
?>
