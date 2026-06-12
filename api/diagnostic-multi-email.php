<?php
/**
 * 🔍 DIAGNOSTIC - Envoi Multi-Email
 * Vérifie: 
 * 1. La requête SQL pour récupérer les profs abonnés
 * 2. Si les profs ont des emails
 * 3. Si les emails sont bien envoyés à TOUS
 * 4. La stratégie d'envoi (individual vs batch)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Service/EmailService.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🔍 DIAGNOSTIC: ENVOI MULTI-EMAILS AUX PROFESSEURS           ║\n";
echo "║  Status des Abonnements & Notifications                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $db = Database::getConnection();
    
    // ========== 1. Vérifier les abonnements actifs ==========
    echo "1️⃣  ABONNEMENTS ACTIFS\n";
    echo "────────────────────────────────────────────────────────────\n";
    
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.id_prof,
            a.id_user,
            prof.username as prof_name,
            prof.email as prof_email,
            student.username as student_name,
            student.email as student_email,
            a.created_at
        FROM abonnement a
        INNER JOIN users prof ON a.id_prof = prof.id
        INNER JOIN users student ON a.id_user = student.id
        ORDER BY a.id_user, a.id_prof
    ");
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo "❌ AUCUN ABONNEMENT trouvé!\n";
        echo "   → Créer des abonnements avant de tester les emails\n\n";
    } else {
        $subCount = count($subscriptions);
        echo "✅ $subCount abonnements trouvés:\n\n";
        
        // Grouper par étudiant
        $byStudent = [];
        foreach ($subscriptions as $sub) {
            $studentId = $sub['id_user'];
            if (!isset($byStudent[$studentId])) {
                $byStudent[$studentId] = [
                    'student_name' => $sub['student_name'],
                    'student_email' => $sub['student_email'],
                    'profs' => []
                ];
            }
            $byStudent[$studentId]['profs'][] = [
                'prof_id' => $sub['id_prof'],
                'prof_name' => $sub['prof_name'],
                'prof_email' => $sub['prof_email'],
                'has_email' => !empty($sub['prof_email']),
                'created_at' => $sub['created_at']
            ];
        }
        
        foreach ($byStudent as $studentId => $data) {
            echo "👤 Étudiant: {$data['student_name']} <{$data['student_email']}>\n";
            $profCount = count($data['profs']);
            echo "   Profs abonnés: $profCount\n";
            
            foreach ($data['profs'] as $prof) {
                $emailIcon = $prof['has_email'] ? '✅' : '❌';
                echo "   $emailIcon → {$prof['prof_name']} <{$prof['prof_email']}>\n";
            }
            echo "\n";
        }
    }
    
    // ========== 2. Tester la requête SQL du DocumentController ==========
    echo "\n2️⃣  TEST REQUÊTE SQL (DocumentController)\n";
    echo "────────────────────────────────────────────────────────────\n";
    
    // Prendre le premier abonnement comme exemple
    if (!empty($subscriptions)) {
        $testUserId = $subscriptions[0]['id_user'];
        echo "Test avec étudiant ID: $testUserId\n\n";
        
        $stmt = $db->prepare("
            SELECT u.id, u.email, u.username 
            FROM abonnement a 
            INNER JOIN users u ON a.id_prof = u.id 
            WHERE a.id_user = ? 
            AND u.email IS NOT NULL
        ");
        $stmt->execute([$testUserId]);
        $profs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $profCount = count($profs);
        echo "Requête SQL retourne: $profCount prof(s)\n";
        
        if (!empty($profs)) {
            $emails = array_column($profs, 'email');
            echo "\nEmails trouvés:\n";
            foreach ($emails as $i => $email) {
                echo "  [" . ($i + 1) . "] $email\n";
            }
            
            // ========== 3. Simuler l'envoi ==========
            echo "\n3️⃣  SIMULATION ENVOI\n";
            echo "────────────────────────────────────────────────────────────\n";
            
            $emailService = new EmailService(true); // Debug mode ON
            
            $result = $emailService->sendNewDocumentNotification(
                "student@example.com",
                "Test Student",
                "Document de Test",
                $emails
            );
            
            echo "\n📊 RÉSULTATS:\n";
            $successStr = ($result['success'] ?? false) ? '✅ OUI' : '❌ NON';
            $recipientCount = $result['recipients_count'] ?? 0;
            $sentCount = $result['sent_count'] ?? 0;
            $errorMsg = $result['error'] ?? 'Aucune';
            
            echo "   ├─ Succès: $successStr\n";
            echo "   ├─ Destinataires totaux: $recipientCount\n";
            echo "   ├─ Envoyés avec succès: $sentCount\n";
            echo "   └─ Erreur: $errorMsg\n";
            
            if (!empty($result['logs'])) {
                echo "\n📋 LOGS DÉTAILLÉS:\n";
                echo "────────────────────────────────────────────────────────────\n";
                foreach ($result['logs'] as $log) {
                    echo "   " . $log . "\n";
                }
            }
            
        } else {
            echo "⚠️  Aucun prof avec email trouvé pour cet étudiant!\n";
        }
    }
    
    // ========== 4. Vérifier la stratégie d'envoi ==========
    echo "\n\n4️⃣  STRATÉGIE D'ENVOI ACTUELLE\n";
    echo "────────────────────────────────────────────────────────────\n";
    echo "📤 Méthode: INDIVIDUELLE (1 connexion par destinataire)\n";
    echo "   ✅ Pros: Robustesse, gestion d'erreur par email\n";
    echo "   ❌ Cons: Plus lent, 1 connexion × N destinataires\n";
    echo "   \n";
    echo "⚙️  Délai inter-envois: 300ms\n";
    echo "   → Évite rate-limiting Gmail\n";
    
    // ========== 5. Recommandations ==========
    echo "\n\n5️⃣  RECOMMANDATIONS\n";
    echo "────────────────────────────────────────────────────────────\n";
    
    $issues = [];
    
    // Vérifier si y a plusieurs abonnements
    if (count($subscriptions) <= 1) {
        $issues[] = "Seul 1 abonnement existe - ajouter plus de profs abonnés pour tester le multi-email";
    }
    
    // Vérifier si tous les profs ont des emails
    $profsWithoutEmail = array_filter($subscriptions, fn($s) => empty($s['prof_email']));
    if (!empty($profsWithoutEmail)) {
        $profsWithoutCount = count($profsWithoutEmail);
        $issues[] = "$profsWithoutCount prof(s) sans email - les ajouter d'abord!";
    }
    
    // Vérifier OpenSSL
    if (!extension_loaded('openssl')) {
        $issues[] = "Extension OpenSSL non chargée - ajouter dans php.ini";
    }
    
    if (empty($issues)) {
        echo "✅ Tout semble OK! Le problème vient peut-être de:\n";
        echo "   • La configuration du serveur SMTP\n";
        echo "   • Les logs non envoyés au client\n";
        echo "   • Les emails marqués comme spam\n";
    } else {
        echo "🔴 PROBLÈMES DÉTECTÉS:\n";
        foreach ($issues as $i => $issue) {
            echo "   " . ($i + 1) . ". " . $issue . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n════════════════════════════════════════════════════════════════\n";
