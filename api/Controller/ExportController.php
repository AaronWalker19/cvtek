<?php
/**
 * ExportController.php - Export des fichiers des étudiants en ZIP
 * 
 * Endpoints:
 * - POST /export - Exporte les fichiers des étudiants sélectionnés
 */

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/DocumentRepository.php';
require_once __DIR__ . '/../Repository/UserRepository.php';

class ExportController extends Controller {
    private $documentRepo;
    private $userRepo;
    private $uploadDir;
    
    public function __construct() {
        $this->documentRepo = new DocumentRepository();
        $this->userRepo = new UserRepository();
        $this->uploadDir = __DIR__ . '/../../uploads';
        
        error_log("ExportController: uploadDir = " . $this->uploadDir);
        error_log("ExportController: uploadDir exists = " . (is_dir($this->uploadDir) ? "YES" : "NO"));
        if (is_dir($this->uploadDir)) {
            $files = scandir($this->uploadDir);
            error_log("ExportController: Files in upload dir: " . json_encode($files));
        }
    }
    
    protected function processPostRequest(HttpRequest $request): ?array {
        try {
            $this->handleExport();
            return null; // exit() a été appelé
        } catch (Exception $e) {
            error_log("❌ Erreur export: " . $e->getMessage());
            http_response_code(500);
            return [
                'error' => 'Erreur lors de la génération du ZIP: ' . $e->getMessage()
            ];
        }
    }
    
    protected function processGetRequest(HttpRequest $request): ?array {
        return ['error' => 'Méthode GET non supportée pour l\'export'];
    }
    
    protected function processPutRequest(HttpRequest $request): ?array {
        return ['error' => 'Méthode PUT non supportée pour l\'export'];
    }
    
    protected function processPatchRequest(HttpRequest $request): ?array {
        return ['error' => 'Méthode PATCH non supportée pour l\'export'];
    }
    
    protected function processDeleteRequest(HttpRequest $request): ?array {
        return ['error' => 'Méthode DELETE non supportée pour l\'export'];
    }
    
