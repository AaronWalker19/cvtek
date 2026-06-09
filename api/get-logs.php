<?php
/**
 * API pour récupérer les logs de débogage
 */
header('Content-Type: application/json');

if (isset($_GET['clear'])) {
    // Effacer les logs
    $logFile = ini_get('error_log');
    if ($logFile && file_exists($logFile)) {
        file_put_contents($logFile, '');
    }
    echo json_encode(['success' => true]);
    exit;
}

// Lire les logs
$logFile = ini_get('error_log');
$logs = [];

if ($logFile && file_exists($logFile)) {
    $content = file_get_contents($logFile);
    // Garder seulement les 100 dernières lignes et filtrer pour CVTEK/Unilim
    $lines = array_reverse(explode("\n", $content));
    $count = 0;
    foreach ($lines as $line) {
        if ($count >= 100) break;
        if (empty(trim($line))) continue;
        // Filtrer pour nos logs uniquement (ceux qui contiennent nos marqueurs)
        if (strpos($line, '🔄') !== false || 
            strpos($line, '✅') !== false || 
            strpos($line, '❌') !== false ||
            strpos($line, 'UNILIM') !== false ||
            strpos($line, 'CALLBACK') !== false ||
            strpos($line, 'POST à') !== false) {
            $logs[] = trim($line);
            $count++;
        }
    }
}

// Inverser pour avoir l'ordre chronologique
$logs = array_reverse($logs);

echo json_encode([
    'logs' => $logs,
    'log_file' => $logFile,
    'total' => count($logs)
]);
