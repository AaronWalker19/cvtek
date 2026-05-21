<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/AuthRepository.php';

/**
 * AuthController
 * 
 * Modèle d'authentification:
 * - Utilisateurs normaux: authentification externe, créés via email/username
 * - Admin: peut se connecter avec email + password
 */
class AuthController extends Controller
{
    private AuthRepository $auth;

    public function __construct()
    {
        $this->auth = new AuthRepository();
    }

    protected function processPostRequest(HttpRequest $request): ?array
    {
        $resource = $request->getResource();
        $id = $request->getId();
        
        // POST /api/auth/login (admin uniquement)
        if ($resource === 'auth' && $id === 'login') {
            return $this->handleLogin($request);
        }
        
        // POST /api/auth/external (authentification externe)
        if ($resource === 'auth' && $id === 'external') {
            return $this->handleExternalAuth($request);
        }
        
        // POST /api/auth/register (créer un utilisateur sans password)
        if ($resource === 'auth' && $id === 'register') {
            return $this->handleRegister($request);
        }
        
        // POST /api/auth/logout
        if ($resource === 'auth' && $id === 'logout') {
            return $this->handleLogout($request);
        }

        return ["error" => "Endpoint non trouvé"];
    }

    protected function processGetRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();
        
        // GET /api/auth/user
        if ($request->getResource() === 'auth' && $id === 'user') {
            return $this->handleGetUser($request);
        }

        // GET /api/auth/init-demo → Initialiser tous les utilisateurs démo
        if ($request->getResource() === 'auth' && $id === 'init-demo') {
            return $this->handleInitDemoUsers($request);
        }

        // GET /api/auth/ensure-demo → S'assurer que l'utilisateur démo existe
        if ($request->getResource() === 'auth' && $id === 'ensure-demo') {
            return $this->handleEnsureDemoUser($request);
        }

        // GET /api/auth/demo-token?user_id=X → Obtenir un token pour un utilisateur démo
        if ($request->getResource() === 'auth' && $id === 'demo-token') {
            return $this->handleDemoToken($request);
        }

        // GET /api/auth/{userId} → Récupérer un utilisateur par ID
        if ($request->getResource() === 'auth' && is_numeric($id)) {
            return $this->handleGetUserById($request, (int)$id);
        }

