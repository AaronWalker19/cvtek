<?php
/**
 * Test Envoi d'Email de Document (Diagnostic)
 * Teste l'envoi d'email de notification pour un nouveau document
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Repository/AbonnementRepository.php';
require_once __DIR__ . '/Service/EmailService.php';

error_log("========== TEST DOCUMENT NOTIFICATION EMAIL ==========");

// 1️⃣ TROUVER UN ÉTUDIANT AVEC DOCUMENT
error_log("\n1️⃣ ÉTAPE: Récupérer un étudiant avec professeur abonné");
error_log("─────────────────────────────────────────────────────────────────");

try {
    $db = Database::getConnection();
    $abonnementRepo = new AbonnementRepository();
    
    // Trouver un abonnement (prof suivi étudiant)
    $stmt = $db->prepare("
        SELECT a.id_user as student_id, a.id_prof as prof_id, 
               s.username as student_name, s.email as student_email,
               p.username as prof_name, p.email as prof_email
        FROM abonnement a
        INNER JOIN users s ON a.id_user = s.id
        INNER JOIN users p ON a.id_prof = p.id
        WHERE s.role = 'student' AND p.role = 'professor'
        LIMIT 1
    ");
    $stmt->execute();
    $abonnement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$abonnement) {
        error_log("❌ Aucun abonnement trouvé (aucun professeur ne suit aucun étudiant)");
        exit;
    }
    
    error_log("✅ Abonnement trouvé:");
    error_log("   Étudiant: {$abonnement['student_name']} ({$abonnement['student_email']})");
    error_log("   Professeur: {$abonnement['prof_name']} ({$abonnement['prof_email']})");
    
    $studentId = $abonnement['student_id'];
    $studentName = $abonnement['student_name'];
    $studentEmail = $abonnement['student_email'];
    $profEmail = $abonnement['prof_email'];
    $profName = $abonnement['prof_name'];
    
} catch (Exception $e) {
    error_log("❌ Erreur BD: " . $e->getMessage());
    exit;
}

// 2️⃣ VÉRIFIER QUE getProfEmailsByUser RETOURNE LE BON EMAIL
error_log("\n2️⃣ ÉTAPE: Vérifier getProfEmailsByUser()");
error_log("─────────────────────────────────────────────────────────────────");

try {
    $profEmails = $abonnementRepo->getProfEmailsByUser($studentId);
    error_log("✅ getProfEmailsByUser($studentId) retourne:");
    if (empty($profEmails)) {
        error_log("   ❌ VIDE! Aucun email retourné!");
    } else {
        foreach ($profEmails as $email) {
            error_log("   • $email");
        }
    }
} catch (Exception $e) {
    error_log("❌ Erreur: " . $e->getMessage());
    exit;
}

// 3️⃣ TESTER L'ENVOI AVEC EMAIL SERVICE
error_log("\n3️⃣ ÉTAPE: Tester l'envoi avec EmailService");
error_log("─────────────────────────────────────────────────────────────────");

$emailService = new EmailService(true); // Debug mode activé

// Simuler un envoi de document
$documentName = "R504 PPP consignes retex stage 26.pdf";
$result = $emailService->sendNewDocumentNotification(
    $studentEmail,
    $studentName,
    $documentName,
    $profEmails // Utiliser les emails retournés par getProfEmailsByUser
);

// 4️⃣ AFFICHER LES RÉSULTATS
error_log("\n4️⃣ RÉSULTATS");
error_log("─────────────────────────────────────────────────────────────────");

if ($result['success']) {
    error_log("✅ EMAIL ENVOYÉ AVEC SUCCÈS!");
    error_log("   Destinataires: " . implode(", ", $profEmails));
} else {
    error_log("❌ ÉCHEC DE L'ENVOI");
    if (isset($result['error'])) {
        error_log("   Erreur: " . $result['error']);
    }
}

// 5️⃣ AFFICHER TOUS LES LOGS DÉTAILLÉS
if (isset($result['logs']) && !empty($result['logs'])) {
    error_log("\n5️⃣ LOGS DÉTAILLÉS DU SERVICE");
    error_log("─────────────────────────────────────────────────────────────────");
    foreach ($result['logs'] as $log) {
        error_log("   " . $log);
    }
}

error_log("\n========== FIN DU TEST ==========\n");
?>
