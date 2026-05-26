<?php

/**
 * Test Diagnostics pour EmailService
 * Vérifie la configuration PHP et l'état d'envoi d'email
 */

echo "=============================================\n";
echo "  EMAIL SYSTEM DIAGNOSTICS\n";
echo "=============================================\n\n";

// 1. Vérifier PHP mail()
echo "1️⃣  PHP mail() Function:\n";
if (function_exists('mail')) {
    echo "   ✅ Disponible\n";
    echo "   - sendmail_path: " . (ini_get('sendmail_path') ?: '❌ NON CONFIGURÉ') . "\n";
    echo "   - SMTP: " . (ini_get('SMTP') ?: '❌ NON CONFIGURÉ') . "\n";
    echo "   - smtp_port: " . ini_get('smtp_port') . "\n";
} else {
    echo "   ❌ NON DISPONIBLE\n";
}

echo "\n2️⃣  OpenSSL / TLS Support:\n";
if (extension_loaded('openssl')) {
    echo "   ✅ OpenSSL Extension chargée\n";
} else {
    echo "   ❌ OpenSSL Extension NON CHARGÉE\n";
}

// Vérifier les constantes TLS
echo "\n   Constantes TLS disponibles:\n";
if (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
    echo "   ✅ STREAM_CRYPTO_METHOD_TLS_CLIENT\n";
} else {
    echo "   ❌ STREAM_CRYPTO_METHOD_TLS_CLIENT\n";
}

if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
    echo "   ✅ STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT\n";
} else {
    echo "   ❌ STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT\n";
}

echo "\n3️⃣  Socket Functions (pour SMTP direct):\n";
if (function_exists('fsockopen')) {
    echo "   ✅ fsockopen disponible\n";
} else {
    echo "   ❌ fsockopen NON disponible\n";
}

echo "\n4️⃣  Configuration SMTP Gmail:\n";
echo "   - Host: smtp.gmail.com\n";
echo "   - Port: 587\n";
echo "   - TLS: Required\n";
echo "   - Email: benoitccasibio@gmail.com\n";
echo "   - Mot de passe: aiwachtcdfioihsi (code app Gmail)\n";

// Test de connexion SMTP simple
echo "\n5️⃣  Test de Connexion SMTP:\n";
echo "   Tentative de connexion à smtp.gmail.com:587...\n";

$timeout = 5;
$errno = 0;
$errstr = '';

$sock = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, $timeout);

if ($sock) {
    echo "   ✅ Connexion établie\n";
    
    // Lire la réponse du serveur
    $response = fgets($sock, 512);
    echo "   Réponse serveur: " . trim($response) . "\n";
    
    if (strpos($response, '220') !== false) {
        echo "   ✅ Serveur SMTP prêt\n";
    } else {
        echo "   ❌ Réponse SMTP inattendue\n";
    }
    
    fclose($sock);
} else {
    echo "   ❌ Impossible de se connecter\n";
    echo "   Erreur: $errstr ($errno)\n";
}

echo "\n6️⃣  Test d'Envoi d'Email:\n";

// Inclure le service EmailService
require_once __DIR__ . '/Service/EmailService.php';

try {
    $emailService = new EmailService();
    
    // Test d'envoi
    $testEmail = 'test@example.com';
    $testSubject = 'CVTEK Test Email';
    $testBody = '<html><body><h1>Test Email</h1><p>Ceci est un email de test du système CVTEK.</p></body></html>';
    
    echo "   Envoi d'un email de test à: $testEmail\n";
    
    // Utiliser la réflexion pour accéder aux méthodes privées
    $reflection = new ReflectionClass('EmailService');
    $method = $reflection->getMethod('sendEmail');
    $method->setAccessible(true);
    
    $result = $method->invokeArgs($emailService, [$testEmail, $testSubject, $testBody]);
    
    echo "\n   Résultat:\n";
    echo "   - Succès: " . ($result['success'] ? '✅ OUI' : '❌ NON') . "\n";
    if (isset($result['method'])) {
        echo "   - Méthode utilisée: " . $result['method'] . "\n";
    }
    if (isset($result['error'])) {
        echo "   - Erreur: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=============================================\n";
echo "  DIAGNOSTIC COMPLÉTÉ\n";
echo "=============================================\n";
