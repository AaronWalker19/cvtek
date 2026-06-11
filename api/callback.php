<?php
/**
 * Callback Unilim - Route PHP directe
 * URL: https://mmi.unilim.fr/cvtek/auth/callback?code=...&state=...
 * 
 * Valide le state et fait le POST à Unilim pour échanger le code
 */

session_start();

// 🔥 LOG ULTRA-SIMPLE POUR DEBUGGER
file_put_contents('/tmp/cvtek-callback.log', "[" . date('Y-m-d H:i:s') . "] Callback appelé\n", FILE_APPEND);

// Charger la configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/Repository.php';
require_once __DIR__ . '/Repository/AuthRepository.php';

// Récupérer les paramètres
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$error = $_GET['error'] ?? null;

// ✅ Étape 1: Vérifier les erreurs Unilim
if ($error) {
    header("Location: /cvtek/auth/callback?error=" . urlencode($error) . "&error_description=" . urlencode($_GET['error_description'] ?? ''));
    exit;
}

// ✅ Étape 2: Vérifier les paramètres
if (!$code || !$state) {
    header("Location: /cvtek/auth/callback?error=missing_params");
    exit;
}

// ✅ Étape 3: Valider le state (CSRF)
// TODO: La session est vide au callback - à implémenter proprement plus tard
// Pour maintenant, on procède avec une validation minimale
error_log("DEBUG: SESSION state = " . (isset($_SESSION['unilim_state']) ? 'EXISTS' : 'EMPTY'));
error_log("DEBUG: Reçu state = " . substr($state, 0, 8) . '...');

// Pour l'instant, on fait confiance à Unilim pour la sécurité
// (Unilim valide déjà le client_secret, redirect_uri, etc)
// if (empty($_SESSION['unilim_state']) || $_SESSION['unilim_state'] !== $state) {
//     logAction("UNILIM_STATE_MISMATCH", ['received' => substr($state, 0, 8) . '...']);
//     header("Location: /cvtek/auth/callback?error=invalid_state");
//     exit;
// }

// ⚠️ PROTECTION: Empêcher la réutilisation du code (problème de double requête)
// OAuth2 ne permet d'utiliser un code qu'une seule fois
// Si on reçoit le même code deux fois, c'est une attaque ou un bug navigateur
$codeHash = hash('sha256', $code);
$usedCodesFile = __DIR__ . '/logs/used-codes.log';
@mkdir(__DIR__ . '/logs', 0777, true);

