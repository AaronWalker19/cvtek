<?php
/**
 * Test simple de connexion SMTP Gmail
 * Pour déboguer les identifiants
 */

$smtpHost = 'smtp.gmail.com';
$smtpPort = 587;
$username = 'benoitccasibio@gmail.com';
$password = 'aiwachtcdfioihsi'; // Code app sans espaces

echo "=== TEST SMTP GMAIL ===\n";
echo "Hôte: $smtpHost:$smtpPort\n";
echo "Username: $username\n";
echo "Password length: " . strlen($password) . "\n";
echo "\n";

// Connexion
echo "[1] Connexion à $smtpHost:$smtpPort...\n";
$sock = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);

if (!$sock) {
    echo "❌ ERREUR: Impossible de se connecter: $errstr ($errno)\n";
    exit(1);
}
echo "✅ Connexion établie\n";

// Lire réponse serveur
$response = fgets($sock, 512);
echo "[2] Réponse serveur: " . trim($response);
echo "\n";

// EHLO
echo "[3] Envoi EHLO...\n";
fwrite($sock, "EHLO localhost\r\n");
fflush($sock);
usleep(100000);
$response = fgets($sock, 512);
echo "Réponse: " . trim($response);
while (substr($response, 3, 1) !== ' ') {
    $response = fgets($sock, 512);
    echo trim($response) . "\n";
}
echo "\n";

// STARTTLS
echo "[4] Activation STARTTLS...\n";
fwrite($sock, "STARTTLS\r\n");
fflush($sock);
usleep(100000);
$response = fgets($sock, 512);
echo "Réponse: " . trim($response) . "\n\n";

// Activer TLS
echo "[5] Activation du chiffrement TLS...\n";
if (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
    $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
} else {
    $tlsActive = @stream_socket_enable_crypto($sock, true, 4);
}
echo ($tlsActive ? "✅ TLS activé\n" : "❌ TLS échoué\n");
echo "\n";

// EHLO après TLS
echo "[6] Envoi EHLO après TLS...\n";
fwrite($sock, "EHLO localhost\r\n");
fflush($sock);
usleep(100000);
$response = fgets($sock, 512);
echo "Réponse: " . trim($response) . "\n\n";

// AUTH LOGIN
echo "[7] Demande d'authentification...\n";
fwrite($sock, "AUTH LOGIN\r\n");
fflush($sock);
usleep(100000);
$response = fgets($sock, 512);
echo "Réponse: " . trim($response) . "\n";

// Envoyer username en base64
echo "[8] Envoi du username en base64...\n";
$b64Username = base64_encode($username);
echo "Username encodé: $b64Username\n";
fwrite($sock, $b64Username . "\r\n");
fflush($sock);
usleep(100000);
$response = fgets($sock, 512);
echo "Réponse: " . trim($response) . "\n";

// Envoyer password en base64
echo "[9] Envoi du password en base64...\n";
$b64Password = base64_encode($password);
echo "Password encodé: $b64Password\n";
echo "Password original: $password\n";
echo "Password original length: " . strlen($password) . "\n";
fwrite($sock, $b64Password . "\r\n");
fflush($sock);
usleep(100000);
$response = '';
while ($line = fgets($sock, 512)) {
    $response .= $line;
    if (substr($line, 3, 1) === ' ') {
        break;
    }
}
echo "Réponse serveur:\n" . $response . "\n";

if (strpos($response, '235') !== false) {
    echo "\n✅ AUTHENTIFICATION RÉUSSIE!\n";
} elseif (strpos($response, '535') !== false) {
    echo "\n❌ AUTHENTIFICATION ÉCHOUÉE (535)\n";
    echo "Identifiants rejetés par Gmail\n";
    echo "Vérifiez:\n";
    echo "1. L'authentification à deux facteurs est activée\n";
    echo "2. Un mot de passe d'application a été créé\n";
    echo "3. Vous utilisez le mot de passe d'application, pas le mot de passe Gmail\n";
} else {
    echo "\n❌ ERREUR: " . trim($response) . "\n";
}

fwrite($sock, "QUIT\r\n");
fclose($sock);
echo "\n✅ Connexion fermée\n";
