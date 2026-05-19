<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/DocumentRepository.php';

/**
 * DocumentController
 * Gère les documents (list, get, create, update, delete)
 */
class DocumentController extends Controller
{
    private DocumentRepository $documents;

    public function __construct()
    {
        $this->documents = new DocumentRepository();
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

        // GET /api/documents/123 -> un document spécifique avec ses versions
        if ($id) {
            $id = (int)$id;
            logAction("GET_DOCUMENT", ['id' => $id]);
            $doc = $this->documents->findByIdWithVersions($id);
            
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

        // En développement, on accepte user_id optionnel
        $userId = (int)($data['user_id'] ?? 0);
        $nomFichier = sanitizeString($data['nom_fichier']);
        $titre = sanitizeString($data['titre'] ?? '');
        $typeFichier = sanitizeString($data['type_fichier']);
        $description = sanitizeString($data['description'] ?? '');

        logAction("CREATE_DOCUMENT", ['user_id' => $userId, 'nom_fichier' => $nomFichier]);

        // Créer le document
        $docId = $this->documents->create(
            $userId,
            $nomFichier,
            $titre,
            $typeFichier,
            $description
        );

        // Si url_fichier est fourni, créer la première version (1.0)
        if (!empty($data['url_fichier'])) {
            $urlFichier = sanitizeString($data['url_fichier']);
            $this->documents->addVersion($docId, $urlFichier);
            logAction("ADD_FIRST_VERSION", ['docId' => $docId, 'url_fichier' => $urlFichier]);
        }

        return [
            'message' => 'Document créé avec succès',
            'id' => $docId,
        ];
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

            return ['message' => 'Nouvelle version créée avec succès'];
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
}
