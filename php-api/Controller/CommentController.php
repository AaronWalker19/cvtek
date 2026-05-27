<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/CommentRepository.php';
require_once __DIR__ . '/../Repository/UserRepository.php';
require_once __DIR__ . '/../Repository/DocumentRepository.php';
require_once __DIR__ . '/../Service/EmailService.php';

/**
 * CommentController
 * Gère les commentaires (list, get, create, update, delete)
 */
class CommentController extends Controller
{
    private CommentRepository $comments;
    private UserRepository $users;
    private DocumentRepository $documents;
    private EmailService $emailService;

    public function __construct()
    {
        $this->comments = new CommentRepository();
        $this->users = new UserRepository();
        $this->documents = new DocumentRepository();
        $this->emailService = new EmailService();
    }

    protected function processGetRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();
        $action = $request->getAction();
        $docVersionId = $request->getParam('doc_version_id');
        $userId = $request->getParam('user_id');

        // GET /api/comments -> tous les commentaires
        if (!$id && !$docVersionId && !$userId) {
            logAction("GET_ALL_COMMENTS", ['requester' => 'anonymous']);
            $allComments = $this->comments->findByUserId(999999); // Fake user to get nothing without auth
            return [
                'count' => 0,
                'comments' => [],
                'message' => 'Utilisez doc_version_id ou user_id pour filtrer'
            ];
        }

        // GET /api/comments?doc_version_id=123 -> commentaires d'une version
        if (!$id && $docVersionId) {
            $docVersionId = (int)$docVersionId;
            logAction("GET_COMMENTS_BY_VERSION", ['docVersionId' => $docVersionId]);
            $comments = $this->comments->findByDocVersionId($docVersionId);
            return [
                'count' => count($comments),
                'comments' => $comments,
            ];
        }

        // GET /api/comments?user_id=123 -> commentaires d'un utilisateur
        if (!$id && $userId) {
            $userId = (int)$userId;
            logAction("GET_COMMENTS_BY_USER", ['userId' => $userId]);
            $comments = $this->comments->findByUserId($userId);
            return [
                'count' => count($comments),
                'comments' => $comments,
            ];
        }

        // GET /api/comments/123 -> un commentaire spécifique
        if ($id) {
            $id = (int)$id;
            logAction("GET_COMMENT", ['id' => $id]);
            $comment = $this->comments->findById($id);
            
            if ($comment) {
                return [
                    'comment' => $comment
                ];
            } else {
                return ['error' => 'Commentaire non trouvé'];
            }
        }

