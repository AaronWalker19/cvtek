<?php
/**
 * Migration: Ajouter la colonne année à la table users
 * Exécuter avec: php db/add-annee-column.php
 */

require_once __DIR__ . '/../db.php';

try {
    // Vérifier si la colonne existe déjà
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'année' AND TABLE_SCHEMA = DATABASE()";
    
    $result = $GLOBALS['connexion']->query($sql);
    
    if ($result->num_rows === 0) {
        // La colonne n'existe pas, on l'ajoute
        $alterSql = "ALTER TABLE users ADD COLUMN année INT DEFAULT 1 COMMENT 'Année scolaire (1 à 4)' AFTER parcour";
        $GLOBALS['connexion']->query($alterSql);
        
        // Ajouter un index
        $indexSql = "ALTER TABLE users ADD INDEX idx_année (année)";
        $GLOBALS['connexion']->query($indexSql);
        
        echo "✅ Colonne 'année' ajoutée avec succès à la table users\n";
    } else {
        echo "ℹ️ Colonne 'année' existe déjà\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>
