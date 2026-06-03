<?php
/**
 * Script de test pour vérifier la suppression d'utilisateur
 */

require_once __DIR__ . '/php-api/config.php';
require_once __DIR__ . '/php-api/db.php';
require_once __DIR__ . '/php-api/Repository/UserRepository.php';

$userRepo = new UserRepository();

echo "=== TEST DELETE PROFESSOR ===\n\n";

// Test 1: Récupérer tous les utilisateurs
echo "1. All users:\n";
$allUsers = $userRepo->findAll();
var_dump($allUsers);
echo "\n";

// Test 2: Récupérer tous les professeurs
echo "2. All professors:\n";
$allProfs = $userRepo->findByRole('professor');
var_dump($allProfs);
echo "\n";

// Test 3: Récupérer l'utilisateur 47
echo "3. User 47 via findById:\n";
$user47 = $userRepo->findById(47);
var_dump($user47);
echo "\n";

// Test 4: Si on a des professeurs, essayer de les trouver par ID
if (count($allProfs) > 0) {
    echo "4. Testing first professor lookup:\n";
    $firstProf = $allProfs[0];
    echo "First prof from findByRole: " . json_encode($firstProf) . "\n";
    
    $foundByFind = $userRepo->findById((int)$firstProf['id']);
    echo "Same prof via findById: " . json_encode($foundByFind) . "\n\n";
}

// Test 5: Requête SQL brute
echo "5. Raw SQL test:\n";
$cnx = Database::getConnection();
$stmt = $cnx->prepare("SELECT id, username, email, role FROM users WHERE id = 47");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Direct SQL query for ID=47: " . json_encode($result) . "\n";

$stmt2 = $cnx->prepare("SELECT id, username, email, role FROM users");
$stmt2->execute();
$allRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "All rows in users table: " . json_encode($allRows) . "\n";
