<?php
/**
 * Test d'export avec debug détaillé
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';
require_once __DIR__ . '/Repository/UserRepository.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $documentRepo = new DocumentRepository();
    $userRepo = new UserRepository();
    
    // Récupérer le premier étudiant
    $students = $userRepo->findByRole('student');
    
    if (empty($students)) {
        echo json_encode(['error' => 'Aucun étudiant trouvé']);
        exit(1);
    }
    
    $firstStudent = $students[0];
    $studentId = $firstStudent['id'];
    
    echo json_encode([
        'student' => $firstStudent,
        'upload_dir' => UPLOAD_DIR,
        'upload_dir_exists' => is_dir(UPLOAD_DIR),
        'files_on_disk' => scandir(UPLOAD_DIR),
        'student_documents' => $documentRepo->findByUserId($studentId),
        'document_details' => array_map(function($doc) use ($documentRepo) {
            $versions = $documentRepo->findVersions($doc['id']);
            return [
                'id' => $doc['id'],
                'nom_fichier' => $doc['nom_fichier'],
                'versions_count' => count($versions),
                'versions' => $versions,
                'files_check' => array_map(function($v) {
                    $fileName = basename($v['url_fichier']);
                    $path1 = UPLOAD_DIR . '/' . $fileName;
                    return [
                        'url_fichier' => $v['url_fichier'],
                        'basename' => $fileName,
                        'path' => $path1,
                        'exists' => file_exists($path1)
                    ];
                }, $versions)
            ];
        }, $documentRepo->findByUserId($studentId))
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
