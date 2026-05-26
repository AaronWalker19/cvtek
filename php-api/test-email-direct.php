<?php

/**
 * test-email-direct.php
 * Test direct du service email avec logs détaillés
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ajouter tous les logs à la sortie
ini_set('log_errors', 1);
ini_set('error_log', 'php://stdout');

echo "=== TEST DIRECT EMAIL SERVICE ===\n\n";

try {
    echo "1️⃣ Chargement EmailService...\n";
    require_once __DIR__ . '/Service/EmailService.php';
    echo "   ✅ EmailService chargé\n\n";
    
    echo "2️⃣ Instantiation du service...\n";
    $emailService = new EmailService();
    echo "   ✅ Service instantié\n\n";
    
    echo "3️⃣ Test d'envoi simple...\n";
    $result = $emailService->testConnection();
    echo "   Résultat: " . ($result ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n\n";
    
    echo "4️⃣ Test de notification document...\n";
    $docResult = $emailService->sendNewDocumentNotification(
        'etudiant@example.com',
        'Jean Dupont',
        'Rapport Final',
        ['prof1@example.com', 'prof2@example.com']
    );
    echo "   Résultat: " . ($docResult ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n\n";
    
    echo "5️⃣ Test de notification commentaire...\n";
    $comResult = $emailService->sendNewCommentNotification(
        'etudiant@example.com',
        'Jean Dupont',
        'Prof Dupont',
        'Rapport Final',
        'Très bon travail!'
    );
    echo "   Résultat: " . ($comResult ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n\n";
    
    echo "=== ✅ TESTS TERMINÉS ===\n";
    
} catch (Throwable $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
    echo "\nTrace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n📝 Les logs d'email devraient apparaître ci-dessus si les envois ont été tentés.\n";
