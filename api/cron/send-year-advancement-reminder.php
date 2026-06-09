<?php
/**
 * Cron Job: Envoyer un email de rappel pour la montée d'année
 * À exécuter le 1er août de chaque année
 * 
 * Configuration cron (Linux/Unix):
 * 0 8 1 8 * /usr/bin/php /chemin/vers/api/cron/send-year-advancement-reminder.php
 * 
 * Ou via cURL (si FTP uniquement disponible):
 * curl -s https://votre-domaine.fr/cvtek/api/cron/send-year-advancement-reminder.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../Service/EmailService.php';

class YearAdvancementReminder
{
    private $emailService;

    public function __construct()
    {
        $this->emailService = new EmailService();
    }

    /**
     * Envoyer les rappels à tous les admins
     */
    public function sendReminders(): bool
    {
        try {
            global $connexion;

            // Obtenir tous les admins
            $admins = $connexion->query("SELECT id, email, username FROM users WHERE role = 'admin'");

            if (!$admins || $admins->num_rows === 0) {
                error_log("[CRON] Aucun admin trouvé pour envoyer les rappels");
                return false;
            }

            $remindersSent = 0;

            while ($admin = $admins->fetch_assoc()) {
                $adminEmail = $admin['email'];
                $adminName = $admin['username'];

                try {
                    $this->sendReminderToAdmin($adminEmail, $adminName);
                    $remindersSent++;
                    error_log("[CRON] Rappel envoyé à: $adminEmail");
                } catch (Exception $e) {
                    error_log("[CRON ERROR] Erreur lors de l'envoi au: $adminEmail - " . $e->getMessage());
                }
            }

            error_log("[CRON] Montée d'année: $remindersSent rappels envoyés avec succès");
            return true;

        } catch (Exception $e) {
            error_log("[CRON ERROR] Erreur dans sendReminders: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un rappel à un admin spécifique
     */
    private function sendReminderToAdmin(string $adminEmail, string $adminName): void
    {
        global $connexion;

        // Récupérer les statistiques des étudiants par année
        $stats = $connexion->query(
            "SELECT année, COUNT(*) as count FROM users WHERE role = 'student' GROUP BY année ORDER BY année ASC"
        );

        $statsText = "";
        $statsHtml = "<ul>";

        if ($stats) {
            while ($row = $stats->fetch_assoc()) {
                $year = $row['année'];
                $count = $row['count'];
                $statsText .= "  - Année $year: $count étudiant(s)\n";
                $statsHtml .= "<li><strong>Année $year:</strong> $count étudiant(s)</li>";
            }
        }
        $statsHtml .= "</ul>";

        // Email en texte brut
        $textBody = <<<EOT
Bonjour $adminName,

C'est le 1er août! Il est temps de procéder à la montée d'année universitaire.

AVANT DE CONTINUER:
✓ Vérifiez que tous les notes et évaluations sont enregistrées
✓ Faites une sauvegarde de la base de données
✓ Prévenez les utilisateurs de la maintenance

STATISTIQUES ACTUELLES:
$statsText

PROCESSUS DE MONTÉE D'ANNÉE:
1. Les étudiants de l'année N seront passés à l'année N+1
2. Les étudiants de l'année 4 seront supprimés (avec tous leurs documents)
3. Les fichiers uploads seront également supprimés

POUR EFFECTUER LA MONTÉE D'ANNÉE:
1. Connectez-vous à l'interface admin: https://mmi.unilim.fr/cvtek/
2. Cliquez sur le bouton "📅 Montée d'année"
3. Confirmez l'action

⚠️ ATTENTION: Cette action est irréversible!

Besoin d'aide? Contactez l'administrateur système.

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
        .alert { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
        .danger { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; }
        .stats { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background: #4b575f; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .footer { font-size: 12px; color: #999; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Montée d'année universitaire</h1>
            <p>Le 1er août est arrivé!</p>
        </div>

        <div class="section">
            <p>Bonjour <strong>$adminName</strong>,</p>
            <p>Il est temps de procéder à la montée d'année universitaire pour tous les étudiants.</p>
        </div>

        <div class="alert">
            <strong>✓ AVANT DE CONTINUER:</strong>
            <ul>
                <li>Vérifiez que tous les notes et évaluations sont enregistrées</li>
                <li>Faites une sauvegarde de la base de données</li>
                <li>Prévenez les utilisateurs de la maintenance</li>
            </ul>
        </div>

        <div class="stats">
            <h3>📊 Statistiques actuelles:</h3>
            $statsHtml
        </div>

        <div class="section">
            <h3>🔄 Processus de montée d'année:</h3>
            <ol>
                <li>Les étudiants de l'année N seront passés à l'année N+1</li>
                <li>Les étudiants de l'année 4 seront supprimés (avec tous leurs documents)</li>
                <li>Les fichiers uploads seront également supprimés</li>
            </ol>
        </div>

        <div class="section">
            <h3>📝 Pour effectuer la montée d'année:</h3>
            <ol>
                <li>Connectez-vous à l'interface admin</li>
                <li>Cliquez sur le bouton <strong>"📅 Montée d'année"</strong></li>
                <li>Confirmez l'action</li>
            </ol>
            <a href="https://mmi.unilim.fr/cvtek/" class="button">Accéder à l'admin</a>
        </div>

        <div class="danger">
            <strong>⚠️ ATTENTION:</strong> Cette action est irréversible!
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
        $subject = "📅 Rappel: Montée d'année universitaire du " . date('d/m/Y');

        try {
            $this->emailService->sendEmail(
                $adminEmail,
                $subject,
                $htmlBody,
                $textBody
            );
        } catch (Exception $e) {
            throw new Exception("Erreur d'envoi email: " . $e->getMessage());
        }
    }
}

// ===============================================
// Exécution du cron
// ===============================================

// Vérifier que ce n'est pas un accès direct non autorisé
$isValidCronAccess = false;

// 1. Vérifier via la ligne de commande PHP
if (php_sapi_name() === 'cli') {
    $isValidCronAccess = true;
}

// 2. Vérifier avec une clé secrète via HTTP
if (isset($_GET['key']) && $_GET['key'] === getenv('CRON_SECRET_KEY')) {
    $isValidCronAccess = true;
}

// 3. Vérifier l'IP de la requête (optionnel)
// if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', 'localhost'])) {
//     $isValidCronAccess = true;
// }

if (!$isValidCronAccess) {
    http_response_code(403);
    die('Accès refusé');
}

try {
    $reminder = new YearAdvancementReminder();
    $result = $reminder->sendReminders();

    if ($result) {
        http_response_code(200);
        echo "✅ Rappels de montée d'année envoyés avec succès";
    } else {
        http_response_code(500);
        echo "❌ Erreur lors de l'envoi des rappels";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "❌ Erreur: " . $e->getMessage();
    error_log("[CRON FATAL ERROR] " . $e->getMessage());
}
?>
