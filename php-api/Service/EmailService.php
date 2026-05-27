<?php

/**
 * EmailService
 * Gère l'envoi d'emails via Gmail SMTP
 * 
 * Utilise fsockopen pour connexion directe SMTP (pas de dépendances externes)
 */
class EmailService
{
    private string $fromEmail = 'benoitccasibio@gmail.com';
    private string $fromName = 'CVTEK';
    private string $smtpHost = 'smtp.gmail.com';
    private int $smtpPort = 587;
    private string $username = 'benoitccasibio@gmail.com';
    // Code app Gmail: aiwa chtc dfio ihsi (remplacer les espaces par rien)
    private string $password = 'aiwachtcdfioihsi';
    
    /**
     * Envoie un email de notification de nouveau fichier/URL
     * Retourne un array avec infos de succès/erreur
     */
    public function sendNewDocumentNotification(
        string $studentEmail,
        string $studentName,
        string $studentDocument,
        array $profEmails
    ): array {
        error_log("========================================");
        error_log("[EMAIL] 📧 NOTIFICATION NOUVEAU DOCUMENT");
        error_log("[EMAIL] Étudiant: $studentName ($studentEmail)");
        error_log("[EMAIL] Document: $studentDocument");
        error_log("[EMAIL] Destinataires (" . count($profEmails) . "): " . implode(', ', $profEmails));
        error_log("========================================");
        
        $subject = "Nouveau fichier/URL ajouté par $studentName";
        $body = $this->buildDocumentNotificationBody($studentName, $studentDocument);
        
        return $this->sendToMultiple($profEmails, $subject, $body);
    }
    
    /**
     * Envoie un email de notification de nouveau commentaire
     * Retourne un array avec détails du succès/erreur
     */
    public function sendNewCommentNotification(
        string $studentEmail,
        string $studentName,
        string $professorName,
        string $documentTitle,
        string $comment
    ): array {
        error_log("========================================");
        error_log("[EMAIL] 💬 NOUVEAU COMMENTAIRE");
        error_log("[EMAIL] Étudiant: $studentName → $studentEmail");
        error_log("[EMAIL] Professeur: $professorName");
        error_log("[EMAIL] Document: $documentTitle");
        error_log("[EMAIL] Commentaire: " . substr($comment, 0, 100) . (strlen($comment) > 100 ? '...' : ''));
        error_log("========================================");
        
        $subject = "Nouveau commentaire sur votre document";
        $body = $this->buildCommentNotificationBody(
            $studentName,
            $professorName,
            $documentTitle,
            $comment
        );
        
        return $this->sendEmail($studentEmail, $subject, $body);
    }
    
    /**
     * Envoie un email à plusieurs destinataires
     * Retourne un array avec infos de succès/erreur
     */
    private function sendToMultiple(array $recipients, string $subject, string $body): array
    {
        if (empty($recipients)) {
            error_log("[EMAIL] ❌ Aucun destinataire - email non envoyé");
            return [
                'success' => false,
                'error' => 'Aucun destinataire fourni'
            ];
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($recipients as $email) {
            error_log("[EMAIL] 📨 Envoi à: $email");
            $result = $this->sendEmail($email, $subject, $body);
            if ($result['success']) {
                $successCount++;
            } else {
                $errors[] = $email . ': ' . ($result['error'] ?? 'erreur inconnue');
            }
        }
        
        $message = "$successCount/" . count($recipients) . " emails envoyés";
        error_log("[EMAIL] ✅ Résultat: $message");
        
        if ($successCount > 0) {
            return [
                'success' => true,
                'details' => $message
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Impossible d\'envoyer les emails: ' . implode('; ', $errors)
            ];
        }
    }
    
    /**
     * Envoie un email unique
     * Retourne un array {success: bool, error?: string}
     */
    private function sendEmail(string $to, string $subject, string $body): array
    {
        // Sur Windows, essayer PHP mail() d'abord (plus fiable)
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows && function_exists('mail')) {
            $result = $this->sendViaPhpMail($to, $subject, $body);
            if ($result['success']) {
                return $result;
            }
            error_log("[EMAIL] ⚠️  mail() a échoué, tentative SMTP...");
        }
        
        // Essayer SMTP
        $smtpResult = $this->sendViaSMTP($to, $subject, $body);
        if ($smtpResult['success']) {
            return $smtpResult;
        }
        
        // Sur non-Windows, fallback sur mail()
        if (!$isWindows && function_exists('mail')) {
            error_log("[EMAIL] ⚠️  SMTP a échoué, tentative mail()...");
            return $this->sendViaPhpMail($to, $subject, $body);
        }
        
        return $smtpResult;
    }
    
