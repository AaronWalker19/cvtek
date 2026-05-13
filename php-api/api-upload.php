<?php
/**
 * api-upload.php - Endpoint direct pour l'upload de fichiers
 * 
 * URLs:
 * POST https://mmi.unilim.fr/~valin6/cvtek/api/api-upload.php?action=file
 * POST https://mmi.unilim.fr/~valin6/cvtek/api/api-upload.php?action=delete&file=nom
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
setCorsHeaders();
handleCorsPreFlight();

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    switch ($action) {
        case 'file':
            handleUploadFile();
            break;
            
        case 'delete':
            handleDeleteFile();
            break;
            
        default:
            jsonSuccess([
                'message' => 'Bienvenue sur l\'API Upload CVTEK',
                'actions' => [
                    'file' => 'POST /api-upload.php?action=file (multipart/form-data)',
                    'delete' => 'POST /api-upload.php?action=delete&file=nom',
                ],
                'constraints' => [
                    'max_size' => MAX_UPLOAD_SIZE . ' bytes',
                    'allowed_types' => ALLOWED_EXTENSIONS,
                ]
            ]);
    }
    
} catch (Exception $e) {
    logAction("UPLOAD API ERROR", ['error' => $e->getMessage()]);
    jsonError($e->getMessage(), 500);
}

// ===============================================
// Handlers
// ===============================================

function handleUploadFile(): void {
    // ✅ DÉVELOPPEMENT: upload sans authentification
    
    if (empty($_FILES['file'])) {
        jsonError('Aucun fichier n\'a été envoyé', 400);
    }
    
    $uploadedFile = $_FILES['file'];
    $fileName = sanitizeString($uploadedFile['name']);
    $fileSize = $uploadedFile['size'];
    $fileTmp = $uploadedFile['tmp_name'];
    $fileError = $uploadedFile['error'];
    
    // Vérifier les erreurs d'upload
    if ($fileError !== UPLOAD_ERR_OK) {
        jsonError("Erreur d'upload: $fileError", 400);
    }
    
    // Vérifier la taille
    if ($fileSize > MAX_UPLOAD_SIZE) {
        jsonError('Le fichier est trop volumineux', 413);
    }
    
    // Vérifier l'extension
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        jsonError("Type de fichier non autorisé: $ext", 415);
    }
    
    // Créer un nom unique
    $uniqueName = uniqid('upload_', true) . '.' . $ext;
    $uploadPath = UPLOAD_DIR . '/' . $uniqueName;
    
    // Vérifier que le répertoire existe
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Déplacer le fichier
    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        jsonError('Erreur lors du déplacement du fichier', 500);
    }
    
    logAction("FILE UPLOADED", ['file' => $uniqueName, 'size' => $fileSize]);
    
    jsonSuccess([
        'message' => 'Fichier uploadé avec succès',
        'file' => $uniqueName,
        'original_name' => $fileName,
        'size' => $fileSize,
        'url' => '/~valin6/cvtek/uploads/' . $uniqueName,
        'type' => $ext,
    ], 201);
}

function handleDeleteFile(): void {
    // ✅ DÉVELOPPEMENT: suppression sans authentification
    
    $fileName = $_GET['file'] ?? $_POST['file'] ?? null;
    
    if (!$fileName) {
        jsonError('Nom du fichier requis', 400);
    }
    
    $fileName = sanitizeString($fileName);
    $filePath = UPLOAD_DIR . '/' . $fileName;
    
    // Sécurité: vérifier que le chemin est dans UPLOAD_DIR
    if (realpath($filePath) === false || strpos(realpath($filePath), realpath(UPLOAD_DIR)) !== 0) {
        jsonError('Accès refusé', 403);
    }
    
    if (!file_exists($filePath)) {
        jsonError('Fichier non trouvé', 404);
    }
    
    if (!unlink($filePath)) {
        jsonError('Erreur lors de la suppression', 500);
    }
    
    logAction("FILE DELETED", ['file' => $fileName]);
    
    jsonSuccess(['message' => 'Fichier supprimé avec succès']);
}

?>
