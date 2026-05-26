<?php

/**
 * php-config-test.php
 * Affiche la configuration PHP
 */

echo "=== CONFIGURATION PHP ===\n\n";

echo "📌 Version PHP: " . phpversion() . "\n";
echo "🖥️  OS: " . php_uname() . "\n\n";

echo "📦 Extensions chargées:\n";
$extensions = ['openssl', 'sockets', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext\n";
    } else {
        echo "   ❌ $ext (NON DISPONIBLE)\n";
    }
}

echo "\n🔐 Support Mail:\n";
echo "   mail() disponible: " . (function_exists('mail') ? '✅ OUI' : '❌ NON') . "\n";
echo "   fsockopen disponible: " . (function_exists('fsockopen') ? '✅ OUI' : '❌ NON') . "\n";

echo "\n📋 Constantes OpenSSL:\n";
$tlsConstants = [
    'STREAM_CRYPTO_METHOD_TLS_CLIENT',
    'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT',
    'STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT'
];
foreach ($tlsConstants as $const) {
    if (defined($const)) {
        echo "   ✅ $const = " . constant($const) . "\n";
    } else {
        echo "   ❌ $const (non défini)\n";
    }
}

echo "\n📁 Configuration fichiers:\n";
echo "   error_log: " . (ini_get('error_log') ?: 'défaut du système') . "\n";
echo "   log_errors: " . (ini_get('log_errors') ? 'OUI' : 'NON') . "\n";
echo "   display_errors: " . (ini_get('display_errors') ? 'OUI' : 'NON') . "\n";

echo "\n🔍 SMTP (si configuré pour mail()):\n";
echo "   SMTP: " . (ini_get('SMTP') ?: 'non configuré') . "\n";
echo "   smtp_port: " . (ini_get('smtp_port') ?: 'non configuré') . "\n";

echo "\n✅ Configuration terminée\n";
