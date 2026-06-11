<?php
/**
 * Diagnostic Complet - Système d'Email CVTEK
 * Vérifie tous les points de défaillance possibles
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                  DIAGNOSTIC COMPLET - SYSTÈME EMAIL CVTEK                      ║\n";
echo "║                              (11 Juin 2026)                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// 1️⃣ DIAGNOSTIC PHP
// ============================================================================
echo "📋 1️⃣ DIAGNOSTIC PHP\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

$php_version = phpversion();
$os = PHP_OS;
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

echo "✓ PHP Version: $php_version\n";
echo "✓ OS: $os" . ($is_windows ? " (Windows detecté)" : " (Unix/Linux)") . "\n";
echo "✓ SAPI: " . php_sapi_name() . "\n";

// ============================================================================
// 2️⃣ DIAGNOSTIC EXTENSIONS
// ============================================================================
echo "\n📋 2️⃣ DIAGNOSTIC EXTENSIONS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

$extensions = [
    'openssl' => 'OpenSSL (SMTP TLS)',
    'sockets' => 'Sockets (fsockopen)',
    'pdo_mysql' => 'PDO MySQL (Base de données)',
];

foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "✅ ACTIVÉ" : "❌ ABSENT";
    echo "$status: $desc ($ext)\n";
}

// ============================================================================
// 3️⃣ DIAGNOSTIC CONFIGURATION MAIL
// ============================================================================
echo "\n📋 3️⃣ DIAGNOSTIC CONFIGURATION MAIL\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

if ($is_windows) {
    echo "🪟 Configuration Windows (mail() utilise SMTP)\n";
    $smtp = ini_get('SMTP');
    $smtp_port = ini_get('smtp_port');
    echo "  • SMTP: " . ($smtp ?: "❌ NON CONFIGURÉ") . "\n";
    echo "  • smtp_port: " . ($smtp_port ?: "❌ NON CONFIGURÉ") . "\n";
    
    if (!$smtp) {
        echo "\n  ⚠️  PROBLÈME: SMTP non configuré pour mail()!\n";
        echo "  📝 Solution: Ajouter dans php.ini:\n";
        echo "     SMTP = smtp.gmail.com\n";
        echo "     smtp_port = 587\n";
    }
} else {
    echo "🐧 Configuration Linux/Unix (mail() utilise sendmail)\n";
    $sendmail = ini_get('sendmail_path');
    echo "  • sendmail_path: " . ($sendmail ?: "❌ NON CONFIGURÉ") . "\n";
}

// ============================================================================
// 4️⃣ TEST CONNEXION RÉSEAU
// ============================================================================
echo "\n📋 4️⃣ TEST CONNEXION RÉSEAU\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

echo "Test: Connexion à smtp.gmail.com:587...\n";
$sock = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);

if ($sock) {
    echo "  ✅ SUCCÈS: Connexion établie à SMTP Gmail\n";
    fclose($sock);
} else {
    echo "  ❌ ÉCHEC: Impossible de se connecter\n";
    echo "  Erreur: $errstr ($errno)\n";
    echo "  Causes possibles:\n";
    echo "    • Pare-feu bloque le port 587\n";
    echo "    • Pas de connexion internet\n";
    echo "    • Serveur SMTP injoignable\n";
}

// ============================================================================
// 5️⃣ TEST BASE DE DONNÉES
// ============================================================================
echo "\n📋 5️⃣ TEST BASE DE DONNÉES\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

try {
    require_once __DIR__ . '/db.php';
    
    $db = Database::getConnection();
    
    if ($db) {
        echo "  ✅ Connexion à la base de données réussie\n";
        
        // Vérifier qu'il y a des utilisateurs
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('student', 'professor')");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'] ?? 0;
        
        echo "  ✅ Utilisateurs trouvés: $count\n";
        
        // Vérifier qu'il y a au moins un étudiant avec email
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND email IS NOT NULL AND email != ''");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $students = $result['count'] ?? 0;
        
        echo "  ✅ Étudiants avec email: $students\n";
        
    } else {
        echo "  ❌ Impossible de se connecter à la base de données\n";
    }
} catch (Exception $e) {
    echo "  ❌ Erreur base de données: " . $e->getMessage() . "\n";
}

// ============================================================================
// 6️⃣ TEST SERVICE EMAIL
// ============================================================================
echo "\n📋 6️⃣ TEST SERVICE EMAIL\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

try {
    require_once __DIR__ . '/Service/EmailService.php';
    
    $emailService = new EmailService(true); // Mode debug activé
    
    echo "  ✅ Service EmailService chargé\n";
    
    // Tester la méthode testConnection()
    if (method_exists($emailService, 'testConnection')) {
        echo "\n  Test: testConnection()...\n";
        // (Cette méthode peut ne pas exister, on teste juste si c'est possible)
    }
    
} catch (Exception $e) {
    echo "  ❌ Erreur lors du chargement du service: " . $e->getMessage() . "\n";
}

// ============================================================================
// 7️⃣ RECOMMANDATIONS
// ============================================================================
echo "\n📋 7️⃣ RECOMMANDATIONS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";

if ($is_windows) {
    echo "\n🪟 Pour Windows:\n";
    $smtp = ini_get('SMTP');
    if (!$smtp) {
        echo "  1️⃣  Ouvrir: C:\\xampp\\php\\php.ini (ou votre chemin PHP)\n";
        echo "  2️⃣  Chercher ou ajouter:\n";
        echo "      [mail function]\n";
        echo "      SMTP = smtp.gmail.com\n";
        echo "      smtp_port = 587\n";
        echo "  3️⃣  Redémarrer Apache (xampp restart)\n";
    }
}

echo "\n  Ensuite, tester l'envoi d'email avec:\n";
echo "  📝 Créer un commentaire dans l'interface\n";
echo "  📝 Vérifier la console du navigateur (F12 → Console)\n";
echo "  📝 Chercher les logs avec le prefix '[EMAIL]'\n";

echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                        FIN DU DIAGNOSTIC                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";
?>
