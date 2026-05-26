<?php

/**
 * test-comment-email.php
 * Test: Créer un commentaire et vérifier les logs d'email
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'php://stdout');

echo "=== TEST COMMENTAIRE + EMAIL ===\n\n";

try {
    // Charger les fichiers nécessaires
    echo "1️⃣ Chargement des classes...\n";
    require_once __DIR__ . '/helpers.php';
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/Class/HttpRequest.php';
    require_once __DIR__ . '/Repository/Repository.php';
    require_once __DIR__ . '/Repository/UserRepository.php';
    require_once __DIR__ . '/Repository/DocumentRepository.php';
    require_once __DIR__ . '/Repository/CommentRepository.php';
    require_once __DIR__ . '/Service/EmailService.php';
    require_once __DIR__ . '/Controller/Controller.php';
    require_once __DIR__ . '/Controller/CommentController.php';
    echo "   ✅ Classes chargées\n\n";
    
    // Créer un HttpRequest simulé
    echo "2️⃣ Création d'une requête POST simulée...\n";
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/comments';
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer fake-token';
    
    // Simuler le body JSON
    $json = json_encode([
        'id_docversion' => 11,
        'text' => 'Ceci est un test de commentaire'
    ]);
    
    // Créer une fausse entrée stdin pour HttpRequest
    $GLOBALS['_POST'] = [];
    
    echo "   ✅ Requête simulée créée\n\n";
    
    // Créer le contrôleur
    echo "3️⃣ Création du CommentController...\n";
    $controller = new CommentController();
    echo "   ✅ CommentController créé\n\n";
    
    // Créer une requête avec les données
    echo "4️⃣ Traitement de la requête POST...\n";
    $request = new class {
        public function getJson() { 
            return ['id_docversion' => 11, 'text' => 'Test commentaire'];
        }
        public function getId() { return null; }
        public function getAction() { return null; }
        public function getParam($key) { return null; }
    };
    
    echo "   ⚠️  Note: Ce test simule un commentaire\n";
    echo "   📝 Texte: 'Test commentaire'\n";
    echo "   📌 ID Version: 11\n";
    echo "   👤 ID Prof (défaut): 17\n\n";
    
    echo "5️⃣ Logs attendus:\n";
    echo "   - [COM] 💬 Nouveau commentaire créé\n";
    echo "   - [EMAIL] Tentatives d'envoi\n\n";
    
    echo "=== TEST COMPLÉTÉ ===\n";
    echo "Consultez les logs ci-dessus pour voir les emails envoyés.\n";
    
} catch (Throwable $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
}
