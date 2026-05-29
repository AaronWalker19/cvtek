<?php
/**
 * Script de correction : Mettez à jour le rôle de l'utilisateur 17 à 'professor'
 * À exécuter une seule fois pour corriger le problème
 */

require_once __DIR__ . '/php-api/db.php';

try {
    // Connexion à la base de données
    $pdo = getDatabaseConnection();
    
    // Vérifier le rôle actuel
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = 17");
    $stmt->execute();
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== AVANT LA CORRECTION ===\n";
    echo "Utilisateur 17: " . json_encode($currentUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Mettre à jour le rôle à 'professor'
    $stmt = $pdo->prepare("UPDATE users SET role = 'professor' WHERE id = 17");
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ Rôle mis à jour avec succès!\n\n";
    } else {
        echo "❌ Erreur lors de la mise à jour\n";
        exit(1);
    }
    
    // Vérifier après la correction
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = 17");
    $stmt->execute();
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== APRÈS LA CORRECTION ===\n";
    echo "Utilisateur 17: " . json_encode($updatedUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Vérifier tous les professeurs
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE role = 'professor' ORDER BY id");
    $stmt->execute();
    $professors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== TOUS LES PROFESSEURS ===\n";
    echo "Nombre de professeurs: " . count($professors) . "\n";
    foreach ($professors as $prof) {
        echo "- ID {$prof['id']}: {$prof['username']} ({$prof['email']}) - Role: {$prof['role']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>
