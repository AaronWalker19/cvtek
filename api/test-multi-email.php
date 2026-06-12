<?php
/**
 * TEST - Envoi Multi-Email aux Professeurs
 * 
 * Simule l'upload d'un document et l'envoi de notifications
 * aux professeurs abonnés à l'étudiant
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Service/EmailService.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: ENVOI MULTI-EMAIL                                     ║\n";
echo "║  Simule: Étudiant upload document → Emails aux profs         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $db = Database::getConnection();
    
    // ========== 1. Chercher le premier étudiant ==========
    echo "1️⃣  Cherche un étudiant avec des profs abonnés...\n\n";
    
    $stmt = $db->prepare("
        SELECT DISTINCT a.id_user
        FROM abonnement a
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo "❌ Aucun abonnement trouvé!\n";
        echo "   Créer d'abord des abonnements en BD\n";
        exit(1);
    }
    
    $studentId = $row['id_user'];
    
    // ========== 2. Récupérer l'étudiant ==========
    $stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Étudiant trouvé:\n";
    echo "   • ID: {$student['id']}\n";
    echo "   • Nom: {$student['username']}\n";
    echo "   • Email: {$student['email']}\n\n";
    
    // ========== 3. Récupérer les profs abonnés ==========
    echo "2️⃣  Récupère les profs abonnés...\n\n";
    
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.username 
        FROM abonnement a 
        INNER JOIN users u ON a.id_prof = u.id 
        WHERE a.id_user = ? 
        AND u.email IS NOT NULL
        ORDER BY u.username
    ");
    $stmt->execute([$studentId]);
    $profs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($profs)) {
        echo "❌ Aucun prof avec email trouvé!\n";
        echo "   Créer des abonnements avec des profs qui ont des emails\n";
        exit(1);
    }
    
    echo "✅ " . count($profs) . " prof(s) trouvé(s):\n";
    foreach ($profs as $idx => $prof) {
        echo "   [" . ($idx + 1) . "] {$prof['username']} <{$prof['email']}>\n";
    }
    echo "\n";
    
    // ========== 4. Simuler l'envoi ==========
    echo "3️⃣  TEST D'ENVOI MULTI-EMAIL\n\n";
    
    $profEmails = array_column($profs, 'email');
    
    $emailService = new EmailService(true); // Debug mode ON
    
    echo "📧 Envoi d'une notification \"Nouveau document\" à " . count($profEmails) . " destinataire(s)...\n\n";
    
    $result = $emailService->sendNewDocumentNotification(
        $student['email'],
        $student['username'],
        "Test Document - " . date('Y-m-d H:i:s'),
        $profEmails
    );
    
    echo "\n📊 RÉSULTATS:\n";
    echo "═" . str_repeat("═", 60) . "═\n";
    
    $successStr = ($result['success'] ?? false) ? '✅ SUCCÈS' : '❌ ÉCHEC';
    $recipientCount = $result['recipients_count'] ?? 0;
    $sentCount = $result['sent_count'] ?? 0;
    
    echo "Statut: $successStr\n";
    echo "Destinataires ciblés: $recipientCount\n";
    echo "Emails envoyés avec succès: $sentCount\n";
    
    if (isset($result['error'])) {
        echo "Erreur: {$result['error']}\n";
    }
    
    if (isset($result['sent_emails']) && !empty($result['sent_emails'])) {
        echo "\nEmails envoyés:\n";
        foreach ($result['sent_emails'] as $email) {
            echo "   ✅ $email\n";
        }
    }
    
    echo "\n";
    
    // ========== 5. Afficher les logs détaillés ==========
    if (!empty($result['logs'])) {
        echo "📋 LOGS DÉTAILLÉS:\n";
        echo "═" . str_repeat("═", 60) . "═\n";
        foreach ($result['logs'] as $log) {
            echo "   " . $log . "\n";
        }
    }
    
    echo "\n";
    
    // ========== 6. Recommandations ==========
    echo "✨ VÉRIFICATION:\n";
    echo "═" . str_repeat("═", 60) . "═\n";
    
    if ($result['success'] && $sentCount === $recipientCount) {
        echo "✅ TOUS les emails ont été envoyés avec succès!\n";
        echo "   Vérifiez la boîte email des professeurs (15-30 secondes)\n";
    } elseif ($result['success'] && $sentCount > 0) {
        echo "⚠️  Certains emails n'ont pas été envoyés (" . ($recipientCount - $sentCount) . " échoués)\n";
        echo "   Vérifiez les logs ci-dessus pour les détails\n";
    } else {
        echo "❌ L'envoi a échoué complètement\n";
        echo "   Vérifiez:\n";
        echo "   • La configuration SMTP/mail()\n";
        echo "   • Les credentials Gmail\n";
        echo "   • L'extension OpenSSL\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n════════════════════════════════════════════════════════════════\n";
