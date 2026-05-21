<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/CommentRepository.php';

/**
 * CommentController
 * Gère les commentaires (list, get, create, update, delete)
 */
class CommentController extends Controller
{
    private CommentRepository $comments;

    public function __construct()
    {
        $this->comments = new CommentRepository();
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
            return ['error' => 'Paramètres manquants: id_docversion, text'];
        }

        $docVersionId = (int)$docVersionId;
        $text = trim($text);

        if (empty($text)) {
            return ['error' => 'Le texte du commentaire ne peut pas être vide'];
        }

        logAction("CREATE_COMMENT", [
            'userId' => $userId,
            'docVersionId' => $docVersionId,
            'textLength' => strlen($text)
        ]);

        $comment = $this->comments->create($userId, $docVersionId, $text);

        if ($comment) {
            return [
                'message' => 'Commentaire créé',
                'comment' => $comment
            ];
        } else {
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
     * Vérifie l'authentification et retourne l'ID utilisateur depuis le token JWT
     */
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
