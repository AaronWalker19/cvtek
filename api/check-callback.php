<?php
/**
 * Vérifier si le callback a été appelé
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vérifier Callback</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .ok { border-color: #3c3; background: #efe; }
        .error { border-color: #c33; background: #fee; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px; }
    </style>
</head>
<body>

<h1>✅ Vérifier si le callback a été appelé</h1>

<?php
$logFile = '/tmp/cvtek-callback.log';

echo '<div class="test ' . (file_exists($logFile) ? 'ok' : 'error') . '">';
echo '<h3>' . (file_exists($logFile) ? '✅' : '❌') . ' Callback appelé?</h3>';

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    echo '<p>✅ Oui! Le callback a été exécuté</p>';
    echo '<p>Derniers appels:</p>';
    echo '<pre>' . htmlspecialchars($content) . '</pre>';
    
    $lines = count(array_filter(explode("\n", $content)));
    echo '<p><strong>' . $lines . ' appels enregistrés</strong></p>';
} else {
    echo '<p>❌ Non, le callback n\'a pas encore été appelé</p>';
    echo '<p>Assurez-vous que:</p>';
    echo '<ol>';
    echo '<li>Le .htaccess réécrit bien vers api/callback.php</li>';
    echo '<li>Vous cliquez sur "Connexion Unilim" dans l\'app</li>';
    echo '<li>Vous complétez le login Unilim</li>';
    echo '<li>Vous êtes redirigé vers /cvtek/auth/callback</li>';
    echo '</ol>';
}

echo '</div>';

?>

<p><a href="/cvtek">← Retour</a></p>

</body>
</html>
