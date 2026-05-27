<?php
/**
 * diagnose-comment-email.php
 * Diagnostic du problème d'email lors de la création de commentaire
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Service/EmailService.php';
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║         DIAGNOSTIC: EMAIL COMMENTAIRE (ID 12)                      ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

try {
    $conn = Database::getConnection();
    
    // Étape 1: Récupérer le commentaire
    echo "📌 ÉTAPE 1: Récupération du commentaire ID 12\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT * FROM commentaire WHERE id = ?");
    $stmt->execute([12]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comment) {
        echo "❌ Commentaire ID 12 non trouvé!\n";
        exit(1);
    }
    
    echo "✅ Commentaire trouvé:\n";
    echo "   ID: {$comment['id']}\n";
    echo "   Texte: " . substr($comment['text'], 0, 50) . "...\n";
    echo "   Auteur (ID): {$comment['id_user']}\n";
    echo "   Version: {$comment['id_docversion']}\n";
    echo "   Date: {$comment['date']}\n\n";
    
    // Étape 2: Récupérer l'auteur (professeur)
    echo "📌 ÉTAPE 2: Récupération du professeur (auteur du commentaire)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$comment['id_user']]);
    $professor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$professor) {
        echo "❌ Professeur ID {$comment['id_user']} non trouvé!\n";
        exit(1);
    }
    
    echo "✅ Professeur trouvé:\n";
    echo "   ID: {$professor['id']}\n";
    echo "   Nom: {$professor['username']}\n";
    echo "   Email: {$professor['email']}\n";
    echo "   Rôle: {$professor['role']}\n\n";
    
    // Étape 3: Récupérer le document
    echo "📌 ÉTAPE 3: Récupération du document (pour trouver l'étudiant)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT * FROM doc_version WHERE id = ?");
    $stmt->execute([$comment['id_docversion']]);
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$version) {
        echo "❌ Version ID {$comment['id_docversion']} non trouvée!\n";
        exit(1);
    }
    
    $stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$version['id_doc']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        echo "❌ Document ID {$version['id_doc']} non trouvé!\n";
        exit(1);
    }
    
    echo "✅ Document trouvé:\n";
    echo "   ID: {$document['id']}\n";
    echo "   Titre: " . ($document['titre'] ?: $document['nom_fichier']) . "\n";
    echo "   Propriétaire (Étudiant): {$document['user_id']}\n\n";
    
    // Étape 4: Récupérer l'étudiant
    echo "📌 ÉTAPE 4: Récupération de l'étudiant (destinataire de l'email)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$document['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo "❌ Étudiant ID {$document['user_id']} non trouvé!\n";
        exit(1);
    }
    
    echo "✅ Étudiant trouvé:\n";
    echo "   ID: {$student['id']}\n";
    echo "   Nom: {$student['username']}\n";
    echo "   Email: {$student['email']}\n";
    echo "   Rôle: {$student['role']}\n";
    
    if (!$student['email']) {
        echo "   ⚠️  ATTENTION: Pas d'email configuré pour l'étudiant!\n";
    }
    echo "\n";
    
    // Étape 5: Test d'envoi d'email
    echo "📌 ÉTAPE 5: Test d'envoi d'email\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $emailService = new EmailService();
    $emailResult = $emailService->sendNewCommentNotification(
        $student['email'],
        $student['username'],
        $professor['username'],
        $document['titre'] ?: $document['nom_fichier'],
        $comment['text']
    );
    
    echo "📧 Résultat d'envoi:\n";
    echo "   Succès: " . ($emailResult['success'] ? '✅ OUI' : '❌ NON') . "\n";
    if ($emailResult['error']) {
        echo "   Erreur: {$emailResult['error']}\n";
    }
    if ($emailResult['debug']) {
        echo "   Détails:\n";
        foreach ($emailResult['debug'] as $detail) {
            echo "     - $detail\n";
        }
    }
    echo "\n";
    
    // Résumé
    echo "📊 RÉSUMÉ\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Commentaire: ID {$comment['id']}\n";
    echo "✅ Professeur: {$professor['username']} ({$professor['email']})\n";
    echo "✅ Étudiant: {$student['username']} ({$student['email']})\n";
    echo "✅ Document: " . ($document['titre'] ?: $document['nom_fichier']) . "\n";
    echo "📧 Email: " . ($emailResult['success'] ? '✅ ENVOYÉ' : '❌ ÉCHOUÉ') . "\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

?>