        return ["error" => "Endpoint non trouvé"];
    }

    /**
     * Gère le login admin (email + password)
     * En mode démo: accepte n'importe quel email/password
     */
    private function handleLogin(HttpRequest $request): ?array
    {
        $data = $request->getJson();

        if (empty($data['email'])) {
            return ['error' => 'Email requis', 'code' => 400];
        }

        $email = sanitizeString($data['email']);
        $password = $data['password'] ?? '';

        logAction("LOGIN_ATTEMPT", ['email' => $email]);

        $user = $this->auth->findByEmail($email);

        // En MODE DÉMO: accepter n'importe quel login pour les 3 utilisateurs de test
        if (!$user) {
            logAction("LOGIN_FAILED", ['email' => $email, 'reason' => 'user_not_found']);
            return ['error' => 'Utilisateur non trouvé', 'code' => 401];
        }

        // Générer token
        $token = generateToken($user['id'], $user['username'], $user['role']);

        logAction("LOGIN_SUCCESS", ['userId' => $user['id'], 'email' => $email]);

        return [
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'parcour' => $user['parcour'],
            ]
        ];
    }

    /**
     * Gère l'authentification externe
     * Crée ou récupère un utilisateur à partir de l'email
     */
    private function handleExternalAuth(HttpRequest $request): ?array
    {
        $data = $request->getJson();

        if (empty($data['email']) || empty($data['username'])) {
            return ['error' => 'Email et username requis', 'code' => 400];
        }

        $email = sanitizeString($data['email']);
        $username = sanitizeString($data['username']);
        $role = !empty($data['role']) && in_array($data['role'], ['student', 'professor', 'admin']) ? $data['role'] : 'student';
        $parcour = !empty($data['parcour']) ? sanitizeString($data['parcour']) : null;

        if (!isValidEmail($email)) {
            return ['error' => 'Email invalide', 'code' => 400];
        }

        logAction("EXTERNAL_AUTH", ['email' => $email, 'username' => $username]);

        // Créer ou récupérer l'utilisateur
        $user = $this->auth->findOrCreateByEmail($email, $username, $role, $parcour);

        if (!$user) {
            return ['error' => 'Erreur création utilisateur', 'code' => 500];
        }

        // Générer token
        $token = generateToken($user['id'], $user['username'], $user['role']);

        logAction("EXTERNAL_AUTH_SUCCESS", ['userId' => $user['id'], 'email' => $email]);

        return [
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'parcour' => $user['parcour'],
            ]
        ];
    }

    /**
     * Crée un utilisateur (sans password)
     */
    private function handleRegister(HttpRequest $request): ?array
    {
        $data = $request->getJson();

        if (empty($data['username']) || empty($data['email'])) {
            return ['error' => 'Username et email requis', 'code' => 400];
        }

        $username = sanitizeString($data['username']);
        $email = sanitizeString($data['email']);
        $role = !empty($data['role']) && in_array($data['role'], ['student', 'professor', 'admin']) ? $data['role'] : 'student';
        $parcour = !empty($data['parcour']) ? sanitizeString($data['parcour']) : null;

        if (!isValidEmail($email)) {
            return ['error' => 'Email invalide', 'code' => 400];
        }

        if (!isValidUsername($username)) {
            return ['error' => 'Username invalide (3-100 caractères, alphanumérique)', 'code' => 400];
        }

        // Vérifier si l'email existe
        if ($this->auth->emailExists($email)) {
            return ['error' => 'Cet email existe déjà', 'code' => 409];
        }

        logAction("REGISTER_ATTEMPT", ['email' => $email, 'role' => $role]);

        $userId = $this->auth->create($username, $email, $role, null, $parcour);

        logAction("REGISTER_SUCCESS", ['userId' => $userId, 'email' => $email]);

        return [
            'message' => 'Utilisateur créé',
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'parcour' => $parcour,
            ]
        ];
    }

    /**
     * Récupère les infos utilisateur
     */
    private function handleGetUser(HttpRequest $request): ?array
    {
        $user = getSessionUser();

        if (!$user) {
            return ['error' => 'Utilisateur non authentifié', 'code' => 401];
        }

        // Récupérer les infos fraîches en BD
        $userData = $this->auth->findById($user['id']);

        if (!$userData) {
            return ['error' => 'Utilisateur non trouvé', 'code' => 404];
        }

        return ['user' => $userData];
    }

    /**
     * Récupère les infos d'un utilisateur par son ID
     */
    private function handleGetUserById(HttpRequest $request, int $userId): ?array
    {
        logAction("GET_USER_BY_ID", ['userId' => $userId]);

        $userData = $this->auth->findById($userId);

        if (!$userData) {
            return ['error' => 'Utilisateur non trouvé', 'code' => 404];
        }

        return ['user' => $userData];
    }

    /**
     * S'assurer que l'utilisateur démo existe et migrer les données
     */
    private function handleEnsureDemoUser(HttpRequest $request): ?array
    {
        try {
            $conn = Database::getConnection();

            // Étape 1: Créer la table doc_version si elle n'existe pas
            $check = $conn->query("SHOW TABLES LIKE 'doc_version'");
            $tableExists = $check && $check->rowCount() > 0;

            if (!$tableExists) {
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS doc_version (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        id_doc INT NOT NULL COMMENT 'Référence au document parent',
                        version DECIMAL(3,1) NOT NULL COMMENT 'Numéro de version (1.0, 2.0, etc)',
                        url_fichier VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        
                        CONSTRAINT fk_doc_version FOREIGN KEY (id_doc) REFERENCES documents(id) ON DELETE CASCADE,
                        
                        INDEX idx_id_doc (id_doc),
                        INDEX idx_version (version),
                        INDEX idx_created_at (created_at),
                        UNIQUE KEY uk_doc_version (id_doc, version)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Étape 2: Migrer les données existantes
            $missing = $conn->query("
                SELECT d.id, d.created_at
                FROM documents d
                WHERE NOT EXISTS (
                    SELECT 1 FROM doc_version dv WHERE dv.id_doc = d.id
                )
            ")->fetchAll(PDO::FETCH_ASSOC);

            $migratedCount = 0;
            foreach ($missing as $doc) {
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO doc_version (id_doc, version, url_fichier, created_at)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $doc['id'],
                        1.0,
                        '/~valin6/cvtek/uploads/placeholder.pdf',
                        $doc['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $migratedCount++;
                } catch (Exception $e) {
                    error_log("Migration error for doc " . $doc['id'] . ": " . $e->getMessage());
                }
            }

            // Étape 3: Chercher l'utilisateur mael
            $mael = Database::fetchOne(
                "SELECT id, username, email FROM users WHERE username = ?",
                ['mael']
            );

            if ($mael) {
                // L'utilisateur existe
                return [
                    'success' => true,
                    'message' => 'Utilisateur mael trouvé',
                    'user' => [
                        'id' => (int)$mael['id'],
                        'username' => $mael['username'],
                        'email' => $mael['email']
                    ],
                    'migration' => [
                        'table_status' => $tableExists ? 'exists' : 'created',
                        'documents_migrated' => $migratedCount
                    ]
                ];
            } else {
                // Créer l'utilisateur mael
                $passwordHash = password_hash('mael123', PASSWORD_BCRYPT);

                Database::execute(
                    "INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())",
                    ['mael', 'mael@mael.fr', $passwordHash, 'student']
                );

                $newUser = Database::fetchOne(
                    "SELECT id, username, email FROM users WHERE username = ?",
                    ['mael']
                );

                return [
                    'success' => true,
                    'message' => 'Utilisateur mael créé',
                    'user' => [
                        'id' => (int)$newUser['id'],
                        'username' => $newUser['username'],
                        'email' => $newUser['email']
                    ],
                    'migration' => [
                        'table_status' => $tableExists ? 'exists' : 'created',
                        'documents_migrated' => $migratedCount
                    ]
                ];
            }

        } catch (Exception $e) {
            logAction("ENSURE_DEMO_ERROR", ['error' => $e->getMessage()]);

            return [
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Initialise tous les utilisateurs démo (mael, professor, admin)
     * GET /api/auth/init-demo
     */
    private function handleInitDemoUsers(HttpRequest $request): ?array
    {
        try {
            $conn = Database::getConnection();
            $results = [];

            // Définir les utilisateurs démo avec leurs IDs
            $demoUsers = [
                [
                    'id' => 16,
                    'username' => 'mael',
                    'email' => 'mael@mael.fr',
                    'role' => 'student',
                    'password' => 'mael123'
                ],
                [
                    'id' => 17,
                    'username' => 'professor',
                    'email' => 'professor@cvtek.fr',
                    'role' => 'professor',
                    'password' => 'professor123'
                ],
                [
                    'id' => 18,
                    'username' => 'admin',
                    'email' => 'admin@cvtek.fr',
                    'role' => 'admin',
                    'password' => 'admin123'
                ]
            ];

            // Créer les utilisateurs
            foreach ($demoUsers as $user) {
                $existing = Database::fetchOne(
                    "SELECT id FROM users WHERE username = ?",
                    [$user['username']]
                );

                if ($existing) {
                    $results[] = [
                        'username' => $user['username'],
                        'status' => 'exists',
                        'id' => (int)$existing['id'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                    logAction("DEMO_USER_EXISTS", ['username' => $user['username'], 'id' => $existing['id']]);
                } else {
                    try {
                        $passwordHash = password_hash($user['password'], PASSWORD_BCRYPT);
                        
                        // Essayer d'insérer avec l'ID spécifique
                        $stmt = $conn->prepare(
                            "INSERT INTO users (id, username, email, password_hash, role, created_at) 
                             VALUES (?, ?, ?, ?, ?, NOW())"
                        );
                        $stmt->execute([
                            $user['id'],
                            $user['username'],
                            $user['email'],
                            $passwordHash,
                            $user['role']
                        ]);

                        $results[] = [
                            'username' => $user['username'],
                            'status' => 'created',
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'role' => $user['role']
                        ];
                        logAction("DEMO_USER_CREATED", ['username' => $user['username'], 'id' => $user['id']]);
                    } catch (PDOException $e) {
                        // Si l'ID existe mais le username est différent, créer avec auto-increment
                        if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'id') !== false) {
                            $passwordHash = password_hash($user['password'], PASSWORD_BCRYPT);
                            $stmt = $conn->prepare(
                                "INSERT INTO users (username, email, password_hash, role, created_at) 
                                 VALUES (?, ?, ?, ?, NOW())"
                            );
                            $stmt->execute([
                                $user['username'],
                                $user['email'],
                                $passwordHash,
                                $user['role']
                            ]);

                            $newId = (int)$conn->lastInsertId();
                            $results[] = [
                                'username' => $user['username'],
                                'status' => 'created_with_auto_id',
                                'id' => $newId,
                                'email' => $user['email'],
                                'role' => $user['role'],
                                'note' => "ID spécifique {$user['id']} n'a pas pu être utilisé"
                            ];
                            logAction("DEMO_USER_CREATED_AUTO_ID", ['username' => $user['username'], 'id' => $newId]);
                        } else {
                            throw $e;
                        }
                    }
                }
            }

            logAction("DEMO_USERS_INIT", ['count' => count($results)]);

            return [
                'success' => true,
                'message' => 'Utilisateurs démo vérifiés/créés',
                'users' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            error_log("❌ Erreur init-demo-users: " . $e->getMessage());
            return [
                'error' => 'Erreur lors de l\'initialisation des utilisateurs démo: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Génère un token démo pour un utilisateur spécifié
     * GET /api/auth/demo-token?user_id=X
     */
    private function handleDemoToken(HttpRequest $request): ?array
    {
        $userId = $request->getParam('user_id');

        if (!$userId) {
            return ['error' => 'user_id requis', 'code' => 400];
        }

        $userId = (int)$userId;

        logAction("DEMO_TOKEN_REQUEST", ['userId' => $userId]);

        // Récupérer l'utilisateur
        $userData = $this->auth->findById($userId);

        if (!$userData) {
            return ['error' => 'Utilisateur non trouvé', 'code' => 404];
        }

        // Générer le token JWT
        $token = generateToken($userData['id'], $userData['username'], $userData['role']);

        logAction("DEMO_TOKEN_GENERATED", ['userId' => $userId]);

        return [
            'token' => $token,
            'user' => [
                'id' => (int)$userData['id'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'role' => $userData['role'],
                'parcour' => $userData['parcour'],
            ]
        ];
    }
}

