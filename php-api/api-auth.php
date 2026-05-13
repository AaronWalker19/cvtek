<?php
/**
 * api-auth.php - Endpoint direct pour l'authentification
 * 
 * URLs:
 * POST https://mmi.unilim.fr/~valin6/cvtek/api/api-auth.php?action=login
 * POST https://mmi.unilim.fr/~valin6/cvtek/api/api-auth.php?action=register
 * GET  https://mmi.unilim.fr/~valin6/cvtek/api/api-auth.php?action=user
 * POST https://mmi.unilim.fr/~valin6/cvtek/api/api-auth.php?action=logout
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
setCorsHeaders();
handleCorsPreFlight();

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    switch ($action) {
        case 'login':
            handleLogin();
            break;
            
        case 'register':
            handleRegister();
            break;
            
        case 'user':
            handleGetUser();
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        default:
            jsonSuccess([
                'message' => 'Bienvenue sur l\'API Auth CVTEK',
                'actions' => [
                    'login' => 'POST /api-auth.php?action=login',
                    'register' => 'POST /api-auth.php?action=register',
                    'user' => 'GET /api-auth.php?action=user',
                    'logout' => 'POST /api-auth.php?action=logout',
                ]
            ]);
    }
    
} catch (Exception $e) {
    logAction("AUTH API ERROR", ['error' => $e->getMessage()]);
    jsonError($e->getMessage(), 500);
}

// ===============================================
// Handlers
// ===============================================

function handleLogin(): void {
    $data = getJsonBody();
    
    if (empty($data['email']) || empty($data['password'])) {
        jsonError('Email et password requis', 400);
    }
    
    $email = sanitizeString($data['email']);
    $password = $data['password'];
    
    logAction("LOGIN ATTEMPT", ['email' => $email]);
    
    $user = Database::fetchOne(
        "SELECT id, username, email, password_hash, role FROM users WHERE email = ?",
        [$email]
    );
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonError('Email ou mot de passe incorrect', 401);
    }
    
    // Générer token
    $token = generateToken($user['id'], $user['username'], $user['role']);
    
    // Sauvegarder le token dans la BD (optionnel)
    // Database::execute("UPDATE users SET last_token = ? WHERE id = ?", [$token, $user['id']]);
    
    logAction("LOGIN SUCCESS", ['userId' => $user['id'], 'email' => $email]);
    
    jsonSuccess([
        'token' => $token,
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ]
    ]);
}

function handleRegister(): void {
    $data = getJsonBody();
    
    $required = ['username', 'email', 'password', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonError("Champ requis: $field", 400);
        }
    }
    
    $username = sanitizeString($data['username']);
    $email = sanitizeString($data['email']);
    $password = $data['password'];
    $role = in_array($data['role'], ['student', 'professor', 'admin']) ? $data['role'] : 'student';
    
    // Vérifier si l'email existe
    $existing = Database::fetchOne(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    
    if ($existing) {
        jsonError('Cet email existe déjà', 409);
    }
    
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    logAction("REGISTER ATTEMPT", ['email' => $email, 'role' => $role]);
    
    Database::execute(
        "INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())",
        [$username, $email, $passwordHash, $role]
    );
    
    logAction("REGISTER SUCCESS", ['email' => $email]);
    
    jsonSuccess([
        'message' => 'Inscription réussie',
        'email' => $email,
        'role' => $role
    ], 201);
}

function handleGetUser(): void {
    // ✅ DÉVELOPPEMENT: authentification optionnelle
    // Chercher l'utilisateur sans obligation d'authentification
    $user = getSessionUser();
    
    if ($user) {
        jsonSuccess([
            'id' => (int)$user['userId'],
            'username' => $user['username'],
            'role' => $user['role'],
        ]);
    } else {
        // En dev, retourner l'utilisateur guest
        jsonSuccess([
            'id' => 0,
            'username' => 'guest',
            'role' => 'guest',
            'authenticated' => false,
            'message' => 'Non authentifié'
        ]);
    }
}

function handleLogout(): void {
    // En JWT, pas besoin de faire grand chose
    // Le frontend supprime simplement le token
    logAction("LOGOUT", []);
    
    jsonSuccess(['message' => 'Déconnecté avec succès']);
}

?>
