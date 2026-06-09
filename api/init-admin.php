<?php

/**
 * Script d'initialisation de l'utilisateur admin
 * Crée ou met à jour l'admin dans la base de données avec le mot de passe hashé
 * 
 * Utilisation: php init-admin.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Repository/AuthRepository.php';

// Récupérer les données admin depuis .env
$adminUsername = getenv('ADMIN_USERNAME') ?: 'admin';
$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@cvtek.fr';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';

echo "🔐 Initialisation de l'utilisateur admin...\n";
echo "   Username: $adminUsername\n";
echo "   Email: $adminEmail\n";

try {
    $auth = new AuthRepository();
    
    // Vérifier si l'admin existe déjà
    $existingAdmin = $auth->findByEmail($adminEmail);
    
    if ($existingAdmin) {
        echo "\n✅ Admin existe déjà (ID: {$existingAdmin['id']})\n";
        echo "   Mise à jour du mot de passe...\n";
        
        // Mettre à jour le mot de passe
        $passwordHash = hashPassword($adminPassword);
        $result = updateAdminPassword($adminEmail, $passwordHash);
        
        if ($result) {
            echo "   ✅ Mot de passe mis à jour avec succès!\n";
        } else {
            echo "   ❌ Erreur lors de la mise à jour du mot de passe\n";
            exit(1);
        }
    } else {
        echo "\n✅ Création du nouvel utilisateur admin...\n";
        
        // Créer l'admin avec mot de passe hashé
        $passwordHash = hashPassword($adminPassword);
        $adminId = $auth->create($adminUsername, $adminEmail, 'admin', $passwordHash, null);
        
        if ($adminId) {
            echo "   ✅ Admin créé avec succès (ID: $adminId)!\n";
        } else {
            echo "   ❌ Erreur lors de la création de l'admin\n";
            exit(1);
        }
    }
    
    echo "\n🎉 Admin prêt pour la connexion!\n";
    echo "   Email: $adminEmail\n";
    echo "   Mot de passe: $adminPassword (crypté en base de données)\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Met à jour le mot de passe de l'admin
 */
function updateAdminPassword(string $email, string $passwordHash): bool {
    try {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ? AND role = 'admin'");
        return $stmt->execute([$passwordHash, $email]);
    } catch (Exception $e) {
        echo "Erreur SQL: " . $e->getMessage() . "\n";
        return false;
    }
}
