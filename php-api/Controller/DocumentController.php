<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/DocumentRepository.php';
require_once __DIR__ . '/../Repository/UserRepository.php';
require_once __DIR__ . '/../Repository/AbonnementRepository.php';
require_once __DIR__ . '/../Service/EmailService.php';

/**
 * DocumentController
 * Gère les documents (list, get, create, update, delete)
 */
class DocumentController extends Controller
{
    private DocumentRepository $documents;
    private UserRepository $users;
    private AbonnementRepository $abonnements;
    private EmailService $emailService;

    public function __construct()
    {
        $this->documents = new DocumentRepository();
        $this->users = new UserRepository();
        $this->abonnements = new AbonnementRepository();
        $this->emailService = new EmailService(true); // Mode debug activé
    }

    protected function processGetRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();
        $action = $request->getAction();
        $userId = $request->getParam('user_id');

        // GET /api/documents -> tous les documents
        if (!$id && !$userId) {
            logAction("GET_ALL_DOCUMENTS", ['requester' => 'anonymous']);
            $docs = $this->documents->findAll();
            return [
                'count' => count($docs),
                'documents' => $docs,
            ];
        }

        // GET /api/documents?user_id=123 -> documents d'un utilisateur
        if (!$id && $userId) {
            $userId = (int)$userId;
            logAction("GET_USER_DOCUMENTS", ['userId' => $userId]);
            $docs = $this->documents->findByUserId($userId);
            return [
                'count' => count($docs),
                'documents' => $docs,
            ];
        }

        // GET /api/documents/123/versions -> toutes les versions d'un document
        if ($id && $action === 'versions') {
            $id = (int)$id;
            logAction("GET_DOCUMENT_VERSIONS", ['id' => $id]);
            $versions = $this->documents->findVersions($id);
            
            if (empty($versions)) {
                return ['error' => 'Document non trouvé', 'code' => 404];
            }

            return [
                'count' => count($versions),
                'versions' => $versions,
            ];
        }

        // GET /api/documents/version/456 -> récupère le document associé à une version
        if ($id === 'version' && $action) {
            $versionId = (int)$action;
            error_log("[CONTROLLER] GET_DOCUMENT_BY_VERSION ID = $versionId");
            logAction("GET_DOCUMENT_BY_VERSION", ['version_id' => $versionId]);
            
            // Trouver le document associé à cette version
            $doc = $this->documents->findByVersionId($versionId);
            
            if (!$doc) {
                error_log("[CONTROLLER] Version $versionId non trouvée");
                return ['error' => 'Document non trouvé', 'code' => 404];
            }

            error_log("[CONTROLLER] Document trouvé pour version $versionId");
            return ['document' => $doc];
        }

        // GET /api/documents/123 -> un document spécifique avec ses versions
        if ($id) {
            $id = (int)$id;
            error_log("========================================");
            error_log("[CONTROLLER] GET_DOCUMENT ID = $id");
            logAction("GET_DOCUMENT", ['id' => $id]);
            $doc = $this->documents->findByIdWithVersions($id);
            
            error_log("[CONTROLLER] Document retourné: " . ($doc ? "OK" : "NULL"));
            if ($doc && isset($doc['availableVersions'])) {
                error_log("[CONTROLLER] Versions: " . count($doc['availableVersions']) . " versions");
            }
            error_log("========================================");
            
            if (!$doc) {
                return ['error' => 'Document non trouvé', 'code' => 404];
            }

            return ['document' => $doc];
        }

