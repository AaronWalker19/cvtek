<?php
/**
 * Test Email Notification Debug
 * Teste l'envoi d'email de notification pour un commentaire
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Service/EmailService.php';

error_log("========== EMAIL NOTIFICATION DEBUG ==========");
error_log("Démarrage du test...");

// 1️⃣ VÉRIFIER OPENSSL
error_log("\n📋 ÉTAPE 1: Vérifier OpenSSL");
if (extension_loaded('openssl')) {
    error_log("✅ OpenSSL est activé");
} else {
    error_log("❌ OpenSSL n'est PAS activé!");
    error_log("   Ajoutez 'extension=php_openssl.dll' dans php.ini");
}

// 2️⃣ TESTER LA CONNEXION SMTP
error_log("\n📋 ÉTAPE 2: Test connexion SMTP");
$sock = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
if ($sock) {
    error_log("✅ Connexion SMTP réussie");
    fclose($sock);
} else {
    error_log("❌ Impossible de se connecter à smtp.gmail.com:587");
    error_log("   Erreur: $errstr ($errno)");
    error_log("   Causes possibles:");
    error_log("   • Pare-feu bloque le port 587");
    error_log("   • Pas de connexion internet");
}

// 3️⃣ TESTER L'ENVOI D'EMAIL
error_log("\n📋 ÉTAPE 3: Test envoi d'email");

// Créer le service d'email (avec debug activé)
$emailService = new EmailService(true); // debugMode = true pour voir tous les logs

// Tester avec un étudiant réel depuis la DB
$userRepo = new UserRepository();
$students = $userRepo->findByRole('student');

if (empty($students)) {
    error_log("❌ Aucun étudiant trouvé dans la DB");
    error_log("   Créez un étudiant test d'abord");
} else {
    $student = $students[0];
    error_log("📌 Étudiant test: {$student['username']} ({$student['email']})");
    
    // Appeler le service d'email
    $result = $emailService->sendNewCommentNotification(
        $student['email'],
        $student['username'],
        'Professor Test',
        'Document Test',
        'Ceci est un commentaire de test pour diagnostiquer le problème d\'envoi d\'email'
    );
    
    // Afficher les résultats
    error_log("\n========== RÉSULTAT DE L'ENVOI ==========");
    error_log("Succès: " . ($result['success'] ? 'OUI ✅' : 'NON ❌'));
    
    if (isset($result['error'])) {
        error_log("Erreur: " . $result['error']);
    }
    
    if (isset($result['logs']) && !empty($result['logs'])) {
        error_log("\n📋 Logs détaillés:");
        foreach ($result['logs'] as $log) {
            error_log("   " . $log);
        }
    }
}

error_log("\n========== FIN DU TEST ==========");
echo "Test d'email terminé. Vérifiez les logs PHP.\n";
?>
