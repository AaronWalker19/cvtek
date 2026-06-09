<?php
/**
 * Page de test de connexion à Unilim
 */
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Unilim</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; }
        .test { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .ok { border-color: #3c3; background: #efe; }
        .error { border-color: #c33; background: #fee; }
        code { background: #f5f5f5; padding: 2px 6px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>

<h1>🧪 Test de connexion Unilim</h1>

<?php
// Test 1: Configuration
echo '<div class="test ok">';
echo '<h3>✅ Configuration chargée</h3>';
echo '<p>CLIENT_ID: <code>' . UNILIM_CLIENT_ID . '</code></p>';
echo '<p>TOKEN_URL: <code>' . UNILIM_TOKEN_URL . '</code></p>';
echo '<p>REDIRECT_URI: <code>' . UNILIM_REDIRECT_URI . '</code></p>';
echo '</div>';

// Test 2: Extensions PHP
echo '<div class="test ' . (ini_get('allow_url_fopen') ? 'ok' : 'error') . '">';
echo '<h3>' . (ini_get('allow_url_fopen') ? '✅' : '❌') . ' allow_url_fopen</h3>';
echo '<p>' . (ini_get('allow_url_fopen') ? 'Activé - PHP peut faire des requêtes HTTP' : 'Désactivé - PHP NE peut PAS faire de requêtes HTTP!') . '</p>';
echo '</div>';

// Test 3: OpenSSL
echo '<div class="test ' . (extension_loaded('openssl') ? 'ok' : 'error') . '">';
echo '<h3>' . (extension_loaded('openssl') ? '✅' : '❌') . ' OpenSSL</h3>';
echo '<p>' . (extension_loaded('openssl') ? 'Disponible - HTTPS fonctionne' : 'Manquant - HTTPS ne fonctionnera pas!') . '</p>';
echo '</div>';

// Test 4: Connexion à Unilim
echo '<div class="test">';
echo '<h3>🔄 Test de connexion à ' . UNILIM_TOKEN_URL . '</h3>';

$context = stream_context_create([
    'http' => ['timeout' => 5],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response = @file_get_contents(UNILIM_TOKEN_URL, false, $context);

if ($response !== false) {
    echo '<div class="ok">';
    echo '<p>✅ Connexion réussie!</p>';
    echo '<p>Réponse: <pre>' . substr(htmlspecialchars($response), 0, 200) . '...</pre></p>';
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<p>❌ Connexion échouée!</p>';
    if (isset($http_response_header)) {
        echo '<p>Headers: <pre>' . json_encode($http_response_header, JSON_PRETTY_PRINT) . '</pre></p>';
    }
    echo '<p><strong>Possibilités:</strong></p>';
    echo '<ul>';
    echo '<li>Firewall bloque la connexion</li>';
    echo '<li>Serveur Unilim indisponible</li>';
    echo '<li>Certificat SSL invalide</li>';
    echo '<li>allow_url_fopen désactivé</li>';
    echo '</ul>';
    echo '</div>';
}

echo '</div>';
?>

<p><a href="/cvtek">← Retour</a></p>

</body>
</html>
