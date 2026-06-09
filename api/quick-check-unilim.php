<?php
/**
 * Vérification Rapide Unilim SSO
 * 
 * Résumé complet du diagnostic en une seule page
 */

require_once __DIR__ . '/config.php';

// Collecte des informations
$checks = [];

// 1. Configuration
$checks['config_client_id'] = [
    'name' => 'CLIENT_ID configuré',
    'status' => !empty(UNILIM_CLIENT_ID),
    'value' => UNILIM_CLIENT_ID ?: '(vide)',
];

$checks['config_client_secret'] = [
    'name' => 'CLIENT_SECRET configuré',
    'status' => !empty(UNILIM_CLIENT_SECRET),
    'value' => UNILIM_CLIENT_SECRET ? strlen(UNILIM_CLIENT_SECRET) . ' caractères' : '(vide)',
];

$checks['config_token_url'] = [
    'name' => 'TOKEN_URL',
    'status' => !empty(UNILIM_TOKEN_URL),
    'value' => UNILIM_TOKEN_URL,
];

$checks['config_redirect_uri'] = [
    'name' => 'REDIRECT_URI',
    'status' => !empty(UNILIM_REDIRECT_URI),
    'value' => UNILIM_REDIRECT_URI,
];

// 2. Extensions PHP
$checks['php_allow_url_fopen'] = [
    'name' => 'allow_url_fopen',
    'status' => (bool)ini_get('allow_url_fopen'),
    'value' => ini_get('allow_url_fopen') ? 'ON' : 'OFF',
];

$checks['php_openssl'] = [
    'name' => 'Extension OpenSSL',
    'status' => extension_loaded('openssl'),
    'value' => extension_loaded('openssl') ? 'Chargée' : 'Manquante',
];

$checks['php_curl'] = [
    'name' => 'Extension cURL',
    'status' => extension_loaded('curl'),
    'value' => extension_loaded('curl') ? 'Chargée' : 'Non chargée',
];

// 3. Réseau (tests simples)
$fp = @fsockopen('ssl://cas.unilim.fr', 443, $errno, $errstr, 5);
$ssl_works = ($fp !== false);
if ($ssl_works) fclose($fp);

$checks['network_ssl_connection'] = [
    'name' => 'Connexion SSL à cas.unilim.fr:443',
    'status' => $ssl_works,
    'value' => $ssl_works ? 'OK' : "Erreur: $errstr",
];

$ip = @gethostbyname('cas.unilim.fr');
$dns_works = ($ip !== 'cas.unilim.fr');

$checks['network_dns'] = [
    'name' => 'Résolution DNS (cas.unilim.fr)',
    'status' => $dns_works,
    'value' => $dns_works ? "$ip" : "Impossible",
];

