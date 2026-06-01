<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Service/EmailService.php';

/**
 * SystemController
 * Gère les opérations système (migration BD, diagnostic, etc.)
 */
class SystemController extends Controller
{
    protected function processGetRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();

        // GET /api/system/init → Initialiser le système
        if ($id === 'init') {
            logAction("SYSTEM_INIT", ['action' => 'init']);
            return $this->initializeSystem();
        }

        // GET /api/system/diag → Diagnostiquer l'état
        if ($id === 'diag') {
            logAction("SYSTEM_DIAG", ['action' => 'diag']);
            return $this->diagnoseDatabase();
        }

        // GET /api/system/migrate → Migrer les données
        if ($id === 'migrate') {
            logAction("SYSTEM_MIGRATE", ['action' => 'migrate']);
            return $this->migrateData();
        }
        // GET /api/system/normalize → Normalizar estructura (remover url_fichier e version)
        if ($id === 'normalize' || $id === 'normalize-structure') {
            logAction("SYSTEM_NORMALIZE", ['action' => 'normalize']);
            return $this->normalizeStructure();
        }
        
        // GET /api/system/test-email → Tester l'envoi multiples emails
        if ($id === 'test-email') {
            logAction("SYSTEM_TEST_EMAIL", ['action' => 'test-email']);
            return $this->testEmailNotification();
        }
        
        // GET /api/system/test-email-real - Test avec les VRAIES adresses Gmail
        if ($id === 'test-email-real') {
            logAction("SYSTEM_TEST_EMAIL_REAL", ['action' => 'test-email-real']);
            return $this->testRealEmailAddresses();
        }
        
