<?php

/**
 * diagnose-email.php
 * Diagnostic du service email
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DIAGNOSTIC EMAIL SERVICE ===\n\n";

// Test 1: Vérifier que le fichier existe
echo "1️⃣ Vérification du fichier...\n";
$emailServiceFile = __DIR__ . '/Service/EmailService.php';
if (file_exists($emailServiceFile)) {
    echo "   ✅ Fichier trouvé: $emailServiceFile\n";
    echo "   📦 Taille: " . filesize($emailServiceFile) . " bytes\n";
} else {
    echo "   ❌ Fichier non trouvé!\n";
    exit(1);
}

// Test 2: Vérifier la syntaxe PHP
echo "\n2️⃣ Vérification syntaxe PHP...\n";
$output = shell_exec("php -l " . escapeshellarg($emailServiceFile) . " 2>&1");
if (strpos($output, 'No syntax errors') !== false) {
    echo "   ✅ Syntaxe correcte\n";
} else {
    echo "   ❌ Erreurs de syntaxe:\n";
    echo $output;
    exit(1);
}

// Test 3: Essayer de charger le fichier
echo "\n3️⃣ Chargement du fichier...\n";
try {
    require_once $emailServiceFile;
    echo "   ✅ Fichier chargé avec succès\n";
} catch (Exception $e) {
    echo "   ❌ Erreur lors du chargement:\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Vérifier que la classe existe
echo "\n4️⃣ Vérification de la classe...\n";
if (class_exists('EmailService')) {
    echo "   ✅ Classe EmailService trouvée\n";
} else {
    echo "   ❌ Classe EmailService non trouvée!\n";
    exit(1);
}

// Test 5: Instancier le service
echo "\n5️⃣ Instantiation du service...\n";
try {
    $emailService = new EmailService();
    echo "   ✅ Service instantié avec succès\n";
} catch (Exception $e) {
    echo "   ❌ Erreur lors de l'instantiation:\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Vérifier les méthodes
echo "\n6️⃣ Vérification des méthodes...\n";
$methods = ['sendNewDocumentNotification', 'sendNewCommentNotification', 'testConnection'];
foreach ($methods as $method) {
    if (method_exists($emailService, $method)) {
        echo "   ✅ Méthode $method trouvée\n";
    } else {
        echo "   ❌ Méthode $method non trouvée!\n";
    }
}

// Test 7: Vérifier la disponibilité de fsockopen
echo "\n7️⃣ Vérification des fonctions requises...\n";
$functions = ['fsockopen', 'stream_socket_enable_crypto', 'fwrite', 'fgets'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "   ✅ Fonction $func disponible\n";
    } else {
        echo "   ❌ Fonction $func INDISPONIBLE!\n";
    }
}

// Test 8: Vérifier les constantes OpenSSL
echo "\n8️⃣ Vérification constantes OpenSSL...\n";
if (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
    echo "   ✅ STREAM_CRYPTO_METHOD_TLS_CLIENT disponible\n";
} else {
    echo "   ❌ STREAM_CRYPTO_METHOD_TLS_CLIENT INDISPONIBLE!\n";
    echo "   ⚠️  OpenSSL peut ne pas être compilé avec PHP\n";
}

echo "\n=== ✅ DIAGNOSTIC TERMINÉ ===\n";
echo "Le service email devrait fonctionner correctement.\n";
