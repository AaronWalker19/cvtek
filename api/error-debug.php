<?php
/**
 * Page d'affichage des erreurs Unilim (debug)
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Erreur Unilim SSO</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; }
        .error { background: #fee; border: 1px solid #c33; padding: 20px; border-radius: 4px; }
        .success { background: #efe; border: 1px solid #3c3; padding: 20px; border-radius: 4px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>

<h1>🔐 Callback Unilim SSO</h1>

<?php
session_start();
require_once __DIR__ . '/config.php';

$error = $_GET['error'] ?? null;
$error_description = $_GET['error_description'] ?? null;
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if ($error) {
    echo '<div class="error">';
    echo '<h2>❌ Erreur: ' . htmlspecialchars($error) . '</h2>';
    if ($error_description) {
        echo '<p>' . htmlspecialchars($error_description) . '</p>';
    }
    echo '<h3>Debug:</h3>';
    echo '<p><strong>Error param:</strong> ' . htmlspecialchars($error) . '</p>';
    echo '<p><strong>Description:</strong> ' . htmlspecialchars($error_description ?? 'none') . '</p>';
    echo '<p><strong>Config:</strong></p>';
    echo '<pre>';
    echo "UNILIM_CLIENT_ID: " . UNILIM_CLIENT_ID . "\n";
    echo "UNILIM_TOKEN_URL: " . UNILIM_TOKEN_URL . "\n";
    echo "UNILIM_REDIRECT_URI: " . UNILIM_REDIRECT_URI . "\n";
    echo '</pre>';
    echo '<p><a href="/cvtek">← Retour à l\'accueil</a></p>';
    echo '</div>';
} elseif ($code && $state) {
    echo '<div class="success">';
    echo '<h2>✅ Code reçu!</h2>';
    echo '<p>Code: ' . substr(htmlspecialchars($code), 0, 20) . '...</p>';
    echo '<p>State: ' . substr(htmlspecialchars($state), 0, 20) . '...</p>';
    echo '<p>Traitement en cours...</p>';
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<h2>❌ Paramètres manquants</h2>';
    echo '<p>Code: ' . ($code ? 'OK' : 'MANQUANT') . '</p>';
    echo '<p>State: ' . ($state ? 'OK' : 'MANQUANT') . '</p>';
    echo '</div>';
}
?>

</body>
</html>