// Calculer le score
$total = count($checks);
$passed = count(array_filter($checks, function($c) { return $c['status']; }));
$percentage = ($passed / $total) * 100;
$health = 'CRITIQUE';
if ($percentage >= 75) $health = 'BON';
elseif ($percentage >= 50) $health = 'MOYEN';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vérification Rapide Unilim SSO</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header { 
            background: white; 
            padding: 30px; 
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { margin: 0 0 10px 0; color: #333; }
        .health-badge { 
            display: inline-block;
            padding: 8px 16px; 
            border-radius: 20px; 
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        .health-BON { background: #28a745; color: white; }
        .health-MOYEN { background: #ffc107; color: #333; }
        .health-CRITIQUE { background: #dc3545; color: white; }
        
        .score { 
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .score-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: white;
        }
        .score-circle.ok { background: #28a745; }
        .score-circle.warning { background: #ffc107; }
        .score-circle.error { background: #dc3545; }
        
        .score-text h2 { margin: 0; font-size: 18px; }
        .score-text p { margin: 5px 0; color: #666; }
        
        .checks { background: white; padding: 20px; }
        .check { 
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .check:last-child { border-bottom: none; }
        
        .check-icon { 
            font-size: 24px;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }
        .check-content { flex: 1; }
        .check-name { font-weight: bold; color: #333; }
        .check-value { font-size: 12px; color: #999; margin-top: 4px; font-family: monospace; }
        .check-status { text-align: right; font-size: 12px; font-weight: bold; }
        
        .actions { 
            background: white;
            padding: 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        a, button {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }
        a { color: white; background: #667eea; }
        a:hover { background: #764ba2; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .alert { 
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        .alert-info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .alert-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .alert-danger { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .alert-success { background: #d4edda; border-color: #28a745; color: #155724; }
        
        .section-title {
            background: #f8f9fa;
            padding: 12px 20px;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #667eea;
            margin-top: 20px;
            margin-bottom: 0;
        }
        .section-title:first-of-type { margin-top: 0; }
        
        .problems { margin-top: 30px; }
        .problem { 
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
        }
        .problem h4 { margin: 0 0 10px 0; color: #dc3545; }
    </style>
</head>
<body>

<div class="container">
    <!-- Header avec Score -->
    <div class="header">
        <h1>🔍 Vérification Rapide Unilim SSO</h1>
        
        <div class="score">
            <div class="score-circle <?php echo $health === 'BON' ? 'ok' : ($health === 'MOYEN' ? 'warning' : 'error'); ?>">
                <?php echo round($percentage); ?>%
            </div>
            <div class="score-text">
                <h2>État du Système: <span class="health-badge health-<?php echo $health; ?>"><?php echo $health; ?></span></h2>
                <p><?php echo $passed; ?> / <?php echo $total; ?> vérifications réussies</p>
                <p style="font-size: 12px; margin-top: 10px;">
                    <?php
                    if ($health === 'BON') {
                        echo '✅ Le système semble correctement configuré';
                    } elseif ($health === 'MOYEN') {
                        echo '⚠️ Certaines vérifications ont échoué';
                    } else {
                        echo '❌ Problèmes critiques détectés';
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Checks -->
    <div class="checks">
        <div class="section-title">📋 Configuration</div>
        <?php foreach (array_slice($checks, 0, 4) as $id => $check): ?>
            <div class="check">
                <div class="check-icon"><?php echo $check['status'] ? '✅' : '❌'; ?></div>
                <div class="check-content">
                    <div class="check-name"><?php echo $check['name']; ?></div>
                    <div class="check-value"><?php echo htmlspecialchars($check['value']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="section-title">🔧 Extensions PHP</div>
        <?php foreach (array_slice($checks, 4, 3) as $id => $check): ?>
            <div class="check">
                <div class="check-icon"><?php echo $check['status'] ? '✅' : '⚠️'; ?></div>
                <div class="check-content">
                    <div class="check-name"><?php echo $check['name']; ?></div>
                    <div class="check-value"><?php echo htmlspecialchars($check['value']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="section-title">🌐 Réseau & Certificats</div>
        <?php foreach (array_slice($checks, 7) as $id => $check): ?>
            <div class="check">
                <div class="check-icon"><?php echo $check['status'] ? '✅' : '❌'; ?></div>
                <div class="check-content">
                    <div class="check-name"><?php echo $check['name']; ?></div>
                    <div class="check-value"><?php echo htmlspecialchars($check['value']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Problèmes et Solutions -->
    <?php 
    $critical_issues = [];
    
    if (!$checks['config_client_secret']['status']) {
        $critical_issues[] = [
            'title' => 'CLIENT_SECRET manquant',
            'solution' => 'Ajouter à api/.env: UNILIM_CLIENT_SECRET=votre_secret',
            'priority' => 'CRITIQUE'
        ];
    }
    
    if (!$checks['php_allow_url_fopen']['status']) {
        $critical_issues[] = [
            'title' => 'allow_url_fopen désactivé',
            'solution' => 'Activer dans php.ini: allow_url_fopen = On (puis redémarrer PHP)',
            'priority' => 'CRITIQUE'
        ];
    }
    
    if (!$checks['network_ssl_connection']['status']) {
        $critical_issues[] = [
            'title' => 'Impossible de se connecter à cas.unilim.fr en SSL',
            'solution' => 'Vérifier firewall, certificats CA, ou que le serveur Unilim est disponible',
            'priority' => 'CRITIQUE'
        ];
    }
    
    if (!$checks['network_dns']['status']) {
        $critical_issues[] = [
            'title' => 'Impossible de résoudre le domaine cas.unilim.fr',
            'solution' => 'Vérifier la connexion Internet et les serveurs DNS',
            'priority' => 'CRITIQUE'
        ];
    }
    
    if (count($critical_issues) > 0):
    ?>
        <div style="background: white; padding: 20px; margin-top: 0;">
            <h2 style="color: #dc3545; margin: 0 0 20px 0;">⚠️ Problèmes Détectés</h2>
            
            <?php foreach ($critical_issues as $issue): ?>
                <div class="alert alert-danger">
                    <strong><?php echo $issue['title']; ?></strong>
                    <br>
                    <small><?php echo $issue['solution']; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Actions Recommandées -->
    <div class="actions">
        <h3 style="margin-top: 0;">📖 Accédez aux outils de diagnostic</h3>
        
        <?php if ($passed == $total): ?>
            <div class="alert alert-success">
                ✅ <strong>Tous les contrôles sont passés!</strong>
                Si l'erreur 400 persiste, consultez les outils de diagnostic interactifs ci-dessous.
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="diagnostic-unilim-advanced.php">📊 Diagnostic Avancé</a>
            <a href="test-unilim-exchange.php">🧪 Test Interactif</a>
            <a href="../UNILIM_SSO_TROUBLESHOOTING.md" target="_blank" class="btn-secondary">📚 Guide Complet</a>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 12px; color: #666;">
            <strong>💡 Conseil:</strong> 
            <?php 
            if (!empty(UNILIM_CLIENT_SECRET)) {
                echo 'Aller sur la page "Test Interactif" pour tester l\'échange de code avec Unilim et voir exactement ce qui se passe.';
            } else {
                echo 'Ajouter d\'abord CLIENT_SECRET à .env, puis redémarrer le serveur. Ensuite, visitez le Test Interactif.';
            }
            ?>
        </div>
    </div>

</div>

</body>
</html>
