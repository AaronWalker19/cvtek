<?php
/**
 * Vérifier exactement ce qui est envoyé à Unilim
 */

require_once __DIR__ . '/config.php';

// Construire les paramètres exactement comme dans callback.php
$demo_code = $_GET['code'] ?? 'DEMO_CODE_' . time();

$postData = [
    'grant_type'    => 'authorization_code',
    'code'          => $demo_code,
    'client_id'     => UNILIM_CLIENT_ID,
    'client_secret' => UNILIM_CLIENT_SECRET,
    'redirect_uri'  => UNILIM_REDIRECT_URI,
];

$postContent = http_build_query($postData);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vérifier les Paramètres POST</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { border: 2px solid #28a745; padding: 15px; margin: 15px 0; border-radius: 4px; background: #f0f8f0; }
        h2 { color: #28a745; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f8f0; font-weight: bold; }
        code { background: #f5f5f5; padding: 3px 6px; }
    </style>
</head>
<body>

<h1>✅ Vérification des Paramètres POST</h1>

<div class="box">
    <h2>📋 Configuration Unilim</h2>
    <table>
        <tr>
            <th>Paramètre</th>
            <th>Valeur</th>
            <th>Status</th>
        </tr>
        <tr>
            <td><strong>UNILIM_TOKEN_URL</strong></td>
            <td><code><?php echo UNILIM_TOKEN_URL; ?></code></td>
            <td>✓</td>
        </tr>
        <tr>
            <td><strong>UNILIM_CLIENT_ID</strong></td>
            <td><code><?php echo UNILIM_CLIENT_ID; ?></code></td>
            <td>✓</td>
        </tr>
        <tr>
            <td><strong>UNILIM_REDIRECT_URI</strong></td>
            <td><code><?php echo UNILIM_REDIRECT_URI; ?></code></td>
            <td>✓</td>
        </tr>
    </table>
</div>

<div class="box">
    <h2>📤 Requête POST à Envoyer</h2>
    
    <p><strong>Méthode:</strong> <code>POST</code></p>
    <p><strong>URL:</strong> <code><?php echo UNILIM_TOKEN_URL; ?></code></p>
    <p><strong>Content-Type:</strong> <code>application/x-www-form-urlencoded</code></p>
    
    <h3>Paramètres:</h3>
    <table>
        <tr>
            <th>Paramètre</th>
            <th>Valeur</th>
            <th>Type</th>
        </tr>
        <tr>
            <td><strong>grant_type</strong></td>
            <td><code>authorization_code</code></td>
            <td>String</td>
        </tr>
        <tr>
            <td><strong>code</strong></td>
            <td><code><?php echo htmlspecialchars($demo_code); ?></code></td>
            <td>String (code Unilim)</td>
        </tr>
        <tr>
            <td><strong>client_id</strong></td>
            <td><code><?php echo UNILIM_CLIENT_ID; ?></code></td>
            <td>String</td>
        </tr>
        <tr>
            <td><strong>client_secret</strong></td>
            <td><code><?php echo UNILIM_CLIENT_SECRET ? '••••••••••' . substr(UNILIM_CLIENT_SECRET, -3) : '(VIDE)'; ?></code></td>
            <td>String (SECRET)</td>
        </tr>
        <tr>
            <td><strong>redirect_uri</strong></td>
            <td><code><?php echo UNILIM_REDIRECT_URI; ?></code></td>
            <td>String (URL exact)</td>
        </tr>
    </table>
</div>

<div class="box">
    <h2>📝 Données POST Encodées (form-urlencoded)</h2>
    <pre><?php echo htmlspecialchars($postContent); ?></pre>
    
    <h3>Format cURL:</h3>
    <pre>curl -X POST <?php echo UNILIM_TOKEN_URL; ?> \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "<?php echo htmlspecialchars($postContent); ?>"</pre>
</div>

<div class="box">
    <h2>✅ Checklist</h2>
    <ul>
        <li>☑ grant_type = "authorization_code"</li>
        <li>☑ code = (code reçu du callback Unilim)</li>
        <li>☑ client_id = <?php echo UNILIM_CLIENT_ID; ?></li>
        <li>☑ client_secret = (défini)</li>
        <li>☑ redirect_uri = <?php echo UNILIM_REDIRECT_URI; ?></li>
        <li>☑ Méthode = POST</li>
        <li>☑ Content-Type = application/x-www-form-urlencoded</li>
    </ul>
</div>

</body>
</html>
