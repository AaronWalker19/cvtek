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
                return [
                    'success' => false,
                    'error' => 'Commentaire non trouvé'
                ];
            }
        }

        return null;
    }

    protected function processPostRequest(HttpRequest $request): ?array
    {
        // Vérifier l'authentification
        $userId = $this->checkAuth();
        if (!$userId) {
            return [
                'success' => false,
                'error' => 'Non authentifié'
            ];
        }

        $data = $request->getBody();
        $docVersionId = $data['id_docversion'] ?? null;
        $text = $data['text'] ?? null;

        // Validation
        if (!$docVersionId || !$text) {
            return [
                'success' => false,
                'error' => 'Paramètres manquants: id_docversion, text'
            ];
        }

        $docVersionId = (int)$docVersionId;
        $text = trim($text);

        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'Le texte du commentaire ne peut pas être vide'
            ];
        }

        logAction("CREATE_COMMENT", [
            'userId' => $userId,
            'docVersionId' => $docVersionId,
            'textLength' => strlen($text)
        ]);

        $comment = $this->comments->create($userId, $docVersionId, $text);

        if ($comment) {
            return [
                'success' => true,
                'message' => 'Commentaire créé',
                'comment' => $comment
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Erreur lors de la création du commentaire'
            ];
        }
    }

    protected function processPutRequest(HttpRequest $request): ?array
    {
        // Vérifier l'authentification
        $userId = $this->checkAuth();
        if (!$userId) {
            return [
                'success' => false,
                'error' => 'Non authentifié'
            ];
        }

        $id = $request->getId();
        if (!$id) {
            return [
                'success' => false,
                'error' => 'ID du commentaire requis'
            ];
        }

        $id = (int)$id;
        $comment = $this->comments->findById($id);

        if (!$comment) {
            return [
                'success' => false,
                'error' => 'Commentaire non trouvé'
            ];
        }

        // Vérifier que l'utilisateur est le propriétaire du commentaire
        if ($comment['id_user'] != $userId) {
            return [
                'success' => false,
                'error' => 'Vous n\'êtes pas autorisé à modifier ce commentaire'
            ];
        }

        $data = $request->getBody();
        $text = $data['text'] ?? null;

        if (!$text) {
            return [
                'success' => false,
                'error' => 'Paramètre requis: text'
            ];
        }

        $text = trim($text);

        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'Le texte du commentaire ne peut pas être vide'
            ];
        }

        logAction("UPDATE_COMMENT", [
            'id' => $id,
            'userId' => $userId
        ]);

        $success = $this->comments->update($id, $text);

        if ($success) {
            return [
                'success' => true,
                'message' => 'Commentaire mis à jour',
                'comment' => $this->comments->findById($id)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Erreur lors de la mise à jour du commentaire'
            ];
        }
    }

    protected function processDeleteRequest(HttpRequest $request): ?array
    {
        // Vérifier l'authentification
        $userId = $this->checkAuth();
        if (!$userId) {
            return [
                'success' => false,
                'error' => 'Non authentifié'
            ];
        }

        $id = $request->getId();
        if (!$id) {
            return [
                'success' => false,
                'error' => 'ID du commentaire requis'
            ];
        }

        $id = (int)$id;
        $comment = $this->comments->findById($id);

        if (!$comment) {
            return [
                'success' => false,
                'error' => 'Commentaire non trouvé'
            ];
        }

        // Vérifier que l'utilisateur est le propriétaire du commentaire ou admin
        if ($comment['id_user'] != $userId) {
            $userRole = $this->getUserRole();
            if ($userRole !== 'admin' && $userRole !== 'professor') {
                return [
                    'success' => false,
                    'error' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire'
                ];
            }
        }

        logAction("DELETE_COMMENT", [
            'id' => $id,
            'userId' => $userId
        ]);

        $success = $this->comments->delete($id);

        if ($success) {
            return [
                'success' => true,
                'message' => 'Commentaire supprimé'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Erreur lors de la suppression du commentaire'
            ];
        }
    }

    /**
     * Récupère le rôle de l'utilisateur depuis le token
     */
    private function getUserRole(): ?string
    {
        $token = $this->getAuthToken();
        if (!$token) {
            return null;
        }

        $userData = json_decode(base64_decode(explode('.', $token)[1]), true);
        return $userData['role'] ?? null;
    }
}