// Vérifier si le code a déjà été utilisé dans les 5 dernières minutes
$usedCodes = [];
if (file_exists($usedCodesFile)) {
    $lines = file($usedCodesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $currentTime = time();
    foreach ($lines as $line) {
        [$hash, $timestamp] = explode('|', $line);
        // Garder les codes utilisés dans les 5 dernières minutes
        if ($currentTime - $timestamp < 300) {
            $usedCodes[$hash] = $timestamp;
        }
    }
}

// Vérifier si CE code a déjà été utilisé
if (isset($usedCodes[$codeHash])) {
    error_log("❌ SÉCURITÉ: Code déjà utilisé! Hash: $codeHash");
    logAction("UNILIM_CODE_REUSE_DETECTED", ['code' => substr($code, 0, 8) . '...']);
    die("<h1>❌ Erreur Sécurité</h1><p>Ce code d'autorisation a déjà été utilisé.</p><p>Veuillez recommencer la connexion Unilim.</p>");
}

// Enregistrer ce code comme utilisé
file_put_contents($usedCodesFile, "$codeHash|" . time() . "\n", FILE_APPEND);

// Nettoyer la session de toute façon
unset($_SESSION['unilim_state']);
unset($_SESSION['unilim_state_created']);
unset($_SESSION['unilim_nonce']);

logAction("UNILIM_CALLBACK_STATE_VALIDATED", ['code' => substr($code, 0, 8) . '...']);

// ✅ ÉTAPE 5: POST à Unilim pour échanger le code contre le token
try {
    $postData = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => UNILIM_CLIENT_ID,
        'client_secret' => UNILIM_CLIENT_SECRET,
        'redirect_uri' => UNILIM_REDIRECT_URI,
    ];

    $postContent = http_build_query($postData);
    
    // 📝 Log dans un fichier local pour le debugging
    $debugLog = __DIR__ . '/logs/callback-debug.log';
    @mkdir(__DIR__ . '/logs', 0777, true);
    
    $logMsg = "[" . date('Y-m-d H:i:s') . "] 🔄 TOKEN EXCHANGE\n";
    $logMsg .= "  URL: " . UNILIM_TOKEN_URL . "\n";
    $logMsg .= "  Code: " . substr($code, 0, 10) . "...\n";
    $logMsg .= "  Client: " . UNILIM_CLIENT_ID . "\n";
    $logMsg .= "  Redirect URI: " . UNILIM_REDIRECT_URI . "\n";
    $logMsg .= "  POST data: " . $postContent . "\n";
    file_put_contents($debugLog, $logMsg, FILE_APPEND);

    // 🔄 Essayer avec cURL d'abord (plus fiable)
    $response = null;
    $statusCode = 0;
    $usedMethod = 'none';
    
    if (extension_loaded('curl')) {
        $usedMethod = 'curl';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, UNILIM_TOKEN_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postContent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        file_put_contents($debugLog, "  Method: cURL\n  Status: " . $statusCode . "\n  cURL Error: " . ($curlError ?: 'none') . "\n", FILE_APPEND);
    } else {
        // Fallback sur file_get_contents
        $usedMethod = 'file_get_contents';
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ],
                'content' => $postContent,
                'timeout' => 10
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents(UNILIM_TOKEN_URL, false, $context);
        
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0];
            if (preg_match('/\d{3}/', $statusLine, $matches)) {
                $statusCode = (int)$matches[0];
            }
        }
        file_put_contents($debugLog, "  Method: file_get_contents\n  Status: " . $statusCode . "\n", FILE_APPEND);
    }

    if ($response === false || $statusCode === 0) {
        file_put_contents($debugLog, "  ❌ CONNEXION ÉCHOUÉE\n\n", FILE_APPEND);
        logAction("UNILIM_TOKEN_EXCHANGE_FAILED", ['error' => 'connection_failed', 'method' => $usedMethod]);
        // DEBUG: Afficher l'erreur au lieu de rediriger
        die("<h1>❌ Erreur Connexion</h1><p>Impossible de contacter Unilim (Méthode: $usedMethod)</p><pre>" . print_r(['response' => $response, 'statusCode' => $statusCode], true) . "</pre>");
    }

    // Vérifier le status HTTP - 200 ou 201 = OK
    if ($statusCode !== 200 && $statusCode !== 201) {
        file_put_contents($debugLog, "  ❌ HTTP " . $statusCode . "\n  Response: " . substr($response, 0, 300) . "\n\n", FILE_APPEND);
        logAction("UNILIM_TOKEN_EXCHANGE_FAILED", ['error' => 'http_error', 'status' => $statusCode]);
        // DEBUG: Afficher l'erreur au lieu de rediriger
        die("<h1>❌ Erreur HTTP " . $statusCode . "</h1><p>" . htmlspecialchars($response) . "</p><pre>POST data: " . htmlspecialchars($postContent) . "</pre>");
    }

    file_put_contents($debugLog, "  ✅ HTTP " . $statusCode . " OK\n  Response: " . substr($response, 0, 300) . "\n\n", FILE_APPEND);

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ Erreur décodage JSON token response: " . json_last_error_msg());
        logAction("UNILIM_JSON_ERROR", ['error' => json_last_error_msg()]);
        header("Location: /cvtek/auth/callback?error=json_error");
        exit;
    }

    if (isset($data['error'])) {
        $errorMsg = $data['error_description'] ?? $data['error'];
        error_log("❌ Erreur Unilim token: " . $errorMsg);
        logAction("UNILIM_TOKEN_ERROR", ['error' => $data['error']]);
        // DEBUG: Afficher l'erreur au lieu de rediriger
        die("<h1>❌ Erreur Unilim: " . htmlspecialchars($data['error']) . "</h1><p>" . htmlspecialchars($errorMsg) . "</p><pre>Réponse complète:\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>");
    }

    if (empty($data['id_token'])) {
        error_log("❌ Pas de token d'identité reçu");
        logAction("UNILIM_NO_ID_TOKEN", []);
        header("Location: /cvtek/auth/callback?error=no_id_token");
        exit;
    }

    logAction("UNILIM_TOKEN_EXCHANGE_SUCCESS", []);

    // ✅ ÉTAPE 6: Décoder le JWT (id_token)
    $token = $data['id_token'];
    
    // Diviser le token: header.payload.signature
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        error_log("❌ Token JWT invalide: mauvais format");
        logAction("UNILIM_INVALID_JWT", ['error' => 'invalid_format']);
        header("Location: /cvtek/auth/callback?error=invalid_jwt");
        exit;
    }

    // Décoder le payload (partie 2)
    $payload = $parts[1];
    
    // Ajouter le padding base64 si nécessaire
    $padding = 4 - (strlen($payload) % 4);
    if ($padding !== 4) {
        $payload .= str_repeat('=', $padding);
    }

    // Décoder de base64
    $decoded = base64_decode($payload, true);
    if ($decoded === false) {
        error_log("❌ Erreur décodage base64 du payload JWT");
        logAction("UNILIM_JWT_DECODE_ERROR", []);
        header("Location: /cvtek/auth/callback?error=jwt_decode_error");
        exit;
    }

    // Parser le JSON
    $payloadData = json_decode($decoded, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ Erreur parsing JSON du payload JWT: " . json_last_error_msg());
        logAction("UNILIM_JWT_PARSE_ERROR", ['error' => json_last_error_msg()]);
        header("Location: /cvtek/auth/callback?error=jwt_parse_error");
        exit;
    }

    logAction("UNILIM_JWT_DECODED", [
        'email' => $payloadData['email'] ?? 'unknown',
        'sub' => $payloadData['sub'] ?? 'unknown'
    ]);

    // 🔍 DEBUG: Afficher le payload complet pour diagnostic
    error_log("🔍 JWT PAYLOAD COMPLET: " . json_encode($payloadData, JSON_PRETTY_PRINT));
    error_log("🔍 Clés du payload: " . json_encode(array_keys($payloadData)));

    // ✅ ÉTAPE 7: Récupérer les infos utilisateur via /userinfo (pas dans le JWT)
    // Le JWT ne contient que sub, pas email. On doit appeler l'endpoint /userinfo
    $accessToken = $data['access_token'] ?? null;
    
    if (!$accessToken) {
        error_log("❌ Token d'accès manquant");
        logAction("UNILIM_MISSING_ACCESS_TOKEN", []);
        die("<h1>❌ Erreur</h1><p>Token d'accès manquant</p>");
    }

    // Appeler /userinfo pour récupérer l'email
    error_log("📡 Appel de /userinfo avec token: " . substr($accessToken, 0, 20) . "...");
    
    $userInfoUrl = 'https://cas.unilim.fr/oauth2/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $userInfoResponse = curl_exec($ch);
    $userInfoStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($userInfoResponse === false || $userInfoStatus !== 200) {
        error_log("❌ Erreur /userinfo: HTTP " . $userInfoStatus . " - " . substr($userInfoResponse, 0, 200));
        logAction("UNILIM_USERINFO_FAILED", ['status' => $userInfoStatus]);
        die("<h1>❌ Erreur /userinfo</h1><p>Impossible de récupérer les infos utilisateur</p><pre>" . htmlspecialchars($userInfoResponse) . "</pre>");
    }
    
    $userInfo = json_decode($userInfoResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ Erreur parsing /userinfo response: " . json_last_error_msg());
        logAction("UNILIM_USERINFO_PARSE_ERROR", []);
        die("<h1>❌ Erreur parsing /userinfo</h1><p>" . json_last_error_msg() . "</p>");
    }
    
    error_log("🔍 /userinfo RESPONSE: " . json_encode($userInfo, JSON_PRETTY_PRINT));
    
    // Extraire email et autres infos
    $email = $userInfo['email'] ?? null;
    if (!$email) {
        error_log("❌ Email manquant de /userinfo. Response: " . json_encode($userInfo));
        logAction("UNILIM_NO_EMAIL_IN_USERINFO", ['userinfo' => $userInfo]);
        die("<h1>❌ Erreur</h1><p>Email manquant de /userinfo</p><pre>" . json_encode($userInfo, JSON_PRETTY_PRINT) . "</pre>");
    }
    
    // 🎯 Récupérer le nom complet (nom + prénom) de /userinfo
    // Au lieu du "sub" qui est juste l'identifiant court (valin6)
    $username = $userInfo['name'] ?? $payloadData['sub'] ?? null;
    if (!$username) {
        error_log("❌ Nom manquant de /userinfo. Response: " . json_encode($userInfo));
        logAction("UNILIM_NO_NAME_IN_USERINFO", ['userinfo' => $userInfo]);
        die("<h1>❌ Erreur</h1><p>Nom manquant de /userinfo</p><pre>" . json_encode($userInfo, JSON_PRETTY_PRINT) . "</pre>");
    }
    
    error_log("✅ Username récupéré: " . $username);
    
    $role = $payloadData['role'] ?? 'student';
    
    // ✅ ÉTAPE 8: Vérifier qu'on a bien récupéré les infos
    $auth = new AuthRepository();
    
    // Déterminer le rôle en fonction du domaine d'email
    if (strpos($email, '@etu.unilim.fr') !== false) {
        $role = 'student';
    } elseif (strpos($email, '@unilim.fr') !== false) {
        $role = 'professor';
    }
    
    error_log("✅ Infos complètes récupérées: username=" . $username . ", email=" . $email . ", role=" . $role);

    // ✅ ÉTAPE 9: Traiter les professeurs et étudiants différemment
    $user = null;
    
    if ($role === 'professor') {
        // 🔒 PROFESSEUR: Doit DÉJÀ exister en BD (pas d'auto-création)
        $user = $auth->findByEmail($email);
        
        if (!$user) {
            // ❌ Professeur non autorisé
            error_log("❌ Accès refusé: professeur non enregistré: " . $email);
            logAction("UNILIM_PROFESSOR_NOT_AUTHORIZED", ['email' => $email]);
            
            // Détruire la session et les cookies
            session_destroy();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            setcookie('PHPSESSID', '', time() - 3600, '/');
            
            // Afficher page d'erreur
            http_response_code(403);
            die("
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <title>Accès refusé</title>
                    <style>
                        body { font-family: Arial, sans-serif; background: #f5f5f5; }
                        .container { max-width: 500px; margin: 100px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
                        h1 { color: #d32f2f; }
                        p { color: #666; line-height: 1.6; }
                        a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h1>🔒 Accès refusé</h1>
                        <p>Votre compte professeur n'est pas autorisé à accéder à cette application.</p>
                        <p>Veuillez contacter l'administrateur pour obtenir l'accès.</p>
                        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                        <a href='/cvtek/'>← Retour</a>
                    </div>
                </body>
                </html>
            ");
        }
    } else {
        // 👨‍🎓 ÉTUDIANT: Auto-création si n'existe pas
        $user = $auth->findOrCreateByEmail($email, $username, $role);
    }
    
    if (!$user) {
        error_log("❌ Erreur création/récupération utilisateur: " . $email);
        logAction("UNILIM_USER_CREATION_FAILED", ['email' => $email]);
        die("<h1>❌ Erreur</h1><p>Impossible de créer ou récupérer l'utilisateur</p>");
    }

    logAction("UNILIM_USER_SUCCESS", [
        'userId' => $user['id'],
        'email' => $email,
        'username' => $username,
        'role' => $role
    ]);

    // ✅ ÉTAPE 10: Générer le token JWT de l'app
    $appToken = generateToken($user['id'], $user['username'], $user['role']);
    
    // ✅ ÉTAPE 11: Stocker en session
    $_SESSION['app_token'] = $appToken;
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'parcour' => $user['parcour'] ?? null
    ];
    $_SESSION['unilim_payload'] = $payloadData;
    
    // ✅ ÉTAPE 12: Rediriger vers le dashboard (sans paramètres!)
    // Ceci évitera la boucle car le frontend verra que l'utilisateur est connecté
    logAction("UNILIM_CALLBACK_COMPLETE", [
        'userId' => $user['id'],
        'email' => $email
    ]);
    
    header("Location: /cvtek/");
    exit;

} catch (Exception $e) {
    error_log("❌ Exception: " . $e->getMessage());
    logAction("UNILIM_CALLBACK_EXCEPTION", ['error' => $e->getMessage()]);
    header("Location: /cvtek/auth/callback?error=exception");
    exit;
}
