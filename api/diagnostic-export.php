<?php
/**
 * Test d'export - Simule une requête d'export pour diagnostiquer le problème
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Repository/DocumentRepository.php';
require_once __DIR__ . '/Repository/UserRepository.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getConnection();
    $userRepo = new UserRepository();
    $docRepo = new DocumentRepository();
    
    // Récupérer le premier étudiant
    $students = $userRepo->findByRole('student');
    
    if (empty($students)) {
        echo json_encode(['error' => 'Aucun étudiant trouvé']);
        exit(1);
    }
    
    $student = $students[0];
    $studentId = $student['id'];
    
    // Vérifier les documents de cet étudiant
    $documents = $docRepo->findByUserId($studentId);
    
    $result = [
        'student' => $student,
        'documents_count' => count($documents),
        'documents' => array_map(function($doc) use ($docRepo) {
            $versions = $docRepo->findVersions($doc['id']);
            return [
                'id' => $doc['id'],
                'nom_fichier' => $doc['nom_fichier'],
                'versions_count' => count($versions),
                'versions' => array_map(function($v) {
                    $fileName = basename($v['url_fichier']);
                    $path = UPLOAD_DIR . '/' . $fileName;
                    return [
                        'version' => $v['version'],
                        'url_fichier' => $v['url_fichier'],
                        'basename' => $fileName,
                        'file_exists' => file_exists($path),
                        'attempted_path' => $path
                    ];
                }, $versions)
            ];
        }, $documents),
        'upload_dir' => UPLOAD_DIR,
        'upload_dir_exists' => is_dir(UPLOAD_DIR),
        'files_on_disk' => array_diff(scandir(UPLOAD_DIR) ?: [], ['.', '..'])
    ];
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
