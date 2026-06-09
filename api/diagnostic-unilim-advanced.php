<?php
/**
 * Diagnostic avancé - Connexion Unilim SSO
 * 
 * Tests:
 * 1. Configuration Unilim
 * 2. Extensions PHP (allow_url_fopen, OpenSSL, cURL)
 * 3. Certificats SSL
 * 4. Paramètres de requête exacts
 * 5. Réponse complète du serveur
 * 6. Vérification du CLIENT_SECRET
 */

require_once __DIR__ . '/config.php';

// Fonction pour afficher les résultats
function showTest($title, $status, $details = '') {
    $class = $status ? 'ok' : 'error';
    $icon = $status ? '✅' : '❌';
    echo "<div class='test $class'>";
    echo "<h3>$icon $title</h3>";
    if ($details) {
        echo "<p>$details</p>";
    }
    echo "</div>";
}

function showCode($code) {
    echo "<pre><code>" . htmlspecialchars($code) . "</code></pre>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Diagnostic Unilim SSO Avancé</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .section { margin: 20px 0; }
        .section h2 { background: #007bff; color: white; padding: 10px 15px; margin: 0; border-radius: 4px 4px 0 0; }
        .test { border: 2px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; background: white; }
        .ok { border-color: #28a745; background: #f0f8f0; }
        .error { border-color: #dc3545; background: #fef0f0; }
        .ok h3 { color: #28a745; }
        .error h3 { color: #dc3545; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New'; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; border-left: 4px solid #007bff; }
        pre code { padding: 0; background: transparent; }
        .parameter { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #007bff; border-radius: 3px; }
        .parameter strong { color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; }
        .warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .warning h3 { color: #ff9800; }
        .success { background: #d4edda; border-color: #28a745; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Diagnostic Unilim SSO Avancé</h1>
    <p>Détection du problème de connexion au serveur Unilim (erreur 400)</p>

    <!-- ========== SECTION 1: Configuration ========== -->
    <div class="section">
        <h2>1️⃣ Configuration</h2>
        
        <?php
        // Vérifier si credentials sont configurés
        $hasClientSecret = !empty(UNILIM_CLIENT_SECRET) && UNILIM_CLIENT_SECRET !== '';
        
        showTest('CLIENT_ID configuré', !empty(UNILIM_CLIENT_ID),
            'Valeur: <code>' . UNILIM_CLIENT_ID . '</code>');
        
        showTest('CLIENT_SECRET configuré', $hasClientSecret,
            $hasClientSecret 
                ? '✓ Secret défini (longueur: ' . strlen(UNILIM_CLIENT_SECRET) . ' car.)'
                : '⚠️ SECRET MANQUANT ou vide! Ajouter à .env: UNILIM_CLIENT_SECRET=votre_secret_ici');
        
        showTest('URLs configurées', true,
            'TOKEN_URL: <code>' . UNILIM_TOKEN_URL . '</code><br>' .
            'REDIRECT_URI: <code>' . UNILIM_REDIRECT_URI . '</code>');
        ?>
    </div>

    <!-- ========== SECTION 2: Extensions PHP ========== -->
    <div class="section">
        <h2>2️⃣ Extensions PHP</h2>
        
        <?php
        $allow_url_fopen = ini_get('allow_url_fopen');
        $openssl_loaded = extension_loaded('openssl');
        $curl_loaded = extension_loaded('curl');
        
        showTest(
            'allow_url_fopen',
            $allow_url_fopen,
            $allow_url_fopen 
                ? '✓ Activé - PHP peut faire des requêtes HTTP/HTTPS'
                : '❌ Désactivé! PHP ne peut PAS faire de requêtes (file_get_contents, fopen, etc.)<br>'.
                  'Solution: Activer dans php.ini: allow_url_fopen = On'
        );
        
        showTest(
            'OpenSSL',
            $openssl_loaded,
            $openssl_loaded
                ? '✓ Disponible - HTTPS et certificats supportés'
                : '❌ Manquant! HTTPS ne fonctionnera pas'
        );
        
        showTest(
            'cURL',
            $curl_loaded,
            $curl_loaded
                ? '✓ Disponible (méthode alternative pour requêtes)'
                : '⚠️ Non disponible (mais allow_url_fopen peut suffire)'
        );
        ?>
    </div>

    <!-- ========== SECTION 3: Certificats SSL ========== -->
    <div class="section">
        <h2>3️⃣ Certificats SSL</h2>
        
        <?php
        // Test connexion SSL simple
        $ssl_context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ]
        ]);
        
        $fp = @fsockopen('ssl://cas.unilim.fr', 443, $errno, $errstr, 5);
        $ssl_works = ($fp !== false);
        if ($ssl_works) fclose($fp);
        
        showTest(
            'Connexion SSL à cas.unilim.fr',
            $ssl_works,
            $ssl_works
                ? '✓ Certificat valide et connexion OK'
                : "❌ Erreur SSL: $errstr (Code: $errno)<br>".
                  'Cela peut indiquer:<br>'.
                  '• Certificat auto-signé non accepté<br>'.
                  '• Certificat expiré<br>'.
                  '• Firewall bloquant la connexion<br>'.
                  '• Serveur Unilim indisponible'
        );
        
        // Test DNS
        $ip = @gethostbyname('cas.unilim.fr');
        $dns_works = ($ip !== 'cas.unilim.fr');
        showTest(
            'DNS (Résolution du domaine)',
            $dns_works,
            $dns_works
                ? "✓ cas.unilim.fr → $ip"
                : '❌ Impossible de résoudre cas.unilim.fr<br>'.
                  'Vérifier la connexion Internet'
        );
        ?>
    </div>

    <!-- ========== SECTION 4: Paramètres de Requête ========== -->
    <div class="section">
        <h2>4️⃣ Paramètres de Requête (Token Endpoint)</h2>
        
        <p>Les paramètres suivants seront envoyés lors de l'échange de code:</p>
        
        <?php
        // Simuler un code pour la démo
        $demo_code = 'DEMO_AUTH_CODE_12345';
        $demo_grant_type = 'authorization_code';
        
        $params = [
            'grant_type'    => $demo_grant_type,
            'code'          => $demo_code,
            'client_id'     => UNILIM_CLIENT_ID,
            'client_secret' => UNILIM_CLIENT_SECRET ?: 'CLIENT_SECRET_MANQUANT',
            'redirect_uri'  => UNILIM_REDIRECT_URI,
        ];
        
        echo '<table>';
        echo '<tr><th>Paramètre</th><th>Valeur</th><th>Vérification</th></tr>';
        foreach ($params as $key => $value) {
            $icon = ($value && $value !== 'CLIENT_SECRET_MANQUANT') ? '✓' : '❌';
            echo "<tr>";
            echo "<td><strong>$key</strong></td>";
            echo "<td><code>" . (strlen($value) > 50 ? substr($value, 0, 47) . '...' : $value) . "</code></td>";
            echo "<td>$icon</td>";
            echo "</tr>";
        }
        echo '</table>';
        
        echo '<div class="test">';
        echo '<h3>Format POST (application/x-www-form-urlencoded)</h3>';
        echo '<p>Les données seront envoyées comme:</p>';
        $post_data = http_build_query($params);
        showCode($post_data);
        echo '</div>';
        ?>
    </div>

    <!-- ========== SECTION 5: Test de Connexion Réelle ========== -->
    <div class="section">
        <h2>5️⃣ Test de Connexion Réelle</h2>
        
        <p><strong>Note:</strong> Utilise un code d'authentification de démonstration. Le serveur Unilim rejettera le code, mais on verra la réponse détaillée.</p>
        
        <?php
        // Créer la requête
        $demo_code = 'DEBUG_CODE_' . time();
        $post_params = [
            'grant_type'    => 'authorization_code',
            'code'          => $demo_code,
            'client_id'     => UNILIM_CLIENT_ID,
            'client_secret' => UNILIM_CLIENT_SECRET,
            'redirect_uri'  => UNILIM_REDIRECT_URI,
        ];
        
        $post_data = http_build_query($post_params);
        
        // Option 1: Utiliser file_get_contents (si allow_url_fopen = On)
        if ($allow_url_fopen) {
            echo '<div class="test">';
            echo '<h3>📤 Requête file_get_contents (allow_url_fopen)</h3>';
            
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                                "Content-Length: " . strlen($post_data) . "\r\n",
                    'content' => $post_data,
                    'timeout' => 10,
                ],
                'ssl' => [
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                ]
            ]);
            
            $start_time = microtime(true);
            $response = @file_get_contents(UNILIM_TOKEN_URL, false, $context);
            $elapsed = microtime(true) - $start_time;
            
            echo "<p>⏱️ Temps de réponse: " . number_format($elapsed, 3) . "s</p>";
            
            if ($response === false) {
                echo '<p class="error">❌ Erreur de connexion</p>';
                echo '<p>Informations sur l\'erreur:</p>';
                if (!empty($http_response_header)) {
                    echo '<h4>Headers HTTP:</h4>';
                    showCode(implode("\n", $http_response_header));
                }
            } else {
                echo '<h4>Réponse du serveur:</h4>';
                echo '<p>Longueur: ' . strlen($response) . ' octets</p>';
                showCode($response);
                
                // Essayer de décoder JSON
                $json = @json_decode($response, true);
                if ($json) {
                    echo '<h4>Analyse JSON:</h4>';
                    echo '<div class="parameter">';
                    foreach ($json as $key => $value) {
                        echo "<strong>$key:</strong> <code>" . 
                             (is_array($value) ? json_encode($value) : $value) . 
                             "</code><br>";
                    }
                    echo '</div>';
                }
            }
            
            echo '</div>';
        }
        
        // Option 2: Utiliser cURL (si disponible)
        if ($curl_loaded) {
            echo '<div class="test">';
            echo '<h3>📤 Requête cURL (alternative)</h3>';
            
            $ch = curl_init(UNILIM_TOKEN_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            
            $start_time = microtime(true);
            $response = @curl_exec($ch);
            $elapsed = microtime(true) - $start_time;
            
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            echo "<p>⏱️ Temps: " . number_format($elapsed, 3) . "s</p>";
            echo "<p>HTTP Code: <code>$http_code</code></p>";
            
            if ($error) {
                echo "<p class='error'>❌ Erreur cURL: $error</p>";
            } else {
                echo '<h4>Réponse du serveur:</h4>';
                showCode($response);
            }
            
            echo '</div>';
        }
        ?>
    </div>

    <!-- ========== SECTION 6: Causes Possibles et Solutions ========== -->
    <div class="section">
        <h2>6️⃣ Causes Possibles et Solutions</h2>
        
        <div class="test warning">
            <h3>⚠️ Erreur 400 Bad Request - Dépannage</h3>
            
            <h4>Cause 1️⃣: CLIENT_SECRET manquant ou incorrect</h4>
            <p><strong>Symptôme:</strong> Erreur 400, message: "invalid_client"</p>
            <p><strong>Solution:</strong></p>
            <ol>
                <li>Vérifier que UNILIM_CLIENT_SECRET est défini dans <code>.env</code></li>
                <li>Vérifier que le secret est correct (compare avec les credentials Unilim)</li>
                <li>Vérifier qu'il n'y a pas d'espaces ou caractères invisibles</li>
            </ol>
            <pre>UNILIM_CLIENT_SECRET=votre_secret_exact_ici</pre>
            
            <hr>
            
            <h4>Cause 2️⃣: CLIENT_ID incorrect</h4>
            <p><strong>Symptôme:</strong> Erreur 400</p>
            <p><strong>Solution:</strong></p>
            <ol>
                <li>Vérifier que UNILIM_CLIENT_ID = "gupp" (ou la valeur exacte fournie par Unilim)</li>
                <li>Vérifier auprès de l'administrateur Unilim</li>
            </ol>
            
            <hr>
            
            <h4>Cause 3️⃣: Paramètre de requête manquant ou mal formaté</h4>
            <p><strong>Symptôme:</strong> Erreur 400</p>
            <p><strong>Paramètres requis:</strong></p>
            <ul>
                <li><code>grant_type</code> = "authorization_code" (OBLIGATOIRE)</li>
                <li><code>code</code> = code reçu d'Unilim (OBLIGATOIRE)</li>
                <li><code>client_id</code> = votre CLIENT_ID (OBLIGATOIRE)</li>
                <li><code>client_secret</code> = votre SECRET (OBLIGATOIRE)</li>
                <li><code>redirect_uri</code> = URL de callback exact (OBLIGATOIRE)</li>
            </ul>
            
            <hr>
            
            <h4>Cause 4️⃣: REDIRECT_URI ne correspond pas</h4>
            <p><strong>Symptôme:</strong> Erreur 400, message: "invalid_grant" ou "redirect_uri_mismatch"</p>
            <p><strong>Solution:</strong></p>
            <ol>
                <li>Vérifier que UNILIM_REDIRECT_URI correspond exactement à celui configuré chez Unilim</li>
                <li>Vérifier: protocole (http/https), domaine, port, chemin</li>
                <li>Valeur actuelle: <code><?php echo UNILIM_REDIRECT_URI; ?></code></li>
            </ol>
            
            <hr>
            
            <h4>Cause 5️⃣: Firewall ou connexion réseau</h4>
            <p><strong>Symptôme:</strong> Timeout, "Connection refused", "Network unreachable"</p>
            <p><strong>Solution:</strong></p>
            <ol>
                <li>Vérifier que le serveur Unilim est accessible: <code>ping cas.unilim.fr</code></li>
                <li>Vérifier qu'aucun proxy/firewall ne bloque port 443</li>
                <li>Demander au support réseau de débloquer https://cas.unilim.fr</li>
            </ol>
            
            <hr>
            
            <h4>Cause 6️⃣: Certificat SSL invalide</h4>
            <p><strong>Symptôme:</strong> "SSL certificate problem", "certificate verify failed"</p>
            <p><strong>Solution:</strong></p>
            <ol>
                <li>Mettre à jour les certificats CA du système</li>
                <li>Sur Linux: <code>apt-get install ca-certificates</code></li>
                <li>Sur Windows: Mettre à jour via Windows Update</li>
                <li>En dernier recours (NOT RECOMMENDED): Désactiver vérification SSL (développement seulement)</li>
            </ol>
        </div>
    </div>

    <!-- ========== SECTION 7: Logs de Débogage ========== -->
    <div class="section">
        <h2>7️⃣ Logs de Débogage</h2>
        
        <p>Pour déboguer plus loin, activez la journalisation:</p>
        
        <div class="test">
            <h3>Activer APP_DEBUG dans .env</h3>
            <pre>APP_DEBUG=true</pre>
            <p>Cela affichera les détails complets des erreurs PHP.</p>
        </div>
        
        <div class="test">
            <h3>Vérifier les logs PHP</h3>
            <pre>tail -f /var/log/php-errors.log</pre>
            <p>Ou: <code><?php echo ini_get('error_log'); ?></code></p>
        </div>
        
        <div class="test">
            <h3>Logs de callback Unilim</h3>
            <pre><?php 
            $logFile = '/tmp/cvtek-callback.log';
            if (file_exists($logFile)) {
                echo "Existe: " . $logFile . "\n";
                echo "Contenu (dernières 10 lignes):\n";
                $lines = array_slice(file($logFile), -10);
                echo implode('', $lines);
            } else {
                echo "Fichier de logs non trouvé: " . $logFile;
            }
            ?></pre>
        </div>
    </div>

    <!-- ========== SECTION 8: Prochaines Étapes ========== -->
    <div class="section">
        <h2>8️⃣ Prochaines Étapes</h2>
        
        <div class="test success">
            <h3>✅ Checklist de Débogage</h3>
            <ol>
                <li>☐ Vérifier que CLIENT_SECRET n'est pas vide dans .env</li>
                <li>☐ Comparer CLIENT_ID et CLIENT_SECRET avec credentials officiels Unilim</li>
                <li>☐ Vérifier que REDIRECT_URI correspond (protocole, domaine, port, chemin)</li>
                <li>☐ Tester: <code>curl -I https://cas.unilim.fr</code> (doit être 200)</li>
                <li>☐ Vérifier allow_url_fopen = On dans php.ini</li>
                <li>☐ Relancer le serveur PHP après modifications de .env</li>
                <li>☐ Vider le cache du navigateur (Ctrl+Shift+Delete)</li>
                <li>☐ Vérifier les logs PHP pour les erreurs détaillées</li>
            </ol>
        </div>
        
        <div class="test">
            <h3>📞 Support Unilim</h3>
            <p>Si tous les tests réussissent mais l'erreur 400 persiste:</p>
            <ol>
                <li>Contacter le support Unilim avec:</li>
                <li>Screenshot de cette page (diagnostic complet)</li>
                <li>Message d'erreur exact du serveur</li>
                <li>CLIENT_ID utilisé</li>
                <li>REDIRECT_URI configurée</li>
            </ol>
        </div>
    </div>

</div>

</body>
</html>