        return null;
    }

    protected function processPostRequest(HttpRequest $request): ?array
    {
        // En MODE DÉMO: utiliser l'ID 17 (professeur) par défaut pour les commentaires
        // En MODE DÉMO: essayer d'abord de récupérer l'ID utilisateur depuis le token
        $userId = $this->checkAuth();
        
        // Fallback: utiliser l'ID du professeur en démo
        if (!$userId) {
            $userId = 17; // ID du professeur en démo
        }
        
        $data = $request->getJson();
        $docVersionId = $data['id_docversion'] ?? null;
        $text = $data['text'] ?? null;

        // Validation
        if (!$docVersionId || !$text) {
            error_log("========== COMMENT CONTROLLER ==========");
            error_log("[COM] ❌ VALIDATION ÉCHOUÉE");
            error_log("[COM] Paramètres manquants: id_docversion=$docVersionId, text=" . ($text ? 'OK' : 'MANQUANT'));
            error_log("=========================================");
            return ['error' => 'Paramètres manquants: id_docversion, text'];
        }

        $docVersionId = (int)$docVersionId;
        $text = trim($text);

        if (empty($text)) {
            error_log("========== COMMENT CONTROLLER ==========");
            error_log("[COM] ❌ VALIDATION ÉCHOUÉE: Texte vide");
            error_log("=========================================");
            return ['error' => 'Le texte du commentaire ne peut pas être vide'];
        }

        error_log("========== COMMENT CONTROLLER ==========");
        error_log("[COM] 💬 CRÉATION COMMENTAIRE");
        error_log("[COM] 👤 Professeur ID: $userId");
        error_log("[COM] 📌 Version ID: $docVersionId");
        error_log("[COM] 📝 Texte: " . substr($text, 0, 60) . (strlen($text) > 60 ? '...' : ''));
        error_log("[COM] 📊 Longueur: " . strlen($text) . " caractères");
        error_log("=========================================");

        logAction("CREATE_COMMENT", [
            'userId' => $userId,
            'docVersionId' => $docVersionId,
            'textLength' => strlen($text)
        ]);

        $comment = $this->comments->create($userId, $docVersionId, $text);

        if ($comment) {
            error_log("[COM] ✅ Commentaire créé avec ID: {$comment['id']}");
            
            // Envoyer un email de notification à l'étudiant
            error_log("[COM] 📧 Envoi de l'email de notification...");
            $emailInfo = $this->sendCommentNotificationEmail($userId, $docVersionId, $text);
            
            $response = [
                'message' => 'Commentaire créé',
                'comment' => $comment,
                'email_sent' => $emailInfo['success'],
                'email_recipient' => $emailInfo['student_email'],
                'email_recipient_name' => $emailInfo['student_name'],
                'email_sender_name' => $emailInfo['professor_name']
            ];
            
            // Ajouter le message d'erreur s'il y en a un
            if (!$emailInfo['success'] && isset($emailInfo['error'])) {
                $response['email_error'] = $emailInfo['error'];
                error_log("[COM] ⚠️ Email non envoyé: " . $emailInfo['error']);
            } else if ($emailInfo['success']) {
                error_log("[COM] ✅ Email envoyé avec succès");
            }
            error_log("=========================================");
            
            return $response;
        } else {
            error_log("[COM] ❌ Erreur lors de la création du commentaire");
            error_log("=========================================");
            return ['error' => 'Erreur lors de la création du commentaire'];
        }
    }

    protected function processPutRequest(HttpRequest $request): ?array
    {
        // En MODE DÉMO: essayer d'abord de récupérer l'ID utilisateur depuis le token
        $userId = $this->checkAuth();
        
        // Fallback: utiliser l'ID du professeur en démo
        if (!$userId) {
            $userId = 17; // ID du professeur en démo
        }
        
        $id = $request->getId();
        if (!$id) {
            return ['error' => 'ID du commentaire requis'];
        }

        $id = (int)$id;
        $comment = $this->comments->findById($id);

        if (!$comment) {
            return ['error' => 'Commentaire non trouvé'];
        }

        // En mode démo, ne pas vérifier la propriété du commentaire
        // if ($comment['id_user'] != $userId) {
        //     return ['error' => 'Vous n\'êtes pas autorisé à modifier ce commentaire'];
        // }

        $data = $request->getJson();
        $text = $data['text'] ?? null;

        if (!$text) {
            return ['error' => 'Paramètre requis: text'];
        }

        $text = trim($text);

        if (empty($text)) {
            return ['error' => 'Le texte du commentaire ne peut pas être vide'];
        }

        logAction("UPDATE_COMMENT", [
            'id' => $id,
            'userId' => $userId
        ]);

        $success = $this->comments->update($id, $text);

        if ($success) {
            return [
                'message' => 'Commentaire mis à jour',
                'comment' => $this->comments->findById($id)
            ];
        } else {
            return ['error' => 'Erreur lors de la mise à jour du commentaire'];
        }
    }

    protected function processDeleteRequest(HttpRequest $request): ?array
    {
        // En MODE DÉMO: utiliser l'ID 17 (professeur) par défaut
        $userId = 17;
        
        // Optionnel: vérifier l'authentification pour les systèmes réels
        // $userId = $this->checkAuth();
        // if (!$userId) {
        //     return ['error' => 'Non authentifié'];
        // }

        $id = $request->getId();
        if (!$id) {
            return ['error' => 'ID du commentaire requis'];
        }

        $id = (int)$id;
        $comment = $this->comments->findById($id);

        if (!$comment) {
            return ['error' => 'Commentaire non trouvé'];
        }

        // En mode démo, ne pas vérifier la propriété du commentaire
        // if ($comment['id_user'] != $userId) {
        //     $userRole = $this->getUserRole();
        //     if ($userRole !== 'admin' && $userRole !== 'professor') {
        //         return ['error' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire'];
        //     }
        // }

        logAction("DELETE_COMMENT", [
            'id' => $id,
            'userId' => $userId
        ]);

        $success = $this->comments->delete($id);

        if ($success) {
            return ['message' => 'Commentaire supprimé'];
        } else {
            return ['error' => 'Erreur lors de la suppression du commentaire'];
        }
    }

    /**
     * Envoie un email de notification de nouveau commentaire
     */
    private function sendCommentNotificationEmail(int $profId, int $docVersionId, string $comment): array
    {
        $result = [
            'success' => false,
            'student_email' => null,
            'student_name' => null,
            'professor_name' => null,
            'document_title' => null,
            'error' => null
        ];
        
        try {
            error_log("========== COMMENT CONTROLLER ===========");
            error_log("[COM] Nouveau commentaire cree");
            error_log("[COM] ID Version: $docVersionId");
            error_log("[COM] ID Prof: $profId");
            
            // Get database connection
            $db = Database::getConnection();
            
            // Recuperer le document associe a cette version
            $stmt = $db->prepare("SELECT d.id, d.user_id, d.nom_fichier, d.titre FROM documents d INNER JOIN doc_version dv ON dv.id_doc = d.id WHERE dv.id = ?");
            $stmt->execute([$docVersionId]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doc) {
                error_log("[COM] Document non trouve pour version $docVersionId");
                error_log("=========================================");
                return $result;
            }

            error_log("[COM] Document trouve - ID: {$doc['id']}, user_id: {$doc['user_id']}");

            $studentId = $doc['user_id'];
            
            if (!$studentId) {
                error_log("[COM] Document n'a pas de user_id!");
                error_log("=========================================");
                return $result;
            }
            
            // Recuperer les infos de l'etudiant
            $stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$student) {
                error_log("[COM] Etudiant non trouve - ID: $studentId");
                error_log("=========================================");
                return $result;
            }
            
            if (!$student['email']) {
                error_log("[COM] Etudiant trouve ({$student['username']}) mais sans email!");
                error_log("=========================================");
                return $result;
            }
            
            $result['student_email'] = $student['email'];
            $result['student_name'] = $student['username'];
            error_log("[COM] Etudiant: {$student['username']} ({$student['email']})");

            // Recuperer les infos du professeur
            $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([$profId]);
            $professor = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$professor) {
                error_log("[COM] Professeur non trouve (ID: $profId)");
                error_log("=========================================");
                return $result;
            }
            
            $result['professor_name'] = $professor['username'];
            error_log("[COM] Professeur: {$professor['username']}");
            
            $documentTitle = $doc['titre'] ?: $doc['nom_fichier'];
            $result['document_title'] = $documentTitle;
            error_log("[COM] Document: $documentTitle");

            // Envoyer l'email
            $emailResult = $this->emailService->sendNewCommentNotification(
                $student['email'],
                $student['username'],
                $professor['username'],
                $documentTitle,
                $comment
            );
            
            if ($emailResult['success']) {
                $result['success'] = true;
                error_log("[COM] Email de notification envoye avec succes");
            } else {
                $result['error'] = $emailResult['error'] ?? 'Erreur inconnue lors de l\'envoi d\'email';
                error_log("[COM] Erreur lors de l'envoi de l'email: " . $result['error']);
            }
            error_log("=========================================");
            
            return $result;
        } catch (Exception $e) {
            error_log("[COM] Exception: " . $e->getMessage());
            error_log("=========================================");
            return $result;
        }
    }

    private function checkAuth(): ?int
    {
        $token = $this->getAuthToken();
        if (!$token) {
            error_log("❌ Pas de token trouvé dans Authorization header");
            return null;
        }

        try {
            // Décoder le payload du JWT
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                error_log("❌ Token JWT invalide (ne contient pas 3 parties)");
                return null;
            }

            // Récupérer et décoder le payload (partie 2)
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            
            if (!$payload || !isset($payload['sub'])) {
                error_log("❌ Payload du token invalide ou sans 'sub'");
                return null;
            }

            $userId = (int)$payload['sub'];
            error_log("✅ Token décodé avec succès. User ID: $userId");
            return $userId;
        } catch (Exception $e) {
            error_log("❌ Erreur décodage token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère le rôle de l'utilisateur depuis le token JWT
     */
    private function getUserRole(): ?string
    {
        $token = $this->getAuthToken();
        if (!$token) {
            return null;
        }

        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            return $payload['role'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Récupère le token JWT depuis le header Authorization
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
