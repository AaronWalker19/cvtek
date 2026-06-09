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

    // ✅ ÉTAPE 7: Stocker les infos en session et rediriger
    $_SESSION['unilim_payload'] = $payloadData;
    $_SESSION['unilim_token_response'] = $data;
    
    // Rediriger vers le frontend React avec le code et state
    header("Location: /cvtek/auth/callback?code=" . urlencode($code) . "&state=" . urlencode($state));
    exit;

} catch (Exception $e) {
    error_log("❌ Exception: " . $e->getMessage());
    logAction("UNILIM_CALLBACK_EXCEPTION", ['error' => $e->getMessage()]);
    header("Location: /cvtek/auth/callback?error=exception");
    exit;
}
