<?php
/**
 * Debug - Affiche exactement ce qui est envoyé à Unilim lors de l'échange
 */

require_once __DIR__ . '/config.php';

// Récupérer le code depuis l'URL si présent
$code = $_GET['code'] ?? $_POST['code'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Debug Échange de Code</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { border: 2px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .ok { border-color: #28a745; background: #f0f8f0; }
        .warning { border-color: #ffc107; background: #fff9e6; }
        .error { border-color: #dc3545; background: #fef0f0; }
        h3 { margin-top: 0; }
        code { background: #f5f5f5; padding: 3px 6px; border-radius: 2px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>

<h1>🔍 Debug: Échange de Code Unilim</h1>

<div class="box ok">
    <h3>📋 Configuration Unilim</h3>
    <table>
        <tr>
            <th>Paramètre</th>
            <th>Valeur</th>
        </tr>
        <tr>
            <td><strong>CLIENT_ID</strong></td>
            <td><code><?php echo UNILIM_CLIENT_ID; ?></code></td>
        </tr>
        <tr>
            <td><strong>CLIENT_SECRET</strong></td>
            <td><code><?php echo UNILIM_CLIENT_SECRET ? strlen(UNILIM_CLIENT_SECRET) . ' caractères' : '(VIDE)'; ?></code></td>
        </tr>
        <tr>
            <td><strong>TOKEN_URL</strong></td>
            <td><code><?php echo UNILIM_TOKEN_URL; ?></code></td>
        </tr>
        <tr>
            <td><strong>REDIRECT_URI</strong></td>
            <td><code><?php echo UNILIM_REDIRECT_URI; ?></code></td>
        </tr>
    </table>
</div>

<div class="box">
    <h3>🔧 Paramètres Envoyés à Unilim</h3>
    
    <p><strong>URL de la requête:</strong></p>
    <code style="display: block; padding: 10px; background: #f5f5f5;">POST <?php echo UNILIM_TOKEN_URL; ?></code>
    
    <p><strong>Headers:</strong></p>
    <pre>Content-Type: application/x-www-form-urlencoded
Accept: application/json</pre>
    
    <p><strong>Body (Données POST):</strong></p>
    <?php
    // Construire les paramètres comme le ferait exchangeCodeForToken()
    $demo_code = $code ?: 'DEMO_CODE_' . time();
    
    $postParams = [
        'grant_type'    => 'authorization_code',
        'code'          => $demo_code,
        'client_id'     => UNILIM_CLIENT_ID,
        'client_secret' => UNILIM_CLIENT_SECRET,
        'redirect_uri'  => UNILIM_REDIRECT_URI,
    ];
    
    $postData = http_build_query($postParams);
    
    echo '<pre>' . htmlspecialchars($postData) . '</pre>';
    
    echo '<p><strong>Décodé en tableau:</strong></p>';
    echo '<table>';
    foreach ($postParams as $key => $value) {
        $display = $value;
        if ($key === 'client_secret') {
            $display = UNILIM_CLIENT_SECRET ? '••••••••••' . substr($value, -3) : '(VIDE)';
        }
        echo "<tr><td><strong>$key</strong></td><td><code>" . htmlspecialchars($display) . "</code></td></tr>";
    }
    echo '</table>';
    ?>
</div>

<div class="box warning">
    <h3>⚠️ Points à Vérifier chez Unilim</h3>
    
    <p>Contactez l'administrateur Unilim et vérifiez que:</p>
    
    <table>
        <tr>
            <th>Paramètre</th>
            <th>Votre Valeur</th>
            <th>À Vérifier</th>
        </tr>
        <tr>
            <td><strong>CLIENT_ID</strong></td>
            <td><code><?php echo UNILIM_CLIENT_ID; ?></code></td>
            <td>☐ Correspond exactement</td>
        </tr>
        <tr>
            <td><strong>CLIENT_SECRET</strong></td>
            <td><code><?php echo UNILIM_CLIENT_SECRET ? strlen(UNILIM_CLIENT_SECRET) . ' chars' : '(VIDE)'; ?></code></td>
            <td>☐ Correct (pas d'espaces)</td>
        </tr>
        <tr>
            <td><strong>REDIRECT_URI</strong></td>
            <td><code><?php echo UNILIM_REDIRECT_URI; ?></code></td>
            <td>☐ <strong>EXACTEMENT</strong> enregistré chez Unilim</td>
        </tr>
    </table>
    
    <p><strong>⚠️ ATTENTION REDIRECT_URI:</strong></p>
    <ul>
        <li>Protocole: ✓ <code>https://</code> vs ✗ <code>http://</code></li>
        <li>Domaine: ✓ <code>mmi.unilim.fr</code> exactement</li>
        <li>Chemin: ✓ <code>/cvtek/auth/callback</code></li>
        <li>Slash final: ✓ <code>https://mmi.unilim.fr/cvtek/auth/callback</code> (SANS slash final)</li>
        <li>Pas de paramètres: ✓ Pas de <code>?</code> ou <code>#</code></li>
    </ul>
</div>

<div class="box">
    <h3>🧪 Test avec cURL</h3>
    
    <p>Vous pouvez tester manuellement avec:</p>
    
    <pre>curl -X POST <?php echo UNILIM_TOKEN_URL; ?> \
  -d "grant_type=authorization_code" \
  -d "code=YOUR_CODE_HERE" \
  -d "client_id=<?php echo UNILIM_CLIENT_ID; ?>" \
  -d "client_secret=<?php echo UNILIM_CLIENT_SECRET ? '(votre_secret)' : '(MANQUANT)'; ?>" \
  -d "redirect_uri=<?php echo urlencode(UNILIM_REDIRECT_URI); ?>"</pre>
</div>

<div class="box error">
    <h3>❌ Erreur: invalid_grant</h3>
    
    <p>Cette erreur signifie que Unilim a rejeté votre requête. Causes possibles:</p>
    
    <ol>
        <li><strong>REDIRECT_URI ne correspond pas</strong> (CAUSE LA PLUS PROBABLE)
            <ul>
                <li>Vérifié chez Unilim que c'est: <code><?php echo UNILIM_REDIRECT_URI; ?></code></li>
                <li>Vérifier slash final, protocole, domaine, chemin</li>
                <li>Attention aux typos!</li>
            </ul>
        </li>
        
        <li><strong>Code expiré</strong>
            <ul>
                <li>Code valide ~10 minutes seulement</li>
                <li>Réessayez immédiatement après le login</li>
            </ul>
        </li>
        
        <li><strong>Code déjà utilisé</strong>
            <ul>
                <li>Chaque code ne peut être utilisé qu'une fois</li>
                <li>Refaites un login pour obtenir un nouveau code</li>
            </ul>
        </li>
        
        <li><strong>CLIENT_SECRET incorrect</strong>
            <ul>
                <li>Vérifier auprès de Unilim (pas d'espaces)</li>
                <li>Redémarrer PHP après modification</li>
            </ul>
        </li>
    </ol>
</div>

</body>
</html>