        return ["error" => "Paramètres invalides"];
    }

    protected function processPostRequest(HttpRequest $request): ?array
    {
        $data = $request->getJson();

        // Validation des données requises
        $required = ['nom_fichier', 'type_fichier'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['error' => "Champ requis: $field", 'code' => 400];
            }
        }

        // ✅ CORRECTION: Récupérer l'ID utilisateur depuis le token JWT, pas du client
        $userId = $this->checkAuth();
        
        // Si pas d'authentification, fallback vers user_id du JSON (mode démo)
        if (!$userId) {
            $userId = (int)($data['user_id'] ?? 0);
            error_log("[DOC] ⚠️  Pas de token, utilisant user_id du JSON: $userId");
        }

        $nomFichier = sanitizeString($data['nom_fichier']);
        $titre = sanitizeString($data['titre'] ?? '');
        $typeFichier = sanitizeString($data['type_fichier']);
        $description = sanitizeString($data['description'] ?? '');

        error_log("========== DOCUMENT CONTROLLER ==========");
        error_log("[DOC] 📄 CRÉATION DE DOCUMENT");
        error_log("[DOC] 👤 ID Utilisateur: $userId");
        error_log("[DOC] 📝 Nom fichier: $nomFichier");
        error_log("[DOC] 📂 Type: $typeFichier");
        error_log("[DOC] 📋 Titre: " . ($titre ?: '(non fourni)'));
        error_log("[DOC] 📖 Description: " . ($description ?: '(aucune)'));
        error_log("=========================================");

        // Vérifier que l'utilisateur existe en base de données
        if ($userId > 0) {
            $userExists = $this->users->findById($userId);
            if (!$userExists) {
                error_log("[DOC] ❌ ERREUR: Utilisateur $userId n'existe pas en base de données!");
                error_log("[DOC] Tentative de vérification en base...");
                
                // Vérifier en SQL brut au cas où
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $directUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$directUser) {
                    error_log("[DOC] ✅ Confirmé: Utilisateur $userId n'existe absolument pas en BDD");
                    return [
                        'error' => "Utilisateur $userId n'existe pas en base de données",
                        'code' => 400,
                        'details' => "L'utilisateur avec l'ID $userId n'a pas pu être trouvé. Vérifiez l'authentification."
                    ];
                } else {
                    error_log("[DOC] ⚠️  ATTENTION: Utilisateur $userId trouvé en BDD directe: {$directUser['username']}");
                }
            } else {
                error_log("[DOC] ✅ Utilisateur $userId vérifié en base: {$userExists['username']}");
            }
        }

        logAction("CREATE_DOCUMENT", ['user_id' => $userId, 'nom_fichier' => $nomFichier]);

        // Créer le document
        try {
            $docId = $this->documents->create(
                $userId,
                $nomFichier,
                $titre,
                $typeFichier,
                $description
            );
        } catch (PDOException $e) {
            error_log("[DOC] ❌ Erreur création document: " . $e->getMessage());
            return [
                'error' => 'Erreur lors de la création du document',
                'code' => 500
            ];
        }

        if ($docId <= 0) {
            error_log("[DOC] ❌ ERREUR: Impossible de créer le document (ID retourné: $docId)");
            return [
                'error' => 'Impossible de créer le document en base de données',
                'code' => 500,
                'details' => 'L\'insertion du document a échoué. Vérifiez que l\'utilisateur existe.'
            ];
        }

        error_log("[DOC] ✅ Document créé avec ID: $docId");

        // Si url_fichier est fourni, créer la première version (1.0)
        if (!empty($data['url_fichier'])) {
            $urlFichier = sanitizeString($data['url_fichier']);
            $this->documents->addVersion($docId, $urlFichier);
            error_log("[DOC] 📌 Première version créée: $urlFichier");
            logAction("ADD_FIRST_VERSION", ['docId' => $docId, 'url_fichier' => $urlFichier]);
        }

        error_log("[DOC] 🎉 Document et première version prêts");
        
        // Envoyer les emails de notification aux professeurs abonnés
        $emailSent = false;
        $recipientsCount = 0;
        $emailError = null;
        $recipientEmails = [];
        $emailLogs = []; // Initialiser les logs email
        
        if ($userId > 0) {
            // Recuperer les infos du document et du proprietaire
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT d.id, d.user_id, d.nom_fichier, d.titre FROM documents d WHERE d.id = ?");
            $stmt->execute([$docId]);
            $docWithOwner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($docWithOwner && isset($docWithOwner['user_id'])) {
                $stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($student) {
                    // Recuperer les infos des professeurs abonnes (email + username)
                    // Utiliser du SQL brut au lieu de Repository pour eviter les erreurs
                    $stmt = $db->prepare("SELECT u.id, u.email, u.username FROM abonnement a INNER JOIN users u ON a.id_prof = u.id WHERE a.id_user = ? AND u.email IS NOT NULL");
                    $stmt->execute([$userId]);
                    $profInfos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log("[DOC] Profs abonnes trouves: " . count($profInfos));
                    
                    if (!empty($profInfos)) {
                        // Extraire les emails pour l'envoi
                        $profEmails = array_column($profInfos, 'email');
                        
                        // Envoyer un email à chaque professeur abonné
                        $result = $this->emailService->sendNewDocumentNotification(
                            $student['email'],
                            $student['username'],
                            $docWithOwner['titre'] ?: $docWithOwner['nom_fichier'],
                            $profEmails
                        );
                        
                        // Récupérer les logs du service email
                        if (isset($result['logs'])) {
                            $emailLogs = $result['logs'];
                        }
                        
                        if ($result['success']) {
                            $emailSent = true;
                            $recipientsCount = count($profInfos);
                            // Formater les infos des destinataires
                            foreach ($profInfos as $prof) {
                                $recipientEmails[] = [
                                    'email' => $prof['email'],
                                    'name' => $prof['username']
                                ];
                            }
                            error_log("[DOC] ✅ Notifications d'emails déclenchées avec succès");
                        } else {
                            $emailError = $result['error'] ?? 'Erreur inconnue';
                            error_log("[DOC] ⚠️ Erreur lors de l'envoi des notifications: $emailError");
                        }
                    } else {
                        error_log("[DOC] ℹ️ Aucun professeur abonné trouvé");
                    }
                }
            }
        }
        
        error_log("========================================= ");

        $response = [
            'message' => 'Document créé avec succès',
            'id' => $docId,
            'email_sent' => $emailSent,
            'recipients_count' => $recipientsCount,
            'recipient_emails' => $recipientEmails
        ];
        
        if ($emailError) {
            $response['email_error'] = $emailError;
        }
        
        // Toujours inclure les logs du service d'email s'ils existent
        if (!empty($emailLogs)) {
            $response['logs'] = $emailLogs;
        }
        
        return $response;
    }

    protected function processPutRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();

        if (!$id) {
            return ['error' => 'ID du document requis', 'code' => 400];
        }

        $id = (int)$id;
        $data = $request->getJson();
        $action = $request->getAction();

        // Vérifier que le document existe
        $doc = $this->documents->findById($id);
        if (!$doc) {
            return ['error' => 'Document non trouvé', 'code' => 404];
        }

        // PUT /api/documents/123/version -> créer une nouvelle version
        if ($action === 'version') {
            if (empty($data['url_fichier'])) {
                return ['error' => 'URL fichier requise', 'code' => 400];
            }

            $urlFichier = sanitizeString($data['url_fichier']);

            logAction("ADD_VERSION", ['id' => $id, 'url_fichier' => $urlFichier]);

            $this->documents->addVersion($id, $urlFichier);

            // Initialiser les variables pour la réponse
            $emailSent = false;
            $recipientsCount = 0;
            $emailError = null;

            // Récupérer les infos du document et du propriétaire
            $docWithOwner = $this->documents->findByIdWithOwner($id);
            if ($docWithOwner && isset($docWithOwner['user_id'])) {
                $studentId = $docWithOwner['user_id'];
                $student = $this->users->findById($studentId);
                
                if ($student) {
                    // Récupérer les emails des professeurs abonnés
                    $profEmails = $this->abonnements->getProfEmailsByUser($studentId);
                    
                    error_log("========== DOCUMENT CONTROLLER ==========");
                    error_log("[DOC] 📄 Nouvelle version du document #$id");
                    error_log("[DOC] 👤 Étudiant: {$student['username']} ({$student['email']})");
                    error_log("[DOC] 📋 Document: " . ($docWithOwner['titre'] ?: $docWithOwner['nom_fichier']));
                    error_log("[DOC] 📨 Profs abonnés trouvés: " . count($profEmails));
                    error_log("=========================================");
                    
                    if (!empty($profEmails)) {
                        // Envoyer un email à chaque professeur abonné
                        $result = $this->emailService->sendNewDocumentNotification(
                            $student['email'],
                            $student['username'],
                            $docWithOwner['titre'] ?: $docWithOwner['nom_fichier'],
                            $profEmails
                        );
                        
                        if ($result['success']) {
                            $emailSent = true;
                            $recipientsCount = count($profEmails);
                            error_log("[DOC] ✅ Notifications d'emails déclenchées avec succès");
                        } else {
                            $emailError = $result['error'] ?? 'Erreur inconnue';
                            error_log("[DOC] ⚠️ Erreur lors de l'envoi des notifications: $emailError");
                        }
                    } else {
                        error_log("[DOC] ⚠️ Aucun professeur abonné trouvé");
                    }
                }
            }

            $response = [
                'message' => 'Nouvelle version créée avec succès',
                'email_sent' => $emailSent,
                'recipients_count' => $recipientsCount
            ];
            
            if ($emailError) {
                $response['email_error'] = $emailError;
            }
            
            return $response;
        }

        // PUT /api/documents/123 -> mettre à jour le document
        // Mettre à jour seulement les champs autorisés
        $updateData = [];
        if (isset($data['titre'])) {
            $updateData['titre'] = sanitizeString($data['titre']);
        }
        if (isset($data['description'])) {
            $updateData['description'] = sanitizeString($data['description']);
        }

        if (empty($updateData)) {
            return ['error' => 'Aucune données à mettre à jour', 'code' => 400];
        }

        logAction("UPDATE_DOCUMENT", ['id' => $id, 'fields' => array_keys($updateData)]);

        $this->documents->update($id, $updateData);

        return ['message' => 'Document mis à jour avec succès'];
    }

    protected function processDeleteRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();

        if (!$id) {
            return ['error' => 'ID du document requis', 'code' => 400];
        }

        $id = (int)$id;

        // Vérifier que le document existe
        $doc = $this->documents->findById($id);
        if (!$doc) {
            return ['error' => 'Document non trouvé', 'code' => 404];
        }

        logAction("DELETE_DOCUMENT", ['id' => $id]);

        $this->documents->delete($id);

        return ['message' => 'Document supprimé avec succès'];
    }

    /**
     * Vérifie l'authentification via JWT et retourne l'ID utilisateur
     */
    private function checkAuth(): ?int
    {
        $token = $this->getAuthToken();
        if (!$token) {
            error_log("[DOC] ❌ Pas de token trouvé dans Authorization header");
            return null;
        }

        try {
            // Décoder le payload du JWT
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                error_log("[DOC] ❌ Token JWT invalide (ne contient pas 3 parties)");
                return null;
            }

            // Récupérer et décoder le payload (partie 2)
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            
            if (!$payload || !isset($payload['sub'])) {
                error_log("[DOC] ❌ Payload du token invalide ou sans 'sub'");
                return null;
            }

            $userId = (int)$payload['sub'];
            error_log("[DOC] ✅ Token décodé avec succès. User ID: $userId");
            return $userId;
        } catch (Exception $e) {
            error_log("[DOC] ❌ Erreur décodage token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère le token JWT depuis l'en-tête Authorization
     */
    private function getAuthToken(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            return null;
        }

        return substr($authHeader, 7); // Enlever "Bearer "
    }
}