    /**
     * Exporte les fichiers des étudiants sélectionnés dans un ZIP
     * 
     * POST /api/export
     * Body: {
     *   "student_ids": [1, 2, 3],
     *   "licenses": ["L1", "L2"]  // optionnel
     * }
     */
    private function handleExport() {
        try {
            error_log("\n\n=== EXPORT START ===");
            error_log("Upload dir: {$this->uploadDir}");
            error_log("Upload dir exists: " . (is_dir($this->uploadDir) ? "YES" : "NO"));
            
            // Log all files in uploads
            if (is_dir($this->uploadDir)) {
                $allFiles = scandir($this->uploadDir);
                $realFiles = array_diff($allFiles, ['.', '..']);
                error_log("Files in uploads dir: " . count($realFiles));
                foreach ($realFiles as $file) {
                    error_log("  - $file");
                }
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            error_log("POST data: " . json_encode($input));
            
            if (!isset($input['student_ids']) || !is_array($input['student_ids']) || empty($input['student_ids'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'student_ids requis']);
                return;
            }
            
            $studentIds = array_filter(array_map('intval', $input['student_ids']));
            error_log("Student IDs: " . json_encode($studentIds));
            
            if (empty($studentIds)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Au moins un étudiant requis']);
                return;
            }
            
            $zipFile = tempnam(sys_get_temp_dir(), 'export_') . '.zip';
            error_log("ZIP file: $zipFile");
            
            $zip = new ZipArchive();
            
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Impossible de créer le ZIP');
            }
            
            $filesAdded = 0;
            
            // Pour chaque étudiant
            foreach ($studentIds as $studentId) {
                error_log("\n--- Processing student $studentId ---");
                
                try {
                    $student = $this->userRepo->findById($studentId);
                    if (!$student) {
                        error_log("❌ Student not found");
                        continue;
                    }
                    
                    error_log("✅ Student: {$student['username']}");
                    $studentName = $student['username'];
                    $studentFolder = "Étudiant_$studentId-$studentName";
                    
                    // Récupérer les documents de l'étudiant
                    $allDocs = $this->documentRepo->findByUserId($studentId);
                    error_log("Documents found: " . count($allDocs));
                    
                    if (empty($allDocs)) {
                        error_log("⚠️ No documents for this student");
                        continue;
                    }
                    
                    // Pour chaque document, récupérer ses versions
                    foreach ($allDocs as $doc) {
                        error_log("  Document: {$doc['nom_fichier']} (ID {$doc['id']})");
                        
                        $docId = $doc['id'];
                        
                        // Récupérer toutes les versions
                        $versions = $this->documentRepo->findVersions($docId);
                        error_log("    Versions: " . count($versions));
                        
                        if (empty($versions)) {
                            error_log("    ⚠️ No versions for this document");
                            continue;
                        }
                        
                        // Prendre la dernière version
                        $latestVersion = $versions[0];
                        $urlFichier = $latestVersion['url_fichier'];
                        error_log("    Latest version URL: $urlFichier");
                        
                        if (!$urlFichier) {
                            error_log("    ❌ No URL for version");
                            continue;
                        }
                        
                        $fileName = basename($urlFichier);
                        $parcour = isset($doc['parcour']) ? $doc['parcour'] : 'Sans formation';
                        $zipPath = "$studentFolder/$parcour/$fileName";
                        error_log("    ZIP path: $zipPath");
                        
                        // Chercher le fichier avec fallback multi-niveaux
                        $filePath = $this->findFileByUrl($urlFichier, $doc['nom_fichier'], $docId);
                        
                        if ($filePath && file_exists($filePath)) {
                            error_log("    ✅ File found, adding to ZIP: $filePath");
                            $zip->addFile($filePath, $zipPath);
                            $filesAdded++;
                        } else {
                            error_log("    ❌ File not found. URL: $urlFichier | Expected basename: $fileName | Doc: {$doc['nom_fichier']} (ID: $docId)");
                        }
                    }
                    
                } catch (Exception $e) {
                    error_log("❌ Exception for student $studentId: " . $e->getMessage());
                    continue;
                }
            }
            
            $zip->close();
            error_log("ZIP closed. Files added: $filesAdded");
            
            if ($filesAdded === 0) {
                unlink($zipFile);
                error_log("❌ No files found");
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Aucun fichier trouvé']);
                return;
            }
            
            // Envoyer le ZIP
            error_log("✅ Sending ZIP file");
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="export_' . date('Y-m-d_His') . '.zip"');
            header('Content-Length: ' . filesize($zipFile));
            
            readfile($zipFile);
            unlink($zipFile);
            error_log("=== EXPORT SUCCESS ===\n");
            exit(0);
            
        } catch (Exception $e) {
            error_log("=== EXPORT ERROR: " . $e->getMessage());
            error_log("Stack: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit(1);
        }
    }
    
    /**
     * Récupère les documents d'un étudiant avec l'URL de la dernière version
     */
    private function getStudentDocumentsWithVersions(int $studentId): array {
        try {
            $cnx = Database::getConnection();
            
            $query = "
                SELECT 
                    d.id, 
                    d.user_id, 
                    d.nom_fichier, 
                    d.titre, 
                    d.type_fichier, 
                    d.description, 
                    d.created_at,
                    u.username, 
                    u.email, 
                    u.parcour,
                    (SELECT url_fichier FROM doc_version WHERE id_doc = d.id ORDER BY created_at DESC, id DESC LIMIT 1) as url_fichier
                FROM documents d
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.user_id = ? AND d.id IS NOT NULL
                ORDER BY d.created_at DESC
            ";
            
            $stmt = $cnx->prepare($query);
            $stmt->execute([$studentId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("getStudentDocumentsWithVersions: Récupéré " . (is_array($results) ? count($results) : 0) . " documents pour l'étudiant $studentId");
            if (is_array($results) && !empty($results)) {
                error_log("  Premier document: " . json_encode($results[0]));
            }
            
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("Erreur getStudentDocumentsWithVersions: " . $e->getMessage());
            error_log("Stack: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Recherche un fichier avec fallback multi-niveaux robuste
     * Stratégies:
     * 1. Basename exact
     * 2. Chemin complet depuis l'URL
     * 3. Recherche par nom de document dans les fichiers du dossier
     * 4. Recherche par pattern (old filename format avec timestamps)
     * 
     * @param string $urlFichier URL stockée en BDD (ex: /cvtek/uploads/upload_123-123456.pdf)
     * @param string $docFileName Nom du document original (ex: "My Document.pdf")
     * @param int $docId ID du document
     * @return string|null Chemin complet du fichier ou null
     */
    private function findFileByUrl(string $urlFichier, string $docFileName, int $docId): ?string {
        $attempts = [];
        
        // === ESSAI 1: Basename exact ===
        $fileName = basename($urlFichier);
        $path1 = $this->uploadDir . '/' . $fileName;
        $attempts[] = "basename direct: $path1";
        if (file_exists($path1)) {
            error_log("    ✅ ESSAI 1 RÉUSSI: Basename exact trouvé");
            return $path1;
        }
        
        // === ESSAI 2: Nettoyage du chemin (au cas où l'URL contient le chemin complet) ===
        $cleanPath = str_replace(['/cvtek/uploads/', 'uploads/', '/uploads/'], '', $urlFichier);
        $path2 = $this->uploadDir . '/' . $cleanPath;
        $attempts[] = "chemin nettoyé: $path2";
        if ($path2 !== $path1 && file_exists($path2)) {
            error_log("    ✅ ESSAI 2 RÉUSSI: Chemin nettoyé trouvé");
            return $path2;
        }
        
        // === ESSAI 3: Recherche par nom de document dans les fichiers du dossier ===
        // Utile si le fichier a été renommé mais le nom du document est reconnaissable
        $docFileNameClean = $this->sanitizeForSearch($docFileName);
        $filesInDir = @scandir($this->uploadDir);
        
        if (is_array($filesInDir)) {
            $attempts[] = "recherche dans le dossier (" . count($filesInDir) . " fichiers)";
            
            // Chercher un fichier qui commence par le même nom
            foreach ($filesInDir as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $fileNameClean = $this->sanitizeForSearch($file);
                
                // Correspondance exacte du nom de fichier nettoyé
                if ($fileNameClean === $docFileNameClean) {
                    $fullPath = $this->uploadDir . '/' . $file;
                    error_log("    ✅ ESSAI 3 RÉUSSI: Nom de document exact trouvé: $file");
                    return $fullPath;
                }
                
                // Correspondance partielle (le fichier commence par le nom du document)
                $fileBaseNameClean = $this->sanitizeForSearch(pathinfo($file, PATHINFO_FILENAME));
                if (strpos($fileBaseNameClean, substr($docFileNameClean, 0, min(30, strlen($docFileNameClean)))) === 0) {
                    $fullPath = $this->uploadDir . '/' . $file;
                    error_log("    ✅ ESSAI 3.5 RÉUSSI (correspondance partielle): $file");
                    return $fullPath;
                }
            }
        }
        
        // === ESSAI 4: Extraction du timestamp de l'URL et recherche par pattern ===
        // Format ancien: filename-UNIXTIME-RANDOM.ext
        // Format nouveau: upload_USERID-TIMESTAMP.ext
        // On extrait le timestamp et cherche des fichiers qui le contiennent
        
        preg_match('/(\d{10}|\d{12})/', $urlFichier, $matches);
        $timePattern = $matches[1] ?? null;
        
        if ($timePattern && is_array($filesInDir)) {
            $attempts[] = "recherche par timestamp: $timePattern";
            
            foreach ($filesInDir as $file) {
                if ($file === '.' || $file === '..') continue;
                
                // Chercher le timestamp dans le nom du fichier
                if (strpos($file, $timePattern) !== false) {
                    $fullPath = $this->uploadDir . '/' . $file;
                    error_log("    ✅ ESSAI 4 RÉUSSI: Timestamp trouvé dans: $file");
                    return $fullPath;
                }
            }
        }
        
        // === ESSAI 5: Recherche fuzzy par extension ===
        // Dernier recours: chercher par l'extension du fichier attendu
        $ext = strtolower(pathinfo($docFileName, PATHINFO_EXTENSION));
        if ($ext && is_array($filesInDir)) {
            $attempts[] = "recherche par extension: .$ext";
            
            $filesWithExt = array_filter($filesInDir, function($f) use ($ext) {
                return strtolower(pathinfo($f, PATHINFO_EXTENSION)) === $ext;
            });
            
            // Si un seul fichier avec cette extension, c'est probablement le bon (utiliser avec prudence)
            if (count($filesWithExt) === 1) {
                $file = array_values($filesWithExt)[0];
                $fullPath = $this->uploadDir . '/' . $file;
                error_log("    ⚠️ ESSAI 5 RISQUÉ: Seul fichier avec extension .$ext: $file");
                return $fullPath;
            }
        }
        
        // Aucun fichier trouvé - logs détaillés pour débogage
        error_log("    ❌ RECHERCHE ÉPUISÉE - Tentatives:");
        foreach ($attempts as $i => $attempt) {
            error_log("       " . ($i + 1) . ". $attempt");
        }
        error_log("    URL BDD: $urlFichier");
        error_log("    Nom doc: $docFileName");
        error_log("    ID doc: $docId");
        
        return null;
    }

    /**
     * Nettoie une chaîne pour la comparaison
     * - Supprime les accents
     * - Convertit en minuscules
     * - Supprime les caractères spéciaux
     */
    private function sanitizeForSearch(string $str): string {
        // Supprimer l'extension d'abord
        $pathInfo = pathinfo($str);
        $filename = $pathInfo['filename'];
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        
        // Normalisation NFD puis suppression des diacritiques
        $normalized = normalizer_normalize($filename, \Normalizer::FORM_D);
        $sanitized = preg_replace('/\p{Mn}/u', '', $normalized);
        
        // Minuscules
        $sanitized = strtolower($sanitized);
        
        // Remplacer les espaces et tirets multiples par un seul tiret
        $sanitized = preg_replace('/[\s\-_]+/', '-', $sanitized);
        
        return $sanitized . $extension;
    }
}
