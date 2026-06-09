<?php
/**
 * Test Interactive Unilim Token Exchange
 * 
 * Permet de tester l'échange de code avec logs détaillés
 */

require_once __DIR__ . '/config.php';

// Vérifier si c'est une requête POST pour tester
$test_code = $_POST['test_code'] ?? $_GET['test_code'] ?? '';
$test_mode = isset($_POST['test']) || isset($_GET['test']);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Interactif Unilim Token Exchange</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 3px solid #ff6b6b; padding-bottom: 10px; }
        .section { background: white; margin: 20px 0; padding: 20px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { background: #ff6b6b; color: white; padding: 10px; margin: -20px -20px 15px -20px; border-radius: 4px 4px 0 0; }
        .form-group { margin: 15px 0; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; }
        button { background: #ff6b6b; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; }
        button:hover { background: #ff5252; }
        .test { border: 2px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; background: white; }
        .ok { border-color: #28a745; background: #f0f8f0; }
        .error { border-color: #dc3545; background: #fef0f0; }
        .ok h3 { color: #28a745; }
        .error h3 { color: #dc3545; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New'; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; border-left: 4px solid #ff6b6b; margin: 10px 0; }
        pre code { padding: 0; background: transparent; }
        .request-headers { background: #fff9c4; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .request-body { background: #f3e5f5; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .response { background: #e3f2fd; padding: 10px; border-radius: 3px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; }
        .important { background: #fff3cd; padding: 10px; border-radius: 3px; margin: 10px 0; border-left: 4px solid #ff9800; }
        .curl-cmd { background: #2b2b2b; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .curl-cmd code { background: transparent; color: inherit; }
    </style>
</head>
<body>

<div class="container">
    <h1>🧪 Test Interactif Unilim Token Exchange</h1>
    <p>Testez l'échange de code d'authentification avec le serveur Unilim et voyez exactement ce qui est envoyé et reçu.</p>

    <div class="section">
        <h2>📝 Configuration Actuelle</h2>
        
        <table>
            <tr>
                <th>Paramètre</th>
                <th>Valeur</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>CLIENT_ID</td>
                <td><code><?php echo UNILIM_CLIENT_ID; ?></code></td>
                <td><?php echo UNILIM_CLIENT_ID ? '✓' : '❌'; ?></td>
            </tr>
            <tr>
                <td>CLIENT_SECRET</td>
                <td><code><?php echo UNILIM_CLIENT_SECRET ? '••••••••••' . substr(UNILIM_CLIENT_SECRET, -3) : '(vide)'; ?></code></td>
                <td><?php echo UNILIM_CLIENT_SECRET ? '✓' : '❌ MANQUANT'; ?></td>
            </tr>
            <tr>
                <td>TOKEN_URL</td>
                <td><code><?php echo UNILIM_TOKEN_URL; ?></code></td>
                <td>✓</td>
            </tr>
            <tr>
                <td>REDIRECT_URI</td>
                <td><code><?php echo UNILIM_REDIRECT_URI; ?></code></td>
                <td>✓</td>
            </tr>
            <tr>
                <td>allow_url_fopen</td>
                <td><?php echo ini_get('allow_url_fopen') ? 'ON' : 'OFF'; ?></td>
                <td><?php echo ini_get('allow_url_fopen') ? '✓' : '❌'; ?></td>
            </tr>
        </table>

        <?php if (!UNILIM_CLIENT_SECRET): ?>
            <div class="important">
                ⚠️ <strong>CLIENT_SECRET manquant!</strong> Ajouter dans le fichier <code>.env</code>:<br>
                <code>UNILIM_CLIENT_SECRET=votre_secret_exact</code><br>
                Puis redémarrer le serveur PHP.
            </div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>🔧 Tester l'Échange de Code</h2>
        
        <form method="POST">
            <div class="form-group">
                <label for="test_code">Code d'Authentification Unilim:</label>
                <input type="text" id="test_code" name="test_code" value="<?php echo htmlspecialchars($test_code); ?>" 
                       placeholder="Collez un code d'authentification réel d'Unilim...">
                <small>ou utilisez un code de test pour voir les messages d'erreur du serveur</small>
            </div>
            
            <div class="form-group">
                <label for="custom_code">
                    <input type="checkbox" id="custom_code" name="custom_code" value="1">
                    Utiliser un code de test (pour voir la réponse d'erreur Unilim)
                </label>
            </div>
            
            <button type="submit" name="test" value="1">🚀 Tester l'Échange de Code</button>
        </form>
    </div>

    <?php if ($test_mode): ?>
        <div class="section">
            <h2>📊 Résultats du Test</h2>
            
            <?php
            // Déterminer le code à tester
            if (isset($_POST['custom_code'])) {
                $test_code = 'DEBUG_CODE_' . date('YmdHis') . '_' . rand(1000, 9999);
            }
            
            if (!$test_code) {
                $test_code = 'TEST_CODE_' . time();
            }
            
            // Construire les paramètres POST
            $postParams = [
                'grant_type'    => 'authorization_code',
                'code'          => $test_code,
                'client_id'     => UNILIM_CLIENT_ID,
                'client_secret' => UNILIM_CLIENT_SECRET,
                'redirect_uri'  => UNILIM_REDIRECT_URI,
            ];
            
            $postData = http_build_query($postParams);
            
            echo '<div class="test">';
            echo '<h3>📤 Requête Envoyée</h3>';
            
            echo '<h4>Endpoint:</h4>';
            echo '<code style="display: block; padding: 10px; background: #f5f5f5;">' . UNILIM_TOKEN_URL . '</code>';
            
            echo '<h4>Method:</h4>';
            echo '<code style="display: block; padding: 10px; background: #f5f5f5;">POST</code>';
            
            echo '<h4>Headers:</h4>';
            echo '<div class="request-headers">';
            echo '<code>Content-Type: application/x-www-form-urlencoded</code><br>';
            echo '<code>Accept: application/json</code>';
            echo '</div>';
            
            echo '<h4>Body (Form Data):</h4>';
            echo '<div class="request-body">';
            echo '<pre><code>' . htmlspecialchars($postData) . '</code></pre>';
            
            echo '<h4>Paramètres détaillés:</h4>';
            echo '<table>';
            foreach ($postParams as $key => $value) {
                $display_value = $key === 'client_secret' ? '••••••••••' . substr($value, -3) : $value;
                if ($key === 'client_secret' && !UNILIM_CLIENT_SECRET) {
                    $display_value = '(VIDE - ERREUR!)';
                }
                echo "<tr><td><strong>$key</strong></td><td><code>$display_value</code></td></tr>";
            }
            echo '</table>';
            echo '</div>';
            
            echo '</div>';
            
            // Faire la requête réelle
            echo '<div class="test">';
            echo '<h3>📥 Réponse du Serveur</h3>';
            
            $start_time = microtime(true);
            
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                    'content' => $postData,
                    'timeout' => 10,
                ],
                'ssl' => [
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                ]
            ]);
            
            $response = @file_get_contents(UNILIM_TOKEN_URL, false, $context);
            $elapsed = microtime(true) - $start_time;
            
            echo '<p><strong>⏱️ Temps de réponse:</strong> ' . number_format($elapsed, 3) . 's</p>';
            
            if (!empty($http_response_header)) {
                echo '<h4>Headers HTTP Reçus:</h4>';
                echo '<pre><code>' . htmlspecialchars(implode("\n", $http_response_header)) . '</code></pre>';
                
                // Parser le status code
                preg_match('/HTTP\D+(\d+)/', $http_response_header[0], $matches);
                $http_code = $matches[1] ?? 'Unknown';
                echo '<p><strong>Status Code:</strong> <code>' . $http_code . '</code></p>';
            }
            
            if ($response === false) {
                echo '<div class="error">';
                echo '<h3>❌ Erreur de Connexion</h3>';
                echo '<p>Le serveur n\'a pas répondu ou la connexion a échoué.</p>';
                
                if (!ini_get('allow_url_fopen')) {
                    echo '<p class="important">⚠️ <strong>allow_url_fopen est désactivé!</strong> PHP ne peut pas faire de requêtes HTTP.<br>';
                    echo 'Activer dans php.ini: <code>allow_url_fopen = On</code></p>';
                }
                
                echo '<p><strong>Causes possibles:</strong></p>';
                echo '<ul>';
                echo '<li>Firewall bloque la connexion à cas.unilim.fr:443</li>';
                echo '<li>Serveur Unilim indisponible</li>';
                echo '<li>Certificat SSL invalide ou non reconnu</li>';
                echo '<li>Timeout réseau (vérifier latence)</li>';
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<h4>Corps de la Réponse:</h4>';
                echo '<div class="response">';
                echo '<pre><code>' . htmlspecialchars($response) . '</code></pre>';
                echo '</div>';
                
                // Essayer de décoder le JSON
                $json = @json_decode($response, true);
                if ($json) {
                    echo '<h4>Analyse JSON:</h4>';
                    echo '<table>';
                    foreach ($json as $key => $value) {
                        if (is_array($value)) {
                            $display = json_encode($value);
                        } elseif (strlen($value) > 100) {
                            $display = substr($value, 0, 97) . '...';
                        } else {
                            $display = $value;
                        }
                        echo "<tr><td><strong>$key</strong></td><td><code>" . htmlspecialchars($display) . "</code></td></tr>";
                    }
                    echo '</table>';
                    
                    // Afficher les erreurs
                    if (isset($json['error'])) {
                        echo '<div class="important">';
                        echo '<strong>❌ Erreur Unilim:</strong> <code>' . $json['error'] . '</code><br>';
                        if (isset($json['error_description'])) {
                            echo '<strong>Description:</strong> ' . htmlspecialchars($json['error_description']) . '<br>';
                        }
                        echo '</div>';
                        
                        // Conseils spécifiques
                        if (strpos($json['error'], 'invalid_client') !== false) {
                            echo '<div class="important">';
                            echo '⚠️ <strong>Erreur: invalid_client</strong><br>';
                            echo 'Le CLIENT_ID ou CLIENT_SECRET est incorrect. Vérifier:<br>';
                            echo '• CLIENT_ID correspond à la valeur enregistrée chez Unilim<br>';
                            echo '• CLIENT_SECRET est correct (pas d\'espaces)<br>';
                            echo '• Les credentials n\'ont pas changé<br>';
                            echo '</div>';
                        } elseif (strpos($json['error'], 'invalid_grant') !== false) {
                            echo '<div class="important">';
                            echo '⚠️ <strong>Erreur: invalid_grant</strong><br>';
                            echo 'Le code d\'authentification est invalide ou expiré. Causes:<br>';
                            echo '• Le code a expiré (généralement 10 minutes)<br>';
                            echo '• Le REDIRECT_URI ne correspond pas<br>';
                            echo '• Le code a déjà été utilisé<br>';
                            echo '</div>';
                        } elseif (strpos($json['error'], 'invalid_request') !== false) {
                            echo '<div class="important">';
                            echo '⚠️ <strong>Erreur: invalid_request</strong><br>';
                            echo 'Un paramètre obligatoire est manquant ou mal formaté. Vérifier:<br>';
                            echo '• grant_type = "authorization_code"<br>';
                            echo '• code = code reçu d\'Unilim<br>';
                            echo '• client_id = CLIENT_ID<br>';
                            echo '• client_secret = CLIENT_SECRET<br>';
                            echo '• redirect_uri = REDIRECT_URI exact<br>';
                            echo '</div>';
                        }
                    } elseif (isset($json['access_token'])) {
                        echo '<div class="ok">';
                        echo '<h3>✅ Succès!</h3>';
                        echo '<p>Token reçu avec succès du serveur Unilim.</p>';
                        echo '<p><strong>Token Type:</strong> ' . ($json['token_type'] ?? 'N/A') . '</p>';
                        echo '<p><strong>Expires In:</strong> ' . ($json['expires_in'] ?? 'N/A') . 's</p>';
                        if (isset($json['id_token'])) {
                            echo '<p><strong>ID Token (JWT):</strong></p>';
                            echo '<pre style="word-break: break-all;"><code>' . htmlspecialchars($json['id_token']) . '</code></pre>';
                        }
                        echo '</div>';
                    }
                }
            }
            
            echo '</div>';
            ?>
        </div>

        <div class="section">
            <h2>💻 Tester avec cURL</h2>
            
            <p>Vous pouvez aussi tester directement en ligne de commande avec cURL:</p>
            
            <div class="curl-cmd">
                <code>curl -X POST https://cas.unilim.fr/oauth2/token \
  -d "grant_type=authorization_code" \
  -d "code=<?php echo htmlspecialchars($test_code); ?>" \
  -d "client_id=<?php echo UNILIM_CLIENT_ID; ?>" \
  -d "client_secret=<?php echo UNILIM_CLIENT_SECRET ? '(votre_secret)' : '(VIDE)'; ?>" \
  -d "redirect_uri=<?php echo urlencode(UNILIM_REDIRECT_URI); ?>"</code>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
