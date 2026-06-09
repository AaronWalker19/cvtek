<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/UploadRepository.php';

/**
 * UploadController
 * Gère l'upload et suppression de fichiers
 */
class UploadController extends Controller
{
    private UploadRepository $uploads;

    public function __construct()
    {
        $this->uploads = new UploadRepository();
    }

    protected function processPostRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();

        // POST /api/upload -> Upload de fichier (multipart/form-data)
        if (!$id) {
            return $this->handleUpload($request);
        }

        // POST /api/upload/{id}/delete -> Supprimer un upload
        if ($id === 'delete') {
            return $this->handleDeleteFile($request);
        }

        return ["error" => "Endpoint non trouvé"];
    }

    protected function processDeleteRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();

        if (!$id) {
            return ['error' => 'ID du fichier requis', 'code' => 400];
        }

        // DELETE /api/upload/{id} -> Supprimer un upload
        return $this->handleDeleteById((int)$id);
    }

    protected function processGetRequest(HttpRequest $request): ?array
    {
        $userId = $request->getParam('user_id');

        // GET /api/upload?user_id=123 -> Liste des uploads d'un utilisateur
        if ($userId) {
            $userId = (int)$userId;
            logAction("GET_USER_UPLOADS", ['userId' => $userId]);
            $uploads = $this->uploads->findByUserId($userId);
            return [
                'count' => count($uploads),
                'uploads' => $uploads,
            ];
        }

        return ['error' => 'user_id requis', 'code' => 400];
    }

    /**
     * Gère l'upload de fichier
     */
    private function handleUpload(HttpRequest $request): ?array
    {
        if (empty($_FILES['file'])) {
            return ['error' => 'Aucun fichier n\'a été envoyé', 'code' => 400];
        }

        $uploadedFile = $_FILES['file'];
        $fileName = sanitizeFileName($uploadedFile['name']);
        $fileSize = (int)$uploadedFile['size'];
        $fileTmp = $uploadedFile['tmp_name'];
        $fileError = $uploadedFile['error'];
        $mimeType = $uploadedFile['type'] ?? '';

        // Vérifier les erreurs d'upload
        if ($fileError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite upload_max_filesize en php.ini',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
                UPLOAD_ERR_NO_TMP_DIR => 'Le répertoire temporaire est manquant',
                UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier sur le disque',
                UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement',
            ];
            $errorMsg = $errorMessages[$fileError] ?? "Erreur d'upload inconnue: $fileError";
            return ['error' => $errorMsg, 'code' => 400];
        }

        // Vérifier la taille
        if ($fileSize > MAX_UPLOAD_SIZE) {
            return ['error' => 'Le fichier est trop volumineux', 'code' => 413];
        }

        // Vérifier l'extension
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            return ['error' => "Type de fichier non autorisé: $ext", 'code' => 415];
        }

        // Récupérer l'ID utilisateur
        $userId = (int)($_POST['user_id'] ?? 0);

        // Créer un nom unique au format: upload_USERID-JJMMAAAAHHMM.ext
        $now = new DateTime('now');
        $timestamp = $now->format('dmYHi'); // jjmmaaaahhmm
        $uniqueName = 'upload_' . $userId . '-' . $timestamp . '.' . $ext;
        $uploadPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $uniqueName;

        // Vérifier que le répertoire existe
        if (!is_dir(UPLOAD_DIR)) {
            if (!@mkdir(UPLOAD_DIR, 0755, true)) {
                return ['error' => "Impossible de créer le répertoire: " . UPLOAD_DIR, 'code' => 500];
            }
        }

        // Vérifier que le répertoire est accessible en écriture
        if (!is_writable(UPLOAD_DIR)) {
            return ['error' => "Le répertoire n'est pas accessible en écriture: " . UPLOAD_DIR, 'code' => 500];
        }

        // Vérifier que le fichier temporaire existe
        if (!is_uploaded_file($fileTmp)) {
            return ['error' => "Le fichier temporaire n'existe pas", 'code' => 500];
        }

        // Déplacer le fichier
        if (!move_uploaded_file($fileTmp, $uploadPath)) {
            return ['error' => "Erreur lors du déplacement du fichier vers: " . $uploadPath, 'code' => 500];
        }

        logAction("FILE_UPLOADED", [
            'user_id' => $userId,
            'file' => $uniqueName,
            'size' => $fileSize,
            'mime' => $mimeType,
        ]);

        // Enregistrer en BD (optionnel)
        $this->uploads->logUpload($userId, $fileName, $uniqueName, $fileSize, $mimeType);

        return [
            'message' => 'Fichier uploadé avec succès',
            'file' => $uniqueName,
            'original_name' => $fileName,
            'size' => $fileSize,
            'url' => '/cvtek/uploads/' . $uniqueName,
            'type' => $ext,
        ];
    }

    /**
     * Gère la suppression d'un fichier
     */
    private function handleDeleteFile(HttpRequest $request): ?array
    {
        $uniqueName = $request->getParam('file');

        if (!$uniqueName) {
            return ['error' => 'Nom du fichier requis', 'code' => 400];
        }

        $uniqueName = sanitizeFileName($uniqueName);
        $filePath = UPLOAD_DIR . '/' . $uniqueName;

        // Sécurité: vérifier que le chemin est dans UPLOAD_DIR
        $realPath = realpath($filePath);
        $realUploadDir = realpath(UPLOAD_DIR);

        if ($realPath === false || strpos($realPath, $realUploadDir) !== 0) {
            return ['error' => 'Accès refusé', 'code' => 403];
        }

        if (!file_exists($filePath)) {
            return ['error' => 'Fichier non trouvé', 'code' => 404];
        }

        if (!unlink($filePath)) {
            return ['error' => 'Erreur lors de la suppression', 'code' => 500];
        }

        logAction("FILE_DELETED", ['file' => $uniqueName]);

        // Supprimer de la BD aussi
        $this->uploads->deleteByUniqueName($uniqueName);

        return ['message' => 'Fichier supprimé avec succès'];
    }

    /**
     * Gère la suppression par ID
     */
    private function handleDeleteById(int $id): ?array
    {
        $upload = $this->uploads->findById($id);

        if (!$upload) {
            return ['error' => 'Upload non trouvé', 'code' => 404];
        }

        $filePath = UPLOAD_DIR . '/' . $upload['unique_name'];

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        logAction("FILE_DELETED_BY_ID", ['id' => $id, 'file' => $upload['unique_name']]);

        $this->uploads->delete($id);

        return ['message' => 'Fichier supprimé avec succès'];
    }
}
