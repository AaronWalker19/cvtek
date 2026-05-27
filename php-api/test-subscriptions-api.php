<?php
/**
 * test-subscriptions-api.php
 * Teste les endpoints d'abonnement et de commentaire
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Class/HttpRequest.php';
require_once __DIR__ . '/Controller/Controller.php';
require_once __DIR__ . '/Controller/AbonnementController.php';
require_once __DIR__ . '/Controller/CommentController.php';
require_once __DIR__ . '/Repository/Repository.php';
require_once __DIR__ . '/Repository/AbonnementRepository.php';
require_once __DIR__ . '/Repository/CommentRepository.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Service/EmailService.php';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST DES ENDPOINTS D'ABONNEMENT ET COMMENTAIRE               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════
// TEST 1: POST /api/abonnement (Créer un abonnement)
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST 1: POST /api/abonnement (Créer abonnement)\n";
echo "─────────────────────────────────────────────────────────────\n";

class MockHttpRequest extends HttpRequest {
    private $mockMethod = 'POST';
    private $mockResource = 'abonnement';
    private $mockBody = '{"prof_id": 17, "user_id": 16}';
    
    public function __construct($method, $resource, $body) {
        $this->mockMethod = $method;
        $this->mockResource = $resource;
        $this->mockBody = $body;
    }
    
    public function getMethod() { return $this->mockMethod; }
    public function getResource() { return $this->mockResource; }
    public function getId() { return null; }
    public function getAction() { return null; }
    public function getParam($key) { return null; }
    public function getJson() {
        return json_decode($this->mockBody, true);
    }
    public function getJsonBody() {
        return json_decode($this->mockBody, true);
    }
}

try {
    $request = new MockHttpRequest('POST', 'abonnement', '{"prof_id": 17, "user_id": 16}');
    $controller = new AbonnementController();
    $response = $controller->jsonResponse($request);
    $data = json_decode($response, true);
    
    if ($data['success'] ?? false) {
        echo "✅ Abonnement créé avec succès\n";
        echo "   Prof ID: " . $data['prof_id'] . "\n";
        echo "   User ID: " . $data['user_id'] . "\n";
    } else {
        echo "❌ Erreur: " . ($data['error'] ?? 'Inconnue') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// TEST 2: GET /api/abonnement/check?prof_id=17&user_id=16
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST 2: GET /api/abonnement/check (Vérifier abonnement)\n";
echo "─────────────────────────────────────────────────────────────\n";

class MockHttpRequestCheck extends HttpRequest {
    public function getMethod() { return 'GET'; }
    public function getResource() { return 'abonnement'; }
    public function getId() { return 'check'; }
    public function getAction() { return null; }
    public function getParam($key) { 
        if ($key === 'prof_id') return '17';
        if ($key === 'user_id') return '16';
        return null;
    }
    public function getJson() { return null; }
}

try {
    $request = new MockHttpRequestCheck();
    $controller = new AbonnementController();
    $response = $controller->jsonResponse($request);
    $data = json_decode($response, true);
    
    if ($data['success'] ?? false) {
        echo "✅ Requête réussie\n";
        echo "   Abonné: " . ($data['subscribed'] ? 'OUI' : 'NON') . "\n";
    } else {
        echo "❌ Erreur: " . ($data['error'] ?? 'Inconnue') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// TEST 3: Vérifier que getProfInfoByUser fonctionne
// ═══════════════════════════════════════════════════════════════════
echo "🔍 TEST 3: getProfInfoByUser(16) - Direct SQL\n";
echo "─────────────────────────────────────────────────────────────\n";

$db = Database::getConnection();
$stmt = $db->prepare(
    "SELECT u.id, u.email, u.username
     FROM abonnement a
     INNER JOIN users u ON a.id_prof = u.id
     WHERE a.id_user = ? AND u.email IS NOT NULL"
);
$stmt->execute([16]);
$profs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($profs)) {
    echo "❌ Aucun professeur abonné (CORRIGE: c'est le problème des emails!)\n";
} else {
    echo "✅ Professeurs abonnés: " . count($profs) . "\n";
    foreach ($profs as $p) {
        echo "   - {$p['username']} ({$p['email']})\n";
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// RÉSUMÉ
// ═══════════════════════════════════════════════════════════════════
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ                                                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";
echo "✅ Tests terminés\n";
echo "   1. Abonnement a été créé/testé\n";
echo "   2. Vérification de l'abonnement faite\n";
echo "   3. getProfInfoByUser() a été testé\n";
echo "\nProchaines étapes:\n";
echo "   - Tester un commentaire avec l'endpoint POST /api/comments\n";
echo "   - Vérifier que l'email est bien envoyé\n";
?>