        return ["error" => "Action système non reconnue", "id" => $id];
    }

    /**
     * Initialiser le système de versioning
     */
    private function initializeSystem(): array
    {
        try {
            $conn = Database::getConnection();
            $results = [];

            // Étape 1: Créer la table doc_version
            $check = $conn->query("SHOW TABLES LIKE 'doc_version'");
            $tableExists = $check && $check->rowCount() > 0;

            if (!$tableExists) {
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS doc_version (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        id_doc INT NOT NULL COMMENT 'Référence au document parent',
                        version DECIMAL(3,1) NOT NULL COMMENT 'Numéro de version (1.0, 2.0, etc)',
                        url_fichier VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        
                        CONSTRAINT fk_doc_version FOREIGN KEY (id_doc) REFERENCES documents(id) ON DELETE CASCADE,
                        
                        INDEX idx_id_doc (id_doc),
                        INDEX idx_version (version),
                        INDEX idx_created_at (created_at),
                        UNIQUE KEY uk_doc_version (id_doc, version)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                $results['table_created'] = true;
            } else {
                $results['table_exists'] = true;
            }

            // Étape 1b: Créer la table commentaire
            $checkComment = $conn->query("SHOW TABLES LIKE 'commentaire'");
            $commentTableExists = $checkComment && $checkComment->rowCount() > 0;

            if (!$commentTableExists) {
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS commentaire (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        id_user INT NOT NULL COMMENT 'Référence utilisateur',
                        id_docversion INT NOT NULL COMMENT 'Référence version du document',
                        text LONGTEXT NOT NULL COMMENT 'Contenu du commentaire',
                        date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création du commentaire',
                        
                        CONSTRAINT fk_commentaire_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
                        CONSTRAINT fk_commentaire_docversion FOREIGN KEY (id_docversion) REFERENCES doc_version(id) ON DELETE CASCADE,
                        
                        INDEX idx_id_user (id_user),
                        INDEX idx_id_docversion (id_docversion),
                        INDEX idx_date (date)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                $results['commentaire_table_created'] = true;
            } else {
                $results['commentaire_table_exists'] = true;
            }

            // Étape 1c: Créer la table abonnement
            $checkAbonnement = $conn->query("SHOW TABLES LIKE 'abonnement'");
            $abonnementTableExists = $checkAbonnement && $checkAbonnement->rowCount() > 0;

            if (!$abonnementTableExists) {
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS abonnement (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        id_prof INT NOT NULL COMMENT 'Référence professeur',
                        id_user INT NOT NULL COMMENT 'Référence utilisateur/étudiant',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d\'abonnement',
                        
                        CONSTRAINT fk_abonnement_prof FOREIGN KEY (id_prof) REFERENCES users(id) ON DELETE CASCADE,
                        CONSTRAINT fk_abonnement_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
                        
                        INDEX idx_id_prof (id_prof),
                        INDEX idx_id_user (id_user),
                        INDEX idx_created_at (created_at),
                        UNIQUE KEY uk_prof_user (id_prof, id_user)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                $results['abonnement_table_created'] = true;
            } else {
                $results['abonnement_table_exists'] = true;
            }

            // Étape 2: Migrer les données existantes
            $missing = $conn->query("
                SELECT d.id, d.created_at
                FROM documents d
                WHERE NOT EXISTS (
                    SELECT 1 FROM doc_version dv WHERE dv.id_doc = d.id
                )
            ")->fetchAll(PDO::FETCH_ASSOC);

            $migratedCount = 0;
            foreach ($missing as $doc) {
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO doc_version (id_doc, version, url_fichier, created_at)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $doc['id'],
                        1.0,
                        '/cvtek/uploads/placeholder.pdf',
                        $doc['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $migratedCount++;
                } catch (Exception $e) {
                    error_log("Migration error for doc " . $doc['id'] . ": " . $e->getMessage());
                }
            }
            $results['documents_migrated'] = $migratedCount;

            return [
                'success' => true,
                'message' => 'Système initialisé avec succès',
                'results' => $results
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Diagnostiquer l'état de la BD
     */
    private function diagnoseDatabase(): array
    {
        try {
            $conn = Database::getConnection();
            $diagnosis = [];

            // Tables
            $diagnosis['tables'] = [];
            
            try {
                $docsStructure = $conn->query("DESCRIBE documents")->fetchAll(PDO::FETCH_ASSOC);
                $diagnosis['tables']['documents'] = [
                    'exists' => true,
                    'columns' => array_column($docsStructure, 'Field')
                ];
            } catch (Exception $e) {
                $diagnosis['tables']['documents'] = ['exists' => false, 'error' => $e->getMessage()];
            }

            try {
                $versionStructure = $conn->query("DESCRIBE doc_version")->fetchAll(PDO::FETCH_ASSOC);
                $diagnosis['tables']['doc_version'] = [
                    'exists' => true,
                    'columns' => array_column($versionStructure, 'Field')
                ];
            } catch (Exception $e) {
                $diagnosis['tables']['doc_version'] = ['exists' => false];
            }

            try {
                $commentStructure = $conn->query("DESCRIBE commentaire")->fetchAll(PDO::FETCH_ASSOC);
                $diagnosis['tables']['commentaire'] = [
                    'exists' => true,
                    'columns' => array_column($commentStructure, 'Field')
                ];
            } catch (Exception $e) {
                $diagnosis['tables']['commentaire'] = ['exists' => false];
            }

            // Données
            $diagnosis['data'] = [];
            
            $usersCount = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch(PDO::FETCH_ASSOC)['cnt'];
            $diagnosis['data']['total_users'] = (int)$usersCount;

            $docsCount = $conn->query("SELECT COUNT(*) as cnt FROM documents")->fetch(PDO::FETCH_ASSOC)['cnt'];
            $diagnosis['data']['total_documents'] = (int)$docsCount;

            try {
                $versionsCount = $conn->query("SELECT COUNT(*) as cnt FROM doc_version")->fetch(PDO::FETCH_ASSOC)['cnt'];
                $diagnosis['data']['total_versions'] = (int)$versionsCount;
            } catch (Exception $e) {
                $diagnosis['data']['total_versions'] = 'N/A';
            }

            try {
                $commentsCount = $conn->query("SELECT COUNT(*) as cnt FROM commentaire")->fetch(PDO::FETCH_ASSOC)['cnt'];
                $diagnosis['data']['total_comments'] = (int)$commentsCount;
            } catch (Exception $e) {
                $diagnosis['data']['total_comments'] = 'N/A';
            }

            // Utilisateurs avec docs
            $users = $conn->query("
                SELECT u.id, u.username, u.email, u.role, COUNT(d.id) as doc_count
                FROM users u
                LEFT JOIN documents d ON u.id = d.user_id
                GROUP BY u.id
                ORDER BY u.id
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            $diagnosis['users_details'] = $users;

            return [
                'success' => true,
                'message' => 'Diagnostic réussi',
                'diagnosis' => $diagnosis
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Migrer as dados
     */
    private function migrateData(): array
    {
        return $this->initializeSystem();
    }

    /**
     * Normalizar estructura: remover url_fichier e version de documents
     * e migrar as dados para doc_version
     */
    private function normalizeStructure(): array
    {
        try {
            $conn = Database::getConnection();
            $steps = [];

            // Étape 1: Vérifier si url_fichier existe dans documents
            $columnsCheck = $conn->query("SHOW COLUMNS FROM documents LIKE 'url_fichier'");
            $urlFichierExists = $columnsCheck && $columnsCheck->rowCount() > 0;

            if ($urlFichierExists) {
                // Migrer les URLs de documents vers doc_version (version 1.0)
                $docsWithUrl = $conn->query("
                    SELECT id, url_fichier, created_at
                    FROM documents 
                    WHERE url_fichier IS NOT NULL AND url_fichier != ''
                ")->fetchAll(PDO::FETCH_ASSOC);

                $migratedCount = 0;
                foreach ($docsWithUrl as $doc) {
                    // Vérifier si version 1.0 existe déjà
                    $check = $conn->prepare("
                        SELECT COUNT(*) as cnt FROM doc_version 
                        WHERE id_doc = ? AND version = 1.0
                    ");
                    $check->execute([$doc['id']]);
                    $exists = $check->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

                    if (!$exists) {
                        $stmt = $conn->prepare("
                            INSERT INTO doc_version (id_doc, version, url_fichier, created_at)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$doc['id'], 1.0, $doc['url_fichier'], $doc['created_at']]);
                        $migratedCount++;
                    }
                }

                if ($migratedCount > 0) {
                    $steps[] = "Migré $migratedCount URLs de documents vers doc_version (version 1.0)";
                } else {
                    $steps[] = "Aucune URL à migrer (déjà fait ou aucune URL)";
                }

                // Supprimer la colonne url_fichier de documents
                $conn->exec("ALTER TABLE documents DROP COLUMN url_fichier");
                $steps[] = "Colonne url_fichier supprimée de documents";
            } else {
                $steps[] = "Colonne url_fichier n'existe pas dans documents";
            }

            // Étape 2: Vérifier si version existe dans documents (ancienne estructura)
            $versionCheck = $conn->query("SHOW COLUMNS FROM documents LIKE 'version'");
            $versionExists = $versionCheck && $versionCheck->rowCount() > 0;

            if ($versionExists) {
                // Supprimer la colonne version
                $conn->exec("ALTER TABLE documents DROP COLUMN version");
                $steps[] = "Colonne version supprimée de documents";
            } else {
                $steps[] = "Colonne version n'existe pas";
            }

            // Étape 3: Vérifier l'intégrité
            $docCount = $conn->query("SELECT COUNT(*) as cnt FROM documents")->fetch(PDO::FETCH_ASSOC)['cnt'];
            $versionCount = $conn->query("SELECT COUNT(*) as cnt FROM doc_version")->fetch(PDO::FETCH_ASSOC)['cnt'];

            // Vérifier les documents sans version (risque d'orphelins)
            $docsWithoutVersion = $conn->query("
                SELECT COUNT(*) as cnt FROM documents d
                LEFT JOIN doc_version dv ON d.id = dv.id_doc
                WHERE dv.id IS NULL
            ")->fetch(PDO::FETCH_ASSOC)['cnt'];

            if ($docsWithoutVersion > 0) {
                $steps[] = "⚠️ ATTENTION: $docsWithoutVersion document(s) sans version!";
            }

            return [
                'success' => true,
                'message' => 'Normalisation réussie: estructura mise à jour',
                'steps' => $steps,
                'stats' => [
                    'documents_count' => (int)$docCount,
                    'versions_count' => (int)$versionCount,
                    'documents_without_version' => (int)$docsWithoutVersion
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Tester l'envoi multiples emails
     * GET /api/system/test-email?emails=email1,email2,email3
     * ou
     * GET /api/system/test-email (utilise des emails fictifs)
     */
    private function testEmailNotification(): array
    {
        try {
            error_log("========================================");
            error_log("[TEST-EMAIL] 🧪 TEST D'ENVOI MULTIPLES");
            error_log("========================================");

            $emailService = new EmailService();

            // Récupérer les emails depuis les paramètres GET si fournis
            $emailsParam = $_GET['emails'] ?? null;
            
            // Simulation de notification de document
            $studentName = "Test Student";
            $studentEmail = "test@cvtek.local";
            $documentTitle = "Document de Test";

            // Plusieurs emails destinataires
            if ($emailsParam) {
                $professorEmails = array_filter(array_map('trim', explode(',', $emailsParam)));
                error_log("[TEST-EMAIL] Utilisation des emails fournis: " . implode(', ', $professorEmails));
            } else {
                // Par défaut, utiliser des emails fictifs pour test
                $professorEmails = [
                    "prof1@cvtek.local",
                    "prof2@cvtek.local",
                    "prof3@cvtek.local"
                ];
                error_log("[TEST-EMAIL] ⚠️ ATTENTION: Utilisant des emails FICTIFS (prof@cvtek.local)");
                error_log("[TEST-EMAIL] Pour tester avec vos vrais emails, utilisez: /api/system/test-email?emails=email1@domain.com,email2@domain.com");
            }

            error_log("[TEST-EMAIL] Configuration test:");
            error_log("[TEST-EMAIL]   Étudiant: $studentName ($studentEmail)");
            error_log("[TEST-EMAIL]   Document: $documentTitle");
            error_log("[TEST-EMAIL]   Destinataires: " . count($professorEmails));
            foreach ($professorEmails as $i => $email) {
                error_log("[TEST-EMAIL]   " . ($i + 1) . ". " . $email);
            }
            
            error_log("[TEST-EMAIL] 📤 Démarrage de l'envoi...");
            $startTime = microtime(true);

            $result = $emailService->sendNewDocumentNotification(
                $studentEmail,
                $studentName,
                $documentTitle,
                $professorEmails
            );

            $duration = microtime(true) - $startTime;
            error_log("[TEST-EMAIL] ✅ Envoi terminé en " . round($duration, 2) . "s");
            error_log("[TEST-EMAIL] Résultat: " . json_encode($result));

            return [
                'success' => true,
                'test' => 'email_multiples',
                'message' => 'Test d\'envoi multiples emails exécuté',
                'duration_seconds' => round($duration, 2),
                'configuration' => [
                    'student' => $studentName . ' (' . $studentEmail . ')',
                    'document' => $documentTitle,
                    'recipients_count' => count($professorEmails),
                    'recipients' => $professorEmails,
                    'using_real_emails' => !is_null($emailsParam),
                    'using_test_emails' => is_null($emailsParam)
                ],
                'result' => $result,
                'status' => [
                    'emails_sent' => $result['success'] ? ($result['count'] ?? 'unknown') : 0,
                    'total_recipients' => count($professorEmails),
                    'overall_success' => $result['success'] ?? false
                ],
                'instructions' => [
                    '📋 COMMENT UTILISER CE TEST:',
                    '1. Avec emails réels: GET /api/system/test-email?emails=prof1@domain.com,prof2@domain.com',
                    '2. Avec emails fictifs: GET /api/system/test-email',
                    '3. Vérifiez les logs PHP pour le détail complet de chaque envoi',
                    '4. Les logs affichent les délais SMTP, les codes de réponse, et les erreurs',
                    '',
                    '⚠️ IMPORTANT:',
                    '- Les emails fictifs (prof@cvtek.local) ne recevront rien',
                    '- Utilisez des adresses emails RÉELLES pour un test valide',
                    '- Même si "success: true", vérifiez les logs pour les faux positifs'
                ]
            ];

        } catch (Exception $e) {
            error_log("[TEST-EMAIL] ❌ Erreur: " . $e->getMessage());
            error_log("[TEST-EMAIL] Trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'code' => 500
            ];
        }
    }

    /**
     * Test avec les VRAIES adresses Gmail
     * GET /api/system/test-email-real
     */
    private function testRealEmailAddresses(): array
    {
        try {
            error_log("========================================");
            error_log("[TEST-EMAIL-REAL] 🧪 TEST AVEC VRAIES EMAILS");
            error_log("========================================");

            $emailService = new EmailService();

            // Les VRAIES adresses fournies par l'utilisateur
            $realEmails = [
                "maelmy19@gmail.com",
                "malpriv19@gmail.com"
            ];

            $studentName = "Test Réel";
            $studentEmail = "test.real@local";
            $documentTitle = "Document Réel - Test Multiple";

            error_log("[TEST-EMAIL-REAL] Configuration:");
            error_log("[TEST-EMAIL-REAL]   Étudiant: $studentName");
            error_log("[TEST-EMAIL-REAL]   Document: $documentTitle");
            error_log("[TEST-EMAIL-REAL]   Destinataires: " . implode(", ", $realEmails));
            error_log("[TEST-EMAIL-REAL] 📤 Démarrage de l'envoi...");

            $startTime = microtime(true);

            $result = $emailService->sendNewDocumentNotification(
                $studentEmail,
                $studentName,
                $documentTitle,
                $realEmails
            );

            $duration = microtime(true) - $startTime;
            error_log("[TEST-EMAIL-REAL] ✅ Envoi terminé en " . round($duration, 2) . "s");
            error_log("[TEST-EMAIL-REAL] Résultat: " . json_encode($result));

            return [
                'success' => true,
                'test' => 'email_real_addresses',
                'message' => 'Test avec vraies adresses Gmail exécuté',
                'duration_seconds' => round($duration, 2),
                'configuration' => [
                    'student' => $studentName,
                    'document' => $documentTitle,
                    'recipients_count' => count($realEmails),
                    'recipients' => $realEmails
                ],
                'result' => $result,
                'status' => [
                    'emails_sent' => $result['success'] ? ($result['count'] ?? 'unknown') : 0,
                    'total_recipients' => count($realEmails),
                    'overall_success' => $result['success'] ?? false
                ],
                'instructions' => [
                    '📋 RÉSULTATS:',
                    '✅ Si success: true → Les emails ont été envoyés à SMTP',
                    '📧 Vérifiez maelmy19@gmail.com et malpriv19@gmail.com',
                    '',
                    '⏱️ Attendez 5-10 secondes pour la réception',
                    '',
                    '❌ Si success: false → Vérifiez les logs PHP pour l\'erreur SMTP',
                    '',
                    '📋 Cherchez dans les logs:',
                    '[EMAIL] ❌ ERREUR: ...',
                    'Les codes 550, 553, 535 indiquent une erreur'
                ]
            ];

        } catch (Exception $e) {
            error_log("[TEST-EMAIL-REAL] ❌ Erreur: " . $e->getMessage());
            error_log("[TEST-EMAIL-REAL] Trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'code' => 500
            ];
        }
    }
}
