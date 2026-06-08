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

        // POST /api/auth/callback (callback Unilim SSO)
        if ($resource === 'auth' && $id === 'callback') {
            return $this->handleCallback($request);
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

        // GET /api/auth/unilim-authorize → Obtenir l'URL de redirection Unilim
        if ($request->getResource() === 'auth' && $id === 'unilim-authorize') {
            return $this->handleUnilimAuthorize($request);
        }

        // GET /api/auth/{userId} → Récupérer un utilisateur par ID
        if ($request->getResource() === 'auth' && is_numeric($id)) {
            return $this->handleGetUserById($request, (int)$id);
        }

        return ["error" => "Endpoint non trouvé"];
    }

    /**
     * Gère le login admin (email + password)
     * Crée automatiquement l'admin à la première tentative de connexion
     */
    private function handleLogin(HttpRequest $request): ?array
    {
        $data = $request->getJson();

        if (empty($data['email'])) {
            return ['error' => 'Email requis', 'code' => 400];
        }

        $email = sanitizeString($data['email']);
        $password = $data['password'] ?? '';

        if (empty($password)) {
            return ['error' => 'Mot de passe requis', 'code' => 400];
        }

        logAction("LOGIN_ATTEMPT", ['email' => $email]);

        // Vérifier si c'est l'email admin
        $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@cvtek.fr';
        
        if ($email === $adminEmail) {
            // Vérifier ou créer l'admin
            $user = $this->auth->findByEmail($email);
            
            if (!$user) {
                // Créer l'admin avec le mot de passe depuis .env
                $adminUsername = getenv('ADMIN_USERNAME') ?: 'admin';
                $adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';
                
                logAction("ADMIN_CREATE", ['email' => $email, 'username' => $adminUsername]);
                
                $passwordHash = hashPassword($adminPassword);
                $adminId = $this->auth->create($adminUsername, $email, 'admin', $passwordHash, null);
                
                if (!$adminId) {
                    logAction("LOGIN_FAILED", ['email' => $email, 'reason' => 'admin_creation_failed']);
                    return ['error' => 'Erreur création admin', 'code' => 500];
                }
                
                $user = $this->auth->findById($adminId);
            }
            
            // Vérifier le mot de passe
            if (!$user['password_hash'] || !verifyPassword($password, $user['password_hash'])) {
                logAction("LOGIN_FAILED", ['email' => $email, 'reason' => 'invalid_password']);
                return ['error' => 'Mot de passe incorrect', 'code' => 401];
            }
        } else {
            // Utilisateur normal: authentification externe
            $user = $this->auth->findByEmail($email);
            
            if (!$user) {
                logAction("LOGIN_FAILED", ['email' => $email, 'reason' => 'user_not_found']);
                return ['error' => 'Utilisateur non trouvé', 'code' => 401];
            }
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
                        '/cvtek/uploads/placeholder.pdf',
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

    /**
     * Déconnecte l'utilisateur
     * POST /api/auth/logout
     */
    private function handleLogout(HttpRequest $request): ?array
    {
        try {
            $user = getSessionUser();
            
            if ($user) {
                logAction("LOGOUT_ATTEMPT", ['userId' => $user['id'], 'username' => $user['username']]);
            }

            // Détruire la session PHP
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }

            logAction("LOGOUT_SUCCESS", ['userId' => $user['id'] ?? null]);

            return [
                'success' => true,
                'message' => 'Déconnecté avec succès'
            ];
        } catch (Exception $e) {
            error_log("❌ Erreur logout: " . $e->getMessage());
            return [
                'error' => 'Erreur lors de la déconnexion: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Obtient l'URL de redirection vers Unilim
     * GET /api/auth/unilim-authorize
     */
    private function handleUnilimAuthorize(HttpRequest $request): ?array
    {
        // Générer un state aléatoire pour la sécurité (CSRF protection)
        $state = bin2hex(random_bytes(32));
        
        // Sauvegarder le state en session pour vérification ultérieure
        $_SESSION['unilim_state'] = $state;
        $_SESSION['unilim_state_created'] = time();
        
        // Construire l'URL de redirection Unilim
        $authorizeUrl = UNILIM_AUTHORIZE_URL . '?' . http_build_query([
            'client_id' => UNILIM_CLIENT_ID,
            'response_type' => 'code',
            'scope' => UNILIM_SCOPE,
            'state' => $state,
            'redirect_uri' => UNILIM_REDIRECT_URI,
        ]);
        
        logAction("UNILIM_AUTHORIZE_URL_GENERATED", ['state' => substr($state, 0, 8) . '...']);
        
        return [
            'authorize_url' => $authorizeUrl,
            'state' => $state
        ];
    }

    /**
     * Traite le callback Unilim
     * POST /api/auth/callback
     * 
     * Body: { code: "...", state: "..." }
     */
    private function handleCallback(HttpRequest $request): ?array
    {
        try {
            $data = $request->getJson();
            
            if (empty($data['code'])) {
                return ['error' => 'Code Unilim manquant', 'code' => 400];
            }
            
            if (empty($data['state'])) {
                return ['error' => 'State Unilim manquant', 'code' => 400];
            }
            
            $code = sanitizeString($data['code']);
            $state = sanitizeString($data['state']);
            
            // Vérifier le state (CSRF protection)
            if (empty($_SESSION['unilim_state']) || $_SESSION['unilim_state'] !== $state) {
                logAction("UNILIM_STATE_MISMATCH", ['code' => substr($code, 0, 8) . '...']);
                return ['error' => 'State invalide ou expiré', 'code' => 401];
            }
            
            // Vérifier que le state n'est pas trop ancien (5 minutes)
            $stateAge = time() - ($_SESSION['unilim_state_created'] ?? 0);
            if ($stateAge > 300) {
                logAction("UNILIM_STATE_EXPIRED", ['age' => $stateAge]);
                return ['error' => 'State expiré', 'code' => 401];
            }
            
            // Nettoyer le state
            unset($_SESSION['unilim_state']);
            unset($_SESSION['unilim_state_created']);
            
            logAction("UNILIM_CALLBACK_PROCESSING", ['code' => substr($code, 0, 8) . '...']);
            
            // Effectuer l'appel POST au serveur token Unilim
            $tokenResponse = $this->exchangeCodeForToken($code);
            
            if (!$tokenResponse) {
                return ['error' => 'Erreur lors de l\'échange du code', 'code' => 500];
            }
            
            // Vérifier que nous avons un id_token
            if (empty($tokenResponse['id_token'])) {
                logAction("UNILIM_NO_ID_TOKEN", []);
                return ['error' => 'Pas de token d\'identité reçu', 'code' => 500];
            }
            
            // Décoder le JWT
            $payload = $this->decodeJWT($tokenResponse['id_token']);
            
            if (!$payload) {
                logAction("UNILIM_JWT_DECODE_FAILED", []);
                return ['error' => 'Erreur décodage JWT', 'code' => 500];
            }
            
            logAction("UNILIM_JWT_DECODED", [
                'sub' => $payload['sub'] ?? '',
                'email' => $payload['email'] ?? '',
                'name' => $payload['name'] ?? ''
            ]);
            
            // Extraire les informations de l'utilisateur du JWT
            $email = $payload['email'] ?? null;
            $username = $payload['preferred_username'] ?? $payload['name'] ?? $payload['sub'] ?? null;
            
            if (!$email || !$username) {
                logAction("UNILIM_MISSING_USER_INFO", ['payload' => $payload]);
                return ['error' => 'Email ou username manquant dans le token', 'code' => 400];
            }
            
            $email = sanitizeString($email);
            $username = sanitizeString($username);
            
            // Déterminer le rôle (utiliser le payload Unilim si disponible)
            $role = $payload['role'] ?? 'student';
            if (!in_array($role, ['student', 'professor', 'admin'])) {
                $role = 'student';
            }
            
            // ============================================================
            // VÉRIFICATION DU DOMAINE D'EMAIL - CONTRÔLE D'ACCÈS
            // ============================================================
            $accessDenied = $this->checkEmailAccess($email);
            if ($accessDenied !== true) {
                // Accès refusé - retourner un code d'erreur spécifique
                logAction("UNILIM_ACCESS_DENIED", [
                    'email' => $email,
                    'reason' => $accessDenied
                ]);
                
                return [
                    'error' => 'Accès refusé. Votre adresse email n\'est pas autorisée pour accéder à cette application.',
                    'reason' => $accessDenied,
                    'access_denied' => true,
                    'code' => 403
                ];
            }
            
            // Créer ou récupérer l'utilisateur
            $user = $this->auth->findOrCreateByEmail($email, $username, $role);
            
            if (!$user) {
                logAction("UNILIM_USER_CREATION_FAILED", ['email' => $email]);
                return ['error' => 'Erreur création/récupération utilisateur', 'code' => 500];
            }
            
            // Générer le token JWT de l'app
            $token = generateToken($user['id'], $user['username'], $user['role']);
            
            logAction("UNILIM_LOGIN_SUCCESS", [
                'userId' => $user['id'],
                'email' => $email,
                'username' => $username
            ]);
            
            return [
                'token' => $token,
                'user' => [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'parcour' => $user['parcour'],
                ],
                'unilim_payload' => $payload
            ];
            
        } catch (Exception $e) {
            error_log("❌ Erreur Unilim callback: " . $e->getMessage());
            logAction("UNILIM_CALLBACK_ERROR", ['error' => $e->getMessage()]);
            return [
                'error' => 'Erreur traitement callback Unilim: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Échange le code d'autorisation pour un token d'identité
     * Effectue la requête POST à https://cas.unilim.fr/token
     */
    private function exchangeCodeForToken(string $code): ?array
    {
        try {
            $postData = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => UNILIM_CLIENT_ID,
                'client_secret' => UNILIM_CLIENT_SECRET,
                'redirect_uri' => UNILIM_REDIRECT_URI,
            ];
            
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Accept: application/json'
                    ],
                    'content' => http_build_query($postData),
                    'timeout' => 10
                ]
            ];
            
            $context = stream_context_create($options);
            $response = file_get_contents(UNILIM_TOKEN_URL, false, $context);
            
            if ($response === false) {
                error_log("❌ Erreur lors de l'appel à " . UNILIM_TOKEN_URL);
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("❌ Erreur décodage JSON token response: " . json_last_error_msg());
                return null;
            }
            
            if (isset($data['error'])) {
                error_log("❌ Erreur Unilim token: " . ($data['error_description'] ?? $data['error']));
                return null;
            }
            
            logAction("UNILIM_TOKEN_EXCHANGE_SUCCESS", []);
            
            return $data;
            
        } catch (Exception $e) {
            error_log("❌ Exception lors de l'échange de code: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Décode un JWT (ID Token de Unilim)
     * 
     * Note: Cette fonction ne valide PAS la signature du JWT
     * En production, vous devriez valider la signature avec la clé publique de Unilim
     * 
     * Pour l'instant, on fait confiance au HTTPS pour la sécurité du transport
     */
    private function decodeJWT(string $token): ?array
    {
        try {
            // Diviser le token en ses 3 parties: header.payload.signature
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                error_log("❌ Token JWT invalide: mauvais format");
                return null;
            }
            
            // Décoder le payload (partie 2, index 1)
            $payload = $parts[1];
            
            // Ajouter le padding si nécessaire pour base64
            $padding = 4 - (strlen($payload) % 4);
            if ($padding !== 4) {
                $payload .= str_repeat('=', $padding);
            }
            
            // Décoder de base64
            $decoded = base64_decode($payload, true);
            
            if ($decoded === false) {
                error_log("❌ Erreur décodage base64 du payload JWT");
                return null;
            }
            
            // Parser le JSON
            $payloadData = json_decode($decoded, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("❌ Erreur parsing JSON du payload JWT: " . json_last_error_msg());
                return null;
            }
            
            // TODO: Valider la signature du JWT avec la clé publique de Unilim
            // Pour l'instant, on fait simplement confiance au HTTPS
            
            return $payloadData;
            
        } catch (Exception $e) {
            error_log("❌ Exception décodage JWT: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie l'accès d'un utilisateur basé sur le domaine d'email
     * 
     * Règles:
     * - @etu.unilim.fr: Accès autorisé (création automatique)
     * - @unilim.fr: Accès seulement si l'utilisateur existe déjà en base
     * - Autres domaines: Accès refusé
     * 
     * @param string $email L'email de l'utilisateur
     * @return bool|string True si accès autorisé, sinon un message d'erreur
     */
    private function checkEmailAccess(string $email): bool|string
    {
        // Extraire le domaine de l'email
        $emailParts = explode('@', $email);
        if (count($emailParts) !== 2) {
            return 'Format d\'email invalide';
        }
        
        $domain = strtolower($emailParts[1]);
        $username = $emailParts[0];
        
        // RÈGLE 1: Les emails @etu.unilim.fr sont toujours autorisés
        if ($domain === 'etu.unilim.fr') {
            logAction("UNILIM_EMAIL_CHECK", [
                'email' => $email,
                'domain' => $domain,
                'result' => 'ALLOWED_ETU'
            ]);
            return true;
        }
        
        // RÈGLE 2: Les emails @unilim.fr doivent exister en base
        if ($domain === 'unilim.fr') {
            $user = $this->auth->findByEmail($email);
            
            if ($user) {
                logAction("UNILIM_EMAIL_CHECK", [
                    'email' => $email,
                    'domain' => $domain,
                    'result' => 'ALLOWED_UNILIM_EXISTS'
                ]);
                return true;
            } else {
                logAction("UNILIM_EMAIL_CHECK", [
                    'email' => $email,
                    'domain' => $domain,
                    'result' => 'DENIED_UNILIM_NOT_EXISTS'
                ]);
                return 'Professeur non enregistré. Veuillez contacter l\'administrateur.';
            }
        }
        
        // RÈGLE 3: Tous les autres domaines sont refusés
        logAction("UNILIM_EMAIL_CHECK", [
            'email' => $email,
            'domain' => $domain,
            'result' => 'DENIED_INVALID_DOMAIN'
        ]);
        return 'Domaine d\'email non autorisé. Seuls les emails @etu.unilim.fr et @unilim.fr sont acceptés.';
    }
}

