<?php
/**
 * Diagnostic COMPLET - Pourquoi les emails de documents ne sont pas livrés?
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║           DIAGNOSTIC COMPLET - EMAILS DE DOCUMENTS NON LIVRÉS                   ║\n";
echo "║                           (11 Juin 2026 - Issue Critique)                       ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// ÉTAPE 1: VÉRIFIER PHP & OS
// ============================================================================
echo "📋 ÉTAPE 1: Configuration PHP\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$os = $is_windows ? "Windows" : "Linux/Unix";

echo "✓ OS: $os\n";
echo "✓ PHP Version: " . phpversion() . "\n";
echo "✓ SAPI: " . php_sapi_name() . "\n";

// ============================================================================
// ÉTAPE 2: VÉRIFIER mail() CONFIGURATION
// ============================================================================
echo "\n📋 ÉTAPE 2: Configuration mail()\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

if ($is_windows) {
    echo "🪟 Configuration Windows:\n";
    $smtp = ini_get('SMTP') ?: "❌ NON CONFIGURÉ";
    $smtp_port = ini_get('smtp_port') ?: "❌ NON CONFIGURÉ";
    echo "  • SMTP = $smtp\n";
    echo "  • smtp_port = $smtp_port\n";
    
    if (strpos($smtp, 'gmail') === false) {
        echo "\n  ⚠️  PROBLÈME CRITIQUE: mail() utilise SMTP par défaut (probablement localhost)\n";
        echo "      Cet SMTP NE peut PAS envoyer d'emails Gmail authentifiés!\n";
        echo "      💡 Solution: Configurer SMTP = smtp.gmail.com + authentification\n";
    }
} else {
    echo "🐧 Configuration Linux:\n";
    echo "  • sendmail_path: " . (ini_get('sendmail_path') ?: "❌ NON CONFIGURÉ") . "\n";
}

// ============================================================================
// ÉTAPE 3: TESTER mail() RÉELLEMENT
// ============================================================================
echo "\n📋 ÉTAPE 3: Test mail() avec email de TEST\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

$test_email = "test-diagnostic-cvtek@example.com";
$test_subject = "Test CVTEK - " . date('Y-m-d H:i:s');
$test_body = "Ceci est un email de diagnostic. Si tu le vois, mail() fonctionne!";

echo "Envoi de test à: $test_email\n";
$mail_result = @mail($test_email, $test_subject, $test_body, "From: cvtek@example.com\r\n");

if ($mail_result) {
    echo "✅ mail() retourne TRUE (a accepté le message)\n";
    echo "⚠️  IMPORTANT: Cela NE garantit PAS que l'email sera livré!\n";
    echo "   Ça signifie seulement que la fonction a accepté de le traiter.\n";
    echo "   L'email peut être perdu si SMTP n'est pas correctement configuré.\n";
} else {
    echo "❌ mail() retourne FALSE (impossible d'accepter le message)\n";
    echo "   Cela signifie que mail() NE fonctionne clairement pas.\n";
}

// ============================================================================
// ÉTAPE 4: VÉRIFIER SI OPENSSL EST PRÉSENT (pour SMTP)
// ============================================================================
echo "\n📋 ÉTAPE 4: Extensions disponibles pour SMTP\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

$openssl_loaded = extension_loaded('openssl');
$sockets_loaded = extension_loaded('sockets');

echo "✓ OpenSSL: " . ($openssl_loaded ? "✅ PRÉSENT" : "❌ ABSENT") . "\n";
echo "✓ Sockets: " . ($sockets_loaded ? "✅ PRÉSENT" : "❌ ABSENT") . "\n";

if (!$openssl_loaded || !$sockets_loaded) {
    echo "\n❌ PROBLÈME: Extensions manquantes pour SMTP TLS!\n";
    echo "   Solution: Activer dans php.ini\n";
    echo "   • extension=php_openssl.dll\n";
    echo "   • extension=php_sockets.dll\n";
}

// ============================================================================
// ÉTAPE 5: TESTER CONNEXION SMTP
// ============================================================================
echo "\n📋 ÉTAPE 5: Test connexion SMTP Gmail\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

$sock = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
if ($sock) {
    echo "✅ Connexion à smtp.gmail.com:587 réussie\n";
    fclose($sock);
} else {
    echo "❌ Connexion à smtp.gmail.com:587 échouée\n";
    echo "   Erreur: $errstr ($errno)\n";
    echo "   Causes possibles:\n";
    echo "   • Pare-feu bloque le port 587\n";
    echo "   • Pas de connexion internet\n";
}

// ============================================================================
// ÉTAPE 6: AFFICHER LA STRATÉGIE UTILISÉE
// ============================================================================
echo "\n📋 ÉTAPE 6: Stratégie d'envoi utilisée\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

echo "Pour les DOCUMENTS (depuis sendEmailForDocument):\n";
echo "1. SMTP Gmail (avec authentification) → PRIORITÉ\n";
echo "2. mail() système → Fallback\n\n";

echo "Pour les COMMENTAIRES (depuis sendEmail):\n";
echo "1. mail() système\n";
echo "2. SMTP Gmail → Fallback\n";

// ============================================================================
// ÉTAPE 7: RECOMMANDATIONS
// ============================================================================
echo "\n📋 ÉTAPE 7: Recommandations pour RÉSOUDRE le problème\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

echo "🔴 PROBLÈME: Les logs disent \"✅ Email envoyé\" mais le professeur ne reçoit RIEN\n\n";

echo "Cela peut être causé par:\n\n";

echo "1️⃣  mail() retourne TRUE mais n'envoie rien\n";
echo "   • Windows sans SMTP Gmail configuré\n";
echo "   • Solution: mail() sera contourné, SMTP utilisé en priorité pour docs\n\n";

echo "2️⃣  Les emails des professeurs sont INCORRECTS en BD\n";
echo "   • mael.valin@unilim.fr vs mael.valin@etu.unilim.fr\n";
echo "   • Solution: Vérifier les emails sauvegardés\n";
echo "   • Query: SELECT id, email FROM users WHERE role = 'professor';\n\n";

echo "3️⃣  SMTP ne fonctionne pas (OpenSSL manquant, etc)\n";
echo "   • Solution: Vérifier diagnostic ÉTAPE 4\n";
echo "   • Activer OpenSSL dans php.ini\n\n";

echo "4️⃣  Les credentials Gmail sont INCORRECTS\n";
echo "   • Email: benoitccasibio@gmail.com\n";
echo "   • App Code: aiwachtcdfioihsi\n";
echo "   • Solution: Tester avec GET-LOGS.PHP\n\n";

// ============================================================================
// ÉTAPE 8: PROCÉDURE COMPLÈTE DE TEST
// ============================================================================
echo "\n📋 ÉTAPE 8: Procédure complète de TEST\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

echo "1. Exécuter ce script: diagnostic-email-complete-documents.php ✓\n";
echo "2. Vérifier les résultats (toutes les étapes)\n";
echo "3. Exécuter: test-document-email.php\n";
echo "   • Cela teste réellement l'envoi d'email de document\n";
echo "   • Affiche les logs détaillés du service\n";
echo "4. Publier un document dans l'interface\n";
echo "5. Vérifier les logs professeur (il devrait recevoir l'email)\n";
echo "6. Si ça ne fonctionne toujours pas, partager les logs du php-api\n";

// ============================================================================
// AFFICHER LA CONFIGURATION ACTUELLE
// ============================================================================
echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    CONFIGURATION PHP ACTUELLE                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "php.ini (chemins possibles):\n";
echo "• XAMPP: C:\\xampp\\php\\php.ini\n";
echo "• Personnalisé: php -i | grep 'Loaded Configuration'\n\n";

echo "Paramètres importants à vérifier:\n";
echo "✓ extension=php_openssl.dll (dé-commenté)\n";
echo "✓ extension=php_sockets.dll (dé-commenté)\n";
echo "✓ SMTP = smtp.gmail.com (Windows)\n";
echo "✓ smtp_port = 587 (Windows)\n\n";

echo "Après modification de php.ini:\n";
echo "1. Redémarrer Apache (xampp control panel ou services)\n";
echo "2. Tester avec test-document-email.php\n";
echo "3. Publier un nouveau document pour vérifier\n";

echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                        FIN DU DIAGNOSTIC                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";
?>