    /**
     * Envoie via PHP mail()
     */
    private function sendViaPhpMail(string $to, string $subject, string $body): array
    {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        
        try {
            error_log("[EMAIL] 📤 Tentative envoi via mail() à: $to");
            
            // Vérifier la configuration de mail()
            $sendmailPath = ini_get('sendmail_path');
            $smtpHost = ini_get('SMTP');
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            
            if ($isWindows && !$smtpHost) {
                error_log("[EMAIL] ⚠️  INFO: Windows détecté, pas d'SMTP configuré");
                error_log("[EMAIL]    mail() peut ne pas fonctionner sans configuration SMTP");
                error_log("[EMAIL]    À configurer dans php.ini: SMTP=smtp.gmail.com, smtp_port=587");
            }
            
            if (!$isWindows && !$sendmailPath) {
                error_log("[EMAIL] ⚠️  AVERTISSEMENT: sendmail_path non configuré");
            }
            
            $result = @mail($to, $subject, $body, $headers);
            if ($result) {
                error_log("[EMAIL] ✅ Email ENVOYÉ avec succès à: $to");
                error_log("[EMAIL]    Sujet: $subject");
                return [
                    'success' => true,
                    'method' => 'php_mail'
                ];
            } else {
                error_log("[EMAIL] ❌ ERREUR: mail() a retourné false pour: $to");
                error_log("[EMAIL]    Cause probable sur Windows: SMTP non configuré");
                error_log("[EMAIL]    Cause probable sur Linux: sendmail non disponible");
                return [
                    'success' => false,
                    'error' => 'mail() a échoué - Vérifiez la configuration sendmail/SMTP',
                    'method' => 'php_mail'
                ];
            }
        } catch (Exception $e) {
            error_log("[EMAIL] ❌ EXCEPTION mail(): " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'method' => 'php_mail'
            ];
        }
    }
    
    /**
     * Envoie via SMTP direct (sans dépendances)
     */
    private function sendViaSMTP(string $to, string $subject, string $body): array
    {
        try {
            error_log("[EMAIL] 📤 Tentative envoi via SMTP à: $to");
            
            // Vérifier la disponibilité d'OpenSSL
            if (!extension_loaded('openssl')) {
                error_log("[EMAIL] ❌ ERREUR: Extension OpenSSL non disponible");
                error_log("[EMAIL]    Activez OpenSSL dans php.ini: extension=openssl");
                return [
                    'success' => false,
                    'error' => 'Extension OpenSSL requise - vérifiez php.ini',
                    'method' => 'smtp'
                ];
            }
            
            // Créer une connexion SMTP
            error_log("[EMAIL] 🔗 Connexion à {$this->smtpHost}:{$this->smtpPort}...");
            $sock = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 10);
            
            if (!$sock) {
                error_log("[EMAIL] ❌ ERREUR: Impossible de se connecter à SMTP: $errstr ($errno)");
                error_log("[EMAIL]    Causes possibles:");
                error_log("[EMAIL]    • Firewall/pare-feu bloque le port 587");
                error_log("[EMAIL]    • Serveur SMTP non accessible");
                error_log("[EMAIL]    • Problème de connectivité réseau");
                return [
                    'success' => false,
                    'error' => "Connexion SMTP échouée: $errstr ($errno)",
                    'method' => 'smtp'
                ];
            }
            error_log("[EMAIL] ✅ Connexion établie");
            
            // Lire la réponse du serveur
            $response = fgets($sock, 512);
            if (strpos($response, '220') === false) {
                fclose($sock);
                error_log("[EMAIL] ❌ Réponse SMTP invalide: $response");
                return [
                    'success' => false,
                    'error' => "Réponse SMTP invalide: $response",
                    'method' => 'smtp'
                ];
            }
            error_log("[EMAIL] ✅ Serveur prêt");
            
            // EHLO
            error_log("[EMAIL] 🤝 Envoi EHLO...");
            $this->writeCommand($sock, "EHLO cvtek.local");
            $this->readResponse($sock);
            
            // STARTTLS
            error_log("[EMAIL] 🔒 Activation STARTTLS...");
            $this->writeCommand($sock, "STARTTLS");
            $this->readResponse($sock);
            
            // Activer encryption TLS
            $tlsActive = false;
            
            // Essayer différentes constantes TLS disponibles
            if (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
                error_log("[EMAIL] 🔐 Utilisation STREAM_CRYPTO_METHOD_TLS_CLIENT");
                $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            } elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                error_log("[EMAIL] 🔐 Utilisation STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT");
                $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            } else {
                error_log("[EMAIL] ⚠️  Aucune constante TLS disponible, tentative avec valeur 4 (TLS 1.2)");
                $tlsActive = @stream_socket_enable_crypto($sock, true, 4);
            }
            
            if (!$tlsActive) {
                fclose($sock);
                error_log("[EMAIL] ❌ ERREUR: Impossible d'activer TLS");
                error_log("[EMAIL] ⚠️  Vérifiez que OpenSSL est correctement configuré");
                error_log("[EMAIL]    On Windows: extension=php_openssl.dll doit être dans php.ini");
                error_log("[EMAIL]    Sur Linux: openssl extension doit être installée");
                return [
                    'success' => false,
                    'error' => 'Impossible d\'activer TLS/SSL - Vérifiez OpenSSL',
                    'method' => 'smtp'
                ];
            }
            error_log("[EMAIL] ✅ TLS activé");
            
            // AUTH LOGIN
            error_log("[EMAIL] 🔐 Authentification...");
            $this->writeCommand($sock, "AUTH LOGIN");
            $this->readResponse($sock);
            
            // Envoyer username encodé en base64
            $this->writeCommand($sock, base64_encode($this->username));
            $this->readResponse($sock);
            
            // Envoyer password encodé en base64
            $this->writeCommand($sock, base64_encode($this->password));
            $response = $this->readResponse($sock);
            
            if (strpos($response, '235') === false) {
                fclose($sock);
                error_log("[EMAIL] ❌ ERREUR: Authentification échouée");
                error_log("[EMAIL]    Vérifiez les identifiants Gmail");
                error_log("[EMAIL]    Email: benoitccasibio@gmail.com");
                error_log("[EMAIL]    Code app: aiwachtcdfioihsi");
                return [
                    'success' => false,
                    'error' => 'Authentification Gmail SMTP échouée - Vérifiez les identifiants',
                    'method' => 'smtp'
                ];
            }
            error_log("[EMAIL] ✅ Authentification réussie");
            
            // MAIL FROM
            error_log("[EMAIL] 📧 Expéditeur: {$this->fromEmail}");
            $this->writeCommand($sock, "MAIL FROM:<{$this->fromEmail}>");
            $this->readResponse($sock);
            
            // RCPT TO
            error_log("[EMAIL] 📨 Destinataire: $to");
            $this->writeCommand($sock, "RCPT TO:<$to>");
            $this->readResponse($sock);
            
            // DATA
            $this->writeCommand($sock, "DATA");
            $this->readResponse($sock);
            
            // Construire les headers et le body
            $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            $message = $headers . $body . "\r\n.\r\n";
            
            error_log("[EMAIL] 📝 Envoi du contenu...");
            fwrite($sock, $message);
            $this->readResponse($sock);
            
            // QUIT
            $this->writeCommand($sock, "QUIT");
            fclose($sock);
            
            error_log("[EMAIL] ✅ Email ENVOYÉ avec succès à: $to");
            error_log("[EMAIL]    Sujet: $subject");
            return [
                'success' => true,
                'method' => 'smtp'
            ];
            
        } catch (Exception $e) {
            error_log("[EMAIL] ❌ EXCEPTION SMTP: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception SMTP: ' . $e->getMessage(),
                'method' => 'smtp'
            ];
        }
    }
    
    /**
     * Écrit une commande au socket
     */
    private function writeCommand($sock, string $command): void
    {
        fwrite($sock, $command . "\r\n");
    }
    
    /**
     * Lit une réponse du socket
     */
    private function readResponse($sock): string
    {
        $response = '';
        while ($line = fgets($sock, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }
    
    /**
     * Construit le corps de l'email de notification de document
     */
    private function buildDocumentNotificationBody(string $studentName, string $studentDocument): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
        .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: white; padding: 20px; border-radius: 0 0 5px 5px; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Notification CVTEK</h2>
        </div>
        <div class="content">
            <p>L'étudiant <strong>$studentName</strong> a ajouté un nouveau fichier/URL :</p>
            <p><strong>Document :</strong> $studentDocument</p>
            <p>Connectez-vous à CVTEK pour consulter le détail.</p>
        </div>
        <div class="footer">
            <p>CVTEK - Système de gestion des documents</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Construit le corps de l'email de notification de commentaire
     */
    private function buildCommentNotificationBody(
        string $studentName,
        string $professorName,
        string $documentTitle,
        string $comment
    ): string {
        $safeComment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
        .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: white; padding: 20px; border-radius: 0 0 5px 5px; }
        .comment-box { background-color: #f5f5f5; padding: 10px; border-left: 4px solid #0066cc; margin: 15px 0; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Nouveau Commentaire - CVTEK</h2>
        </div>
        <div class="content">
            <p>Bonjour <strong>$studentName</strong>,</p>
            <p>Le professeur <strong>$professorName</strong> a ajouté un commentaire sur votre document :</p>
            <p><strong>Document :</strong> $documentTitle</p>
            <p><strong>Commentaire :</strong></p>
            <div class="comment-box">
                $safeComment
            </div>
            <p>Connectez-vous à CVTEK pour voir le détail complet.</p>
        </div>
        <div class="footer">
            <p>CVTEK - Système de gestion des documents</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Test de configuration email
     */
    public function testConnection(): bool
    {
        $result = $this->sendEmail(
            $this->fromEmail,
            "Test CVTEK",
            "Ce mail teste la connexion email de CVTEK."
        );
        return $result['success'] ?? false;
    }
}

