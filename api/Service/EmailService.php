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
    private array $logs = [];
    private bool $debugMode = false; // Mode debug (logs détaillés)
    
    /**
     * Constructeur
     * @param bool $debugMode Afficher tous les logs (default: false = seulement erreurs)
     */
    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
    }
    
    /**
     * Ajoute un log au tableau (avec filtrage du debug mode)
     */
    private function addLog(string $message, bool $isError = false): void
    {
        // Toujours ajouter les erreurs ET les logs debug si activé
        if ($isError || $this->debugMode) {
            $this->logs[] = $message;
        }
        
        // Toujours écrire les erreurs dans error_log
        if ($isError) {
            error_log($message);
        }
    }
    
    /**
     * Retourne tous les logs collectés
     */
    public function getLogs(): array
    {
        return $this->logs;
    }
    
    /**
     * Envoie un email de notification de nouveau fichier/URL
     * Retourne un array avec infos de succès/erreur
     * Utilise la même approche que les commentaires: mail() d'abord (fonctionne!)
     * Envoie individuellement à chaque professeur
     */
    public function sendNewDocumentNotification(
        string $studentEmail,
        string $studentName,
        string $studentDocument,
        array $profEmails
    ): array {
        $this->addLog("========================================");
        $this->addLog("[EMAIL] 📧 NOTIFICATION NOUVEAU DOCUMENT");
        $this->addLog("[EMAIL] Étudiant: $studentName ($studentEmail)");
        $this->addLog("[EMAIL] Document: $studentDocument");
        $this->addLog("[EMAIL] Destinataires (" . count($profEmails) . "): " . implode(', ', $profEmails));
        $this->addLog("========================================");
        
        if (empty($profEmails)) {
            $this->addLog("[EMAIL] ❌ Aucun destinataire - email non envoyé");
            $result = [
                'success' => false,
                'error' => 'Aucun destinataire fourni',
                'logs' => $this->getLogs()
            ];
            return $result;
        }
        
        $subject = "Nouveau fichier/URL ajouté par $studentName";
        $body = $this->buildDocumentNotificationBody($studentName, $studentDocument);
        
        // Utiliser la MÊME approche que sendNewCommentNotification: mail() d'abord (ça FONCTIONNE!)
        // Envoyer individuellement à chaque professeur
        $this->addLog("[EMAIL] 📧 " . count($profEmails) . " destinataire(s), envoi individuel (mail() en priorité)");
        
        $successCount = 0;
        $sentEmails = [];
        $errors = [];
        
        foreach ($profEmails as $email) {
            $this->addLog("[EMAIL] 📨 Envoi à professeur: $email");
            $result = $this->sendEmail($email, $subject, $body);
            if ($result['success']) {
                $successCount++;
                $sentEmails[] = $email;
                $this->addLog("[EMAIL] ✅ Email envoyé à: $email");
            } else {
                $errors[] = $email . ': ' . ($result['error'] ?? 'erreur inconnue');
                $this->addLog("[EMAIL] ❌ Échec envoi à: $email");
            }
        }
        
        if ($successCount > 0) {
            $this->addLog("[EMAIL] ✅ DOCUMENT NOTIFICATION: " . $successCount . "/" . count($profEmails) . " emails envoyés");
            return [
                'success' => true,
                'details' => $successCount . "/" . count($profEmails) . " destinataires",
                'recipients_count' => count($profEmails),
                'sent_count' => $successCount,
                'sent_emails' => $sentEmails,
                'logs' => $this->getLogs()
            ];
        } else {
            $this->addLog("[EMAIL] ❌ DOCUMENT NOTIFICATION: Aucun email envoyé");
            return [
                'success' => false,
                'error' => 'Impossible d\'envoyer les emails: ' . implode('; ', $errors),
                'recipients_count' => count($profEmails),
                'sent_count' => 0,
                'logs' => $this->getLogs()
            ];
        }
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
        $this->addLog("========================================");
        $this->addLog("[EMAIL] 💬 NOUVEAU COMMENTAIRE");
        $this->addLog("[EMAIL] Étudiant: $studentName → $studentEmail");
        $this->addLog("[EMAIL] Professeur: $professorName");
        $this->addLog("[EMAIL] Document: $documentTitle");
        $this->addLog("[EMAIL] Commentaire: " . substr($comment, 0, 100) . (strlen($comment) > 100 ? '...' : ''));
        $this->addLog("========================================");
        
        $subject = "Nouveau commentaire sur votre document";
        $body = $this->buildCommentNotificationBody(
            $studentName,
            $professorName,
            $documentTitle,
            $comment
        );
        
        $result = $this->sendEmail($studentEmail, $subject, $body);
        $result['logs'] = $this->getLogs();
        return $result;
    }
    
    /**
     * Envoie un email à plusieurs destinataires (une seule connexion SMTP)
     * Retourne un array avec infos de succès/erreur
     */
    private function sendToMultiple(array $recipients, string $subject, string $body): array
    {
        if (empty($recipients)) {
            $this->addLog("[EMAIL] ❌ Aucun destinataire - email non envoyé");
            return [
                'success' => false,
                'error' => 'Aucun destinataire fourni'
            ];
        }
        
        // Envoyer UN SEUL email avec plusieurs destinataires (1 connexion SMTP)
        $result = $this->sendViaSMTPMultiple($recipients, $subject, $body);
        
        if ($result['success']) {
            $sentEmails = $result['sent_emails'] ?? [];
            return [
                'success' => true,
                'details' => count($sentEmails) . "/" . count($recipients) . " destinataires",
                'recipients_count' => count($recipients),
                'sent_count' => count($sentEmails),
                'sent_emails' => $sentEmails
            ];
        }
        
        // Fallback: essayer mail() si SMTP échoue
        $this->addLog("[EMAIL] ⚠️  SMTP a échoué, tentative mail()...");
        $successCount = 0;
        $errors = [];
        $sentEmails = [];
        
        foreach ($recipients as $email) {
            $this->addLog("[EMAIL] 📨 Envoi à: $email");
            $mailResult = $this->sendViaPhpMail($email, $subject, $body);
            if ($mailResult['success']) {
                $successCount++;
                $sentEmails[] = $email;
            } else {
                $errors[] = $email . ': ' . ($mailResult['error'] ?? 'erreur inconnue');
            }
        }
        
        if ($successCount > 0) {
            return [
                'success' => true,
                'details' => $successCount . "/" . count($recipients) . " emails envoyés",
                'recipients_count' => count($recipients),
                'sent_count' => $successCount,
                'sent_emails' => $sentEmails
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Impossible d\'envoyer les emails: ' . implode('; ', $errors),
                'recipients_count' => count($recipients),
                'sent_count' => 0
            ];
        }
    }
    
    /**
     * Envoie 1 seul email avec destinataires en BCC (plus efficace)
     * Retourne un array avec infos de succès/erreur
     */
    private function sendViaBcc(array $bccRecipients, string $subject, string $body): array
    {
        if (empty($bccRecipients)) {
            $this->addLog("[EMAIL] ❌ Aucun destinataire pour BCC");
            return [
                'success' => false,
                'error' => 'Aucun destinataire fourni'
            ];
        }
        
        $this->addLog("[EMAIL] 📤 Envoi 1 email BCC via SMTP d'abord...");
        $smtpResult = $this->sendViaSMTPWithBcc($bccRecipients, $subject, $body);
        
        if ($smtpResult['success']) {
            return $smtpResult;
        }
        
        $this->addLog("[EMAIL] ⚠️  SMTP a échoué, tentative mail() avec BCC...");
        
        // Fallback sur mail() avec BCC header
        if (function_exists('mail')) {
            return $this->sendViaPhpMailWithBcc($bccRecipients, $subject, $body);
        }
        
        return $smtpResult;
    }
    
    /**
     * Envoie 1 email BCC via SMTP direct
     */
    private function sendViaSMTPWithBcc(array $bccRecipients, string $subject, string $body): array
    {
        try {
            $this->addLog("[EMAIL] 📤 Tentative SMTP BCC pour " . count($bccRecipients) . " destinataire(s)...");
            
            // Vérifier la disponibilité d'OpenSSL
            if (!extension_loaded('openssl')) {
                $this->addLog("[EMAIL] ❌ ERREUR: Extension OpenSSL non disponible");
                return [
                    'success' => false,
                    'error' => 'Extension OpenSSL requise'
                ];
            }
            
            // Créer une connexion SMTP
            $this->addLog("[EMAIL] 🔗 Connexion à {$this->smtpHost}:{$this->smtpPort}...");
            $sock = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 10);
            
            if (!$sock) {
                $this->addLog("[EMAIL] ❌ ERREUR: Impossible de se connecter à SMTP: $errstr ($errno)");
                return [
                    'success' => false,
                    'error' => "Connexion SMTP échouée: $errstr ($errno)"
                ];
            }
            $this->addLog("[EMAIL] ✅ Connexion établie");
            
            // Lire la réponse du serveur
            $response = fgets($sock, 512);
            if (strpos($response, '220') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Réponse SMTP invalide");
                return [
                    'success' => false,
                    'error' => "Réponse SMTP invalide: $response"
                ];
            }
            $this->addLog("[EMAIL] ✅ Serveur prêt");
            
            // EHLO
            $this->writeCommand($sock, "EHLO cvtek.local");
            $ehloResponse = $this->readResponse($sock);
            if (strpos($ehloResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: EHLO rejeté");
                return [
                    'success' => false,
                    'error' => "EHLO rejeté: $ehloResponse"
                ];
            }
            $this->addLog("[EMAIL] ✅ EHLO accepté");
            
            // STARTTLS
            $this->addLog("[EMAIL] 🔐 Activation STARTTLS...");
            $this->writeCommand($sock, "STARTTLS");
            $starttlsResponse = $this->readResponse($sock);
            if (strpos($starttlsResponse, '220') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ STARTTLS rejeté");
                return [
                    'success' => false,
                    'error' => "STARTTLS rejeté: $starttlsResponse"
                ];
            }
            $this->addLog("[EMAIL] ✅ STARTTLS accepté");
            
            // Activer TLS
            $tlsActive = false;
            if (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
                $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            } elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            } else {
                $tlsActive = @stream_socket_enable_crypto($sock, true, 4);
            }
            
            if (!$tlsActive) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Impossible d'activer TLS");
                return [
                    'success' => false,
                    'error' => 'Impossible d\'activer TLS'
                ];
            }
            $this->addLog("[EMAIL] ✅ TLS activé");
            
            // EHLO après TLS
            $this->writeCommand($sock, "EHLO cvtek.local");
            $ehloResponse2 = $this->readResponse($sock);
            if (strpos($ehloResponse2, '250') === false) {
                fclose($sock);
                return [
                    'success' => false,
                    'error' => "EHLO (après TLS) rejeté"
                ];
            }
            $this->addLog("[EMAIL] ✅ EHLO accepté (après TLS)");
            
            // AUTH LOGIN
            $this->addLog("[EMAIL] 🔐 Authentification...");
            $this->writeCommand($sock, "AUTH LOGIN");
            $authResponse = $this->readResponse($sock);
            if (strpos($authResponse, '334') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Authentification non demandée");
                return [
                    'success' => false,
                    'error' => "Authentification non demandée"
                ];
            }
            
            // Envoyer username
            $this->writeCommand($sock, base64_encode($this->username));
            $userResponse = $this->readResponse($sock);
            if (strpos($userResponse, '334') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Password non demandé");
                return [
                    'success' => false,
                    'error' => "Password non demandé"
                ];
            }
            
            // Envoyer password
            $this->writeCommand($sock, base64_encode($this->password));
            $response = $this->readResponse($sock);
            
            if (strpos($response, '235') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Authentification échouée");
                return [
                    'success' => false,
                    'error' => 'Authentification échouée'
                ];
            }
            $this->addLog("[EMAIL] ✅ Authentification réussie");
            
            // MAIL FROM
            $this->writeCommand($sock, "MAIL FROM:<{$this->fromEmail}>");
            $mailFromResponse = $this->readResponse($sock);
            if (strpos($mailFromResponse, '250') === false) {
                fclose($sock);
                return [
                    'success' => false,
                    'error' => "MAIL FROM rejeté"
                ];
            }
            $this->addLog("[EMAIL] ✅ Expéditeur accepté");
            
            // RCPT TO pour CHAQUE BCC (le serveur ne voit que ça, pas les headers)
            $successCount = 0;
            foreach ($bccRecipients as $email) {
                $this->addLog("[EMAIL] 📨 Destinataire: $email");
                $this->writeCommand($sock, "RCPT TO:<$email>");
                $rcptToResponse = $this->readResponse($sock);
                if (strpos($rcptToResponse, '250') !== false) {
                    $successCount++;
                    $this->addLog("[EMAIL] ✅ Destinataire accepté: $email");
                } else {
                    $this->addLog("[EMAIL] ⚠️  Destinataire rejeté: $email");
                }
            }
            
            if ($successCount === 0) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Aucun destinataire accepté");
                return [
                    'success' => false,
                    'error' => 'Aucun destinataire accepté'
                ];
            }
            
            // DATA
            $this->writeCommand($sock, "DATA");
            $dataResponse = $this->readResponse($sock);
            if (strpos($dataResponse, '354') === false) {
                fclose($sock);
                return [
                    'success' => false,
                    'error' => "DATA rejeté"
                ];
            }
            
            // Construire les headers avec BCC en tant qu'en-tête
            // Note: Le BCC header dans les headers HTML ne s'affiche PAS aux destinataires
            // mais le serveur SMTP les reçoit via RCPT TO
            $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $headers .= "To: {$this->fromEmail}\r\n"; // Destinataire apparent (on peut aussi laisser vide)
            $headers .= "Bcc: " . implode(', ', $bccRecipients) . "\r\n"; // BCC pour le log
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            $message = $headers . $body . "\r\n.\r\n";
            
            $this->addLog("[EMAIL] 📏 Envoi du contenu à " . $successCount . " destinataire(s)...");
            fwrite($sock, $message);
            $submitResponse = $this->readResponse($sock);
            
            if (strpos($submitResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Message rejeté");
                return [
                    'success' => false,
                    'error' => "Message rejeté: $submitResponse"
                ];
            }
            $this->addLog("[EMAIL] ✅ Serveur a accepté le message");
            
            // QUIT
            $this->writeCommand($sock, "QUIT");
            fclose($sock);
            
            $this->addLog("[EMAIL] ✅ Email BCC ENVOYÉ avec succès");
            $this->addLog("[EMAIL]    Sujet: $subject");
            $this->addLog("[EMAIL]    Destinataires: " . count($bccRecipients));
            
            return [
                'success' => true,
                'method' => 'smtp_bcc',
                'recipients_count' => count($bccRecipients),
                'sent_count' => $successCount
            ];
            
        } catch (Exception $e) {
            $this->addLog("[EMAIL] ❌ EXCEPTION SMTP BCC: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception SMTP: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envoie via PHP mail() à chaque destinataire individuellement
     * (Le BCC header ne fonctionne pas correctement sur Windows)
     */
    private function sendViaPhpMailWithBcc(array $bccRecipients, string $subject, string $body): array
    {
        try {
            $this->addLog("[EMAIL] 📤 Tentative envoi mail() individuellement pour " . count($bccRecipients) . " destinataire(s)");
            $this->addLog("[EMAIL]    ⚠️  Note: BCC header ne fonctionne pas sur Windows, envoi individuel");
            
            $successCount = 0;
            $sentEmails = [];
            $errors = [];
            
            // Envoyer à chaque destinataire individuellement
            foreach ($bccRecipients as $email) {
                $this->addLog("[EMAIL] 📨 Envoi à: $email");
                
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
                $headers .= "Reply-To: {$this->fromEmail}\r\n";
                
                $result = @mail($email, $subject, $body, $headers);
                
                if ($result) {
                    $this->addLog("[EMAIL] ✅ Email envoyé à: $email");
                    $successCount++;
                    $sentEmails[] = $email;
                } else {
                    $this->addLog("[EMAIL] ❌ Échec envoi à: $email");
                    $errors[] = $email;
                }
            }
            
            if ($successCount > 0) {
                $this->addLog("[EMAIL] ✅ Email ENVOYÉ avec succès");
                $this->addLog("[EMAIL]    Sujet: $subject");
                $this->addLog("[EMAIL]    Résultat: " . $successCount . "/" . count($bccRecipients) . " destinataires");
                return [
                    'success' => true,
                    'method' => 'php_mail_individual',
                    'recipients_count' => count($bccRecipients),
                    'sent_count' => $successCount,
                    'sent_emails' => $sentEmails
                ];
            } else {
                $this->addLog("[EMAIL] ❌ Tous les envois mail() ont échoué");
                return [
                    'success' => false,
                    'error' => 'Tous les envois mail() ont échoué',
                    'method' => 'php_mail_individual',
                    'recipients_count' => count($bccRecipients),
                    'sent_count' => 0
                ];
            }
        } catch (Exception $e) {
            $this->addLog("[EMAIL] ❌ EXCEPTION mail() individuel: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception mail(): ' . $e->getMessage(),
                'method' => 'php_mail_individual'
            ];
        }
    }
    
    /**
     * Envoie un email unique
     * Retourne un array {success: bool, error?: string}
     */
    private function sendEmail(string $to, string $subject, string $body): array
    {
        // Stratégie intelligente sur Windows: essayer mail() en priorité
        // car SMTP nécessite OpenSSL qui n'est pas toujours disponible
        $this->addLog("[EMAIL] 🔍 Stratégie: Essayer mail() en priorité → Fallback SMTP");
        
        // Essayer mail() d'abord (fonctionne bien sur Windows avec SMTP relai)
        $this->addLog("[EMAIL] 📤 Tentative 1: Envoi via mail()...");
        $mailResult = $this->sendViaPhpMail($to, $subject, $body);
        
        if ($mailResult['success']) {
            $this->addLog("[EMAIL] ✅ Email envoyé avec succès via mail()");
            return $mailResult;
        }
        
        // Si mail() échoue, fallback à SMTP
        $this->addLog("[EMAIL] ⚠️  mail() a échoué, tentative 2: SMTP directe...");
        $smtpResult = $this->sendViaSMTP($to, $subject, $body);
        
        if ($smtpResult['success']) {
            $this->addLog("[EMAIL] ✅ Email envoyé avec succès via SMTP");
            return $smtpResult;
        }
        
        // Aucune méthode n'a fonctionné
        $this->addLog("[EMAIL] ❌ Les deux méthodes ont échoué (mail() et SMTP)");
        return [
            'success' => false,
            'error' => 'Impossible d\'envoyer l\'email via mail() ou SMTP'
        ];
    }
    
    /**
     * Envoie un email pour DOCUMENTS
     * Même approche que les commentaires: mail() d'abord (ça fonctionne!)
     * Fallback: SMTP seulement si mail() échoue
     */
    private function sendEmailForDocument(string $to, string $subject, string $body): array
    {
        // Utiliser la MÊME approche que sendEmail (mail() fonctionne pour les commentaires!)
        return $this->sendEmail($to, $subject, $body);
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
            $this->addLog("[EMAIL] 📤 Tentative envoi via mail() à: $to");
            
            // Vérifier la configuration de mail()
            $sendmailPath = ini_get('sendmail_path');
            $smtpHost = ini_get('SMTP');
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            
            if ($isWindows && !$smtpHost) {
                $this->addLog("[EMAIL] ⚠️  INFO: Windows détécté, pas d'SMTP configuré");
                $this->addLog("[EMAIL]    mail() peut ne pas fonctionner sans configuration SMTP");
                $this->addLog("[EMAIL]    À configurer dans php.ini: SMTP=smtp.gmail.com, smtp_port=587");
            }
            
            if (!$isWindows && !$sendmailPath) {
                $this->addLog("[EMAIL] ⚠️  AVERTISSEMENT: sendmail_path non configuré");
            }
            
            $result = @mail($to, $subject, $body, $headers);
            if ($result) {
                $this->addLog("[EMAIL] ✅ Email ENVOYÉ avec succès à: $to");
                $this->addLog("[EMAIL]    Sujet: $subject");
                return [
                    'success' => true,
                    'method' => 'php_mail'
                ];
            } else {
                $this->addLog("[EMAIL] ❌ ERREUR: mail() a retourné false pour: $to");
                $this->addLog("[EMAIL]    Cause probable sur Windows: SMTP non configuré");
                $this->addLog("[EMAIL]    Cause probable sur Linux: sendmail non disponible");
                return [
                    'success' => false,
                    'error' => 'mail() a échoué - Vérifiez la configuration sendmail/SMTP',
                    'method' => 'php_mail'
                ];
            }
        } catch (Exception $e) {
            $this->addLog("[EMAIL] ❌ EXCEPTION mail(): " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'method' => 'php_mail'
            ];
        }
    }
    
    /**
     * Envoie via SMTP direct à plusieurs destinataires (une seule connexion)
     */
    private function sendViaSMTPMultiple(array $recipients, string $subject, string $body): array
    {
        try {
            $this->addLog("[EMAIL] 📤 Tentative SMTP pour " . count($recipients) . " destinataire(s)...");
            
            // Vérifier la disponibilité d'OpenSSL
            if (!extension_loaded('openssl')) {
                $this->addLog("[EMAIL] ❌ ERREUR: Extension OpenSSL non disponible");
                return [
                    'success' => false,
                    'error' => 'Extension OpenSSL requise - vérifiez php.ini'
                ];
            }
            
            // Créer une connexion SMTP UNE SEULE FOIS
            $this->addLog("[EMAIL] 🔗 Connexion à {$this->smtpHost}:{$this->smtpPort}...");
            $sock = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 10);
            
            if (!$sock) {
                $this->addLog("[EMAIL] ❌ ERREUR: Impossible de se connecter à SMTP: $errstr ($errno)");
                return [
                    'success' => false,
                    'error' => "Connexion SMTP échouée: $errstr ($errno)"
                ];
            }
            $this->addLog("[EMAIL] ✅ Connexion établie");
            
            // Lire la réponse du serveur
            $response = fgets($sock, 512);
            if (strpos($response, '220') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Réponse SMTP invalide: $response");
                return [
                    'success' => false,
                    'error' => "Réponse SMTP invalide: $response"
                ];
            }
            $this->addLog("[EMAIL] ✅ Serveur prêt");
            
            // EHLO
            $this->addLog("[EMAIL] 🤝 Envoi EHLO...");
            $this->writeCommand($sock, "EHLO cvtek.local");
            $ehloResponse = $this->readResponse($sock);
            if (strpos($ehloResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté EHLO: $ehloResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté EHLO: $ehloResponse"
                ];
            }
            $this->addLog("[EMAIL] ✅ EHLO accepté");
            
            // STARTTLS
            $this->addLog("[EMAIL] 🔐 Activation STARTTLS...");
            $this->writeCommand($sock, "STARTTLS");
            $starttlsResponse = $this->readResponse($sock);
            if (strpos($starttlsResponse, '220') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté STARTTLS: $starttlsResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté STARTTLS: $starttlsResponse"
                ];
            }
            $this->addLog("[EMAIL] ✅ STARTTLS accepté");
            
            // Activer encryption TLS
            $tlsActive = false;
            if (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
                $this->addLog("[EMAIL] 🔐 Utilisation STREAM_CRYPTO_METHOD_TLS_CLIENT");
                $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            } elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $this->addLog("[EMAIL] 🔐 Utilisation STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT");
                $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            } else {
                $this->addLog("[EMAIL] ⚠️  Tentative TLS avec valeur 4");
                $tlsActive = @stream_socket_enable_crypto($sock, true, 4);
            }
            
            if (!$tlsActive) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Impossible d'activer TLS");
                return [
                    'success' => false,
                    'error' => 'Impossible d\'activer TLS/SSL - Vérifiez OpenSSL'
                ];
            }
            $this->addLog("[EMAIL] ✅ TLS activé");
            
            // EHLO A NOUVEAU après TLS (c'est obligatoire!)
            $this->addLog("[EMAIL] 🤝 Envoi EHLO à nouveau après TLS...");
            $this->writeCommand($sock, "EHLO cvtek.local");
            $ehloResponse2 = $this->readResponse($sock);
            if (strpos($ehloResponse2, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté EHLO (après TLS): $ehloResponse2");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté EHLO (après TLS): $ehloResponse2"
                ];
            }
            $this->addLog("[EMAIL] ✅ EHLO accepté (après TLS)");
            
            // AUTH LOGIN (UNE SEULE FOIS)
            $this->addLog("[EMAIL] 🔐 Authentification...");
            $this->addLog("[EMAIL] 📋 Identifiants: username=" . $this->username . " / password length=" . strlen($this->password));
            $this->writeCommand($sock, "AUTH LOGIN");
            $authResponse = $this->readResponse($sock);
            if (strpos($authResponse, '334') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP n'a pas demandé d'authentification: $authResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP: $authResponse"
                ];
            }
            
            // Envoyer username encodé en base64
            $this->addLog("[EMAIL] 📏 Envoi du username...");
            $this->writeCommand($sock, base64_encode($this->username));
            $userResponse = $this->readResponse($sock);
            if (strpos($userResponse, '334') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP n'a pas demandé le password: $userResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP: $userResponse"
                ];
            }
            
            // Envoyer password encodé en base64
            $this->addLog("[EMAIL] 📏 Envoi du password...");
            $this->writeCommand($sock, base64_encode($this->password));
            $response = $this->readResponse($sock);
            
            if (strpos($response, '235') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Authentification échouée");
                $this->addLog("[EMAIL]    Vérifiez les identifiants Gmail");
                $this->addLog("[EMAIL]    Email: benoitccasibio@gmail.com");
                $this->addLog("[EMAIL]    Code app: aiwachtcdfioihsi");
                return [
                    'success' => false,
                    'error' => 'Authentification Gmail SMTP échouée - Vérifiez les identifiants'
                ];
            }
            $this->addLog("[EMAIL] ✅ Authentification réussie");
            
            // MAIL FROM (UNE SEULE FOIS)
            $this->addLog("[EMAIL] 📧 Expéditeur: {$this->fromEmail}");
            $this->writeCommand($sock, "MAIL FROM:<{$this->fromEmail}>");
            $mailFromResponse = $this->readResponse($sock);
            if (strpos($mailFromResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté MAIL FROM: $mailFromResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté MAIL FROM: $mailFromResponse"
                ];
            }
            $this->addLog("[EMAIL] ✅ Expéditeur accepté");
            
            // RCPT TO pour CHAQUE destinataire
            $sentEmails = [];
            foreach ($recipients as $email) {
                $this->addLog("[EMAIL] 📨 Destinataire: $email");
                $this->writeCommand($sock, "RCPT TO:<$email>");
                $rcptToResponse = $this->readResponse($sock);
                if (strpos($rcptToResponse, '250') === false) {
                    $this->addLog("[EMAIL] ⚠️  ERREUR: Destinataire rejeté: $email ($rcptToResponse)");
                } else {
                    $this->addLog("[EMAIL] ✅ Destinataire accepté: $email");
                    $sentEmails[] = $email;
                }
            }
            
            if (empty($sentEmails)) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Aucun destinataire accepté");
                return [
                    'success' => false,
                    'error' => 'Aucun destinataire accepté par le serveur SMTP',
                    'sent_emails' => []
                ];
            }
            
            // DATA
            $this->writeCommand($sock, "DATA");
            $dataResponse = $this->readResponse($sock);
            if (strpos($dataResponse, '354') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP n'est pas prêt pour les données: $dataResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté DATA: $dataResponse",
                    'sent_emails' => $sentEmails
                ];
            }
            
            // Construire les headers
            $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $headers .= "To: " . implode(', ', $sentEmails) . "\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            $message = $headers . $body . "\r\n.\r\n";
            
            $this->addLog("[EMAIL] 📏 Envoi du contenu à " . count($sentEmails) . " destinataire(s)...");
            fwrite($sock, $message);
            $submitResponse = $this->readResponse($sock);
            
            // Vérifier le code 250 (OK - Message queued for delivery)
            if (strpos($submitResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté le message: $submitResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté le message: $submitResponse",
                    'sent_emails' => $sentEmails
                ];
            }
            $this->addLog("[EMAIL] ✅ Serveur a accepté le message (code 250)");
            
            // QUIT
            $this->writeCommand($sock, "QUIT");
            fclose($sock);
            
            $this->addLog("[EMAIL] ✅ Email ENVOYÉ avec succès à " . count($sentEmails) . " destinataire(s)");
            $this->addLog("[EMAIL]    Sujet: $subject");
            $this->addLog("[EMAIL] ✅ Résultat: " . count($sentEmails) . "/" . count($recipients) . " emails envoyés");
            
            return [
                'success' => true,
                'sent_emails' => $sentEmails,
                'method' => 'smtp'
            ];
            
        } catch (Exception $e) {
            $this->addLog("[EMAIL] ❌ EXCEPTION SMTP: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception SMTP: ' . $e->getMessage(),
                'sent_emails' => []
            ];
        }
    }
    
    /**
     * Envoie via SMTP direct (sans dépendances)
     */
    private function sendViaSMTP(string $to, string $subject, string $body): array
    {
        try {
            $this->addLog("[EMAIL] 📤 Tentative envoi via SMTP à: $to");
            
            // Vérifier la disponibilité d'OpenSSL
            if (!extension_loaded('openssl')) {
                $this->addLog("[EMAIL] ❌ ERREUR: Extension OpenSSL non disponible");
                $this->addLog("[EMAIL]    Activez OpenSSL dans php.ini: extension=openssl");
                return [
                    'success' => false,
                    'error' => 'Extension OpenSSL requise - vérifiez php.ini',
                    'method' => 'smtp'
                ];
            }
            
            // Créer une connexion SMTP
            $this->addLog("[EMAIL] 🔗 Connexion à {$this->smtpHost}:{$this->smtpPort}...");
            $sock = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 10);
            
            if (!$sock) {
                $this->addLog("[EMAIL] ❌ ERREUR: Impossible de se connecter à SMTP: $errstr ($errno)");
                $this->addLog("[EMAIL]    Causes possibles:");
                $this->addLog("[EMAIL]    • Firewall/pare-feu bloque le port 587");
                $this->addLog("[EMAIL]    • Serveur SMTP non accessible");
                $this->addLog("[EMAIL]    • Problème de connectivité réseau");
                return [
                    'success' => false,
                    'error' => "Connexion SMTP échouée: $errstr ($errno)",
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ Connexion établie");
            
            // Lire la réponse du serveur
            $response = fgets($sock, 512);
            if (strpos($response, '220') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ Réponse SMTP invalide: $response");
                return [
                    'success' => false,
                    'error' => "Réponse SMTP invalide: $response",
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ Serveur prêt");
            
            // EHLO
            $this->addLog("[EMAIL] 🤝 Envoi EHLO...");
            $this->writeCommand($sock, "EHLO cvtek.local");
            $ehloResponse = $this->readResponse($sock);
            if (strpos($ehloResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté EHLO: $ehloResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté EHLO: $ehloResponse",
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ EHLO accepté");
            
            // STARTTLS
            $this->addLog("[EMAIL] 🔐 Activation STARTTLS...");
            $this->writeCommand($sock, "STARTTLS");
            $starttlsResponse = $this->readResponse($sock);
            if (strpos($starttlsResponse, '220') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté STARTTLS: $starttlsResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté STARTTLS: $starttlsResponse",
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ STARTTLS accepté");
            
            // Activer encryption TLS
            $tlsActive = false;
            
            // Essayer différentes constantes TLS disponibles
            if (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
                $this->addLog("[EMAIL] 🔐 Utilisation STREAM_CRYPTO_METHOD_TLS_CLIENT");
                $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            } elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $this->addLog("[EMAIL] 🔐 Utilisation STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT");
                $tlsActive = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            } else {
                $this->addLog("[EMAIL] ⚠️  Aucune constante TLS disponible, tentative avec valeur 4 (TLS 1.2)");
                $tlsActive = @stream_socket_enable_crypto($sock, true, 4);
            }
            
            if (!$tlsActive) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Impossible d'activer TLS");
                $this->addLog("[EMAIL] ⚠️  Vérifiez que OpenSSL est correctement configuré");
                $this->addLog("[EMAIL]    On Windows: extension=php_openssl.dll doit être dans php.ini");
                $this->addLog("[EMAIL]    Sur Linux: openssl extension doit être installée");
                return [
                    'success' => false,
                    'error' => 'Impossible d\'activer TLS/SSL - Vérifiez OpenSSL',
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ TLS activé");
            
            // EHLO A NOUVEAU après TLS (c'est obligatoire!)
            $this->addLog("[EMAIL] 🤝 Envoi EHLO à nouveau après TLS...");
            $this->writeCommand($sock, "EHLO cvtek.local");
            $ehloResponse2 = $this->readResponse($sock);
            if (strpos($ehloResponse2, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté EHLO (après TLS): $ehloResponse2");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté EHLO (après TLS): $ehloResponse2",
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ EHLO accepté (après TLS)");
            
            // AUTH LOGIN
            $this->addLog("[EMAIL] 🔐 Authentification...");
            $this->writeCommand($sock, "AUTH LOGIN");
            $authResponse = $this->readResponse($sock);
            if (strpos($authResponse, '334') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP n'a pas demandé d'authentification: $authResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP: $authResponse",
                    'method' => 'smtp'
                ];
            }
            
            // Envoyer username encodé en base64
            $this->addLog("[EMAIL] 📏 Envoi du username...");
            $this->writeCommand($sock, base64_encode($this->username));
            $userResponse = $this->readResponse($sock);
            if (strpos($userResponse, '334') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP n'a pas demandé le password: $userResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP: $userResponse",
                    'method' => 'smtp'
                ];
            }
            
            // Envoyer password encodé en base64
            $this->addLog("[EMAIL] 📏 Envoi du password...");
            $this->writeCommand($sock, base64_encode($this->password));
            $response = $this->readResponse($sock);
            
            $this->addLog("[EMAIL] 📋 Réponse serveur après password: " . trim($response));
            
            if (strpos($response, '235') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Authentification échouée");
                $this->addLog("[EMAIL]    Réponse serveur: " . trim($response));
                $this->addLog("[EMAIL]    Code attendu: 235");
                $this->addLog("[EMAIL]    Vérifiez les identifiants Gmail");
                $this->addLog("[EMAIL]    Email: benoitccasibio@gmail.com");
                $this->addLog("[EMAIL]    Code app: aiwachtcdfioihsi");
                return [
                    'success' => false,
                    'error' => 'Authentification Gmail SMTP échouée - Vérifiez les identifiants',
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ Authentification réussie");
            
            // MAIL FROM
            $this->addLog("[EMAIL] 📧 Expéditeur: {$this->fromEmail}");
            $this->writeCommand($sock, "MAIL FROM:<{$this->fromEmail}>");
            $mailFromResponse = $this->readResponse($sock);
            if (strpos($mailFromResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté MAIL FROM: $mailFromResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté MAIL FROM: $mailFromResponse",
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ Expéditeur accepté");
            
            // RCPT TO
            $this->addLog("[EMAIL] 📨 Destinataire: $to");
            $this->writeCommand($sock, "RCPT TO:<$to>");
            $rcptToResponse = $this->readResponse($sock);
            if (strpos($rcptToResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté RCPT TO: $rcptToResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté RCPT TO: $rcptToResponse",
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ Destinataire accepté");
            
            // DATA
            $this->writeCommand($sock, "DATA");
            $dataResponse = $this->readResponse($sock);
            if (strpos($dataResponse, '354') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP n'est pas prêt pour les données: $dataResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté DATA: $dataResponse",
                    'method' => 'smtp'
                ];
            }
            
            // Construire les headers et le body
            $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            $message = $headers . $body . "\r\n.\r\n";
            
            $this->addLog("[EMAIL] 📏 Envoi du contenu...");
            fwrite($sock, $message);
            $submitResponse = $this->readResponse($sock);
            
            // Vérifier le code 250 (OK - Message queued for delivery)
            if (strpos($submitResponse, '250') === false) {
                fclose($sock);
                $this->addLog("[EMAIL] ❌ ERREUR: Serveur SMTP a rejeté le message: $submitResponse");
                return [
                    'success' => false,
                    'error' => "Serveur SMTP a rejeté le message: $submitResponse",
                    'method' => 'smtp'
                ];
            }
            $this->addLog("[EMAIL] ✅ Serveur a accepté le message (code 250)");
            
            // QUIT
            $this->writeCommand($sock, "QUIT");
            fclose($sock);
            
            $this->addLog("[EMAIL] ✅ Email ENVOYÉ avec succès à: $to");
            $this->addLog("[EMAIL]    Sujet: $subject");
            return [
                'success' => true,
                'method' => 'smtp'
            ];
            
        } catch (Exception $e) {
            $this->addLog("[EMAIL] ❌ EXCEPTION SMTP: " . $e->getMessage());
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
        fflush($sock); // Force l'envoi immédiat
        usleep(100000); // 100ms de délai pour que le serveur traite
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
        $safeStudentName = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        $safeStudentDocument = htmlspecialchars($studentDocument, ENT_QUOTES, 'UTF-8');
        
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
            <p>L'étudiant <strong>$safeStudentName</strong> a ajouté un nouveau fichier/URL :</p>
            <p><strong>Document :</strong> $safeStudentDocument</p>
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
     * Envoie un email de résumé des suppressions d'étudiants
     * Endpoint: Après la montée d'année pour les étudiants supprimés
     */
    public function sendStudentDeletionSummary(
        string $adminEmail,
        string $adminName,
        array $deletedStudents,
        int $totalFilesDeleted
    ): array {
        try {
            $studentCount = count($deletedStudents);
            $subject = "📊 Résumé: $studentCount étudiant(s) supprimé(s) - Montée d'année";

            // Construire la liste des étudiants supprimés
            $studentsHtml = "<ul>";
            $studentsText = "";
            
            foreach ($deletedStudents as $student) {
                $email = $student['email'] ?? 'N/A';
                $filesDeleted = $student['files_deleted'] ?? 0;
                $studentsHtml .= "<li><strong>$email</strong> - $filesDeleted fichier(s) supprimé(s)</li>";
                $studentsText .= "  - $email ($filesDeleted fichier(s))\n";
            }
            $studentsHtml .= "</ul>";

            // Email en texte brut
            $textBody = <<<EOT
Bonjour $adminName,

Résumé de la montée d'année universitaire:

ÉTUDIANTS SUPPRIMÉS ($studentCount):
$studentsText

STATISTIQUES:
- Nombre d'étudiants supprimés: $studentCount
- Nombre de fichiers supprimés: $totalFilesDeleted

DONNÉES SUPPRIMÉES:
✓ Comptes utilisateurs
✓ Documents et versions
✓ Commentaires
✓ Fichiers uploads
✓ Abonnements

La suppression a été effectuée avec succès.

Cordialement,
Système CVTEK
EOT;

            // Email en HTML
            $htmlBody = <<<EOH
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4b575f; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .section { margin: 20px 0; }
        .stats-box { background: #f0f4f8; border-left: 4px solid #2563eb; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .stats-item { margin: 8px 0; font-size: 14px; }
        .stats-value { font-weight: bold; color: #2563eb; }
        .student-list { background: #fff9f0; border-left: 4px solid #ea580c; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .button { display: inline-block; padding: 10px 20px; background: #4b575f; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .footer { font-size: 12px; color: #999; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Résumé de la Montée d'Année</h1>
            <p>Suppressions d'étudiants effectuées</p>
        </div>

        <div class="section">
            <p>Bonjour <strong>$adminName</strong>,</p>
            <p>Voici un résumé des étudiants supprimés lors de la montée d'année universitaire.</p>
        </div>

        <div class="stats-box">
            <h3>📈 Statistiques Globales:</h3>
            <div class="stats-item">
                Étudiants supprimés: <span class="stats-value">$studentCount</span>
            </div>
            <div class="stats-item">
                Fichiers supprimés: <span class="stats-value">$totalFilesDeleted</span>
            </div>
        </div>

        <div class="student-list">
            <h3>👥 Liste des Étudiants Supprimés:</h3>
            $studentsHtml
        </div>

        <div class="section">
            <h3>✓ Données Supprimées:</h3>
            <ul>
                <li>Comptes utilisateurs</li>
                <li>Documents et versions</li>
                <li>Commentaires</li>
                <li>Fichiers uploads</li>
                <li>Abonnements</li>
            </ul>
        </div>

        <div class="footer">
            <p>Système CVTEK - Plateforme de gestion des documents pédagogiques</p>
            <p>Université de Limoges</p>
        </div>
    </div>
</body>
</html>
EOH;

            // Envoyer l'email
            $result = $this->sendEmail(
                $adminEmail,
                $subject,
                $htmlBody,
                $textBody
            );

            if ($result['success']) {
                error_log("[EMAIL] ✅ Email de suppression envoyé à: $adminEmail");
            } else {
                error_log("[EMAIL] ❌ Erreur envoi email suppression: " . ($result['error'] ?? 'Inconnue'));
            }

            return $result;

        } catch (Exception $e) {
            error_log("[EMAIL ERROR] " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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

