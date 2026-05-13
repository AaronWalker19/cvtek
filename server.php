<?php

// Chemin vers Node.js et le fichier server.js
$nodePath = 'node'; // Assurez-vous que Node.js est dans le PATH ou spécifiez le chemin complet
$serverPath = __DIR__ . DIRECTORY_SEPARATOR . 'server.js';

// Vérifier si le serveur est déjà en cours d'exécution
$serverRunning = false;
$port = getenv('PORT') ?: 3000;

$connection = @fsockopen('localhost', $port);
if ($connection) {
    $serverRunning = true;
    fclose($connection);
}

if (!$serverRunning) {
    // Démarrer le serveur Node.js
    $command = "$nodePath $serverPath > server.log 2>&1 &";
    exec($command, $output, $returnVar);

    if ($returnVar === 0) {
        echo "Le serveur Node.js a été démarré avec succès sur le port $port.";
    } else {
        echo "Erreur lors du démarrage du serveur Node.js. Vérifiez les logs.";
    }
} else {
    echo "Le serveur Node.js est déjà en cours d'exécution sur le port $port.";
}

?>