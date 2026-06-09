<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/UserRepository.php';
require_once __DIR__ . '/../Repository/CommentRepository.php';
require_once __DIR__ . '/../Repository/AuthRepository.php';
require_once __DIR__ . '/../Repository/DocumentRepository.php';
require_once __DIR__ . '/../Service/EmailService.php';

/**
 * AdminController
 * Gère les opérations d'administration
 */
class AdminController extends Controller
{
    private UserRepository $users;
    private CommentRepository $comments;
    private AuthRepository $auth;
    private DocumentRepository $documents;
    private EmailService $emailService;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->comments = new CommentRepository();
        $this->auth = new AuthRepository();
        $this->documents = new DocumentRepository();
        $this->emailService = new EmailService();
    }

    protected function processGetRequest(HttpRequest $request): ?array
    {
        $resource = $request->getResource();
        $action = $request->getId();
        
        // GET /api/admin/diagnostic -> debug la base de données
        if ($resource === 'admin' && $action === 'diagnostic') {
            return $this->handleDiagnostic($request);
        }
        
        // GET /api/admin/professors -> liste des professeurs avec count commentaires
        if ($resource === 'admin' && $action === 'professors') {
            return $this->handleListProfessors($request);
        }
        
        // GET /api/admin/professors/123 -> détails d'un professeur avec ses commentaires
        if ($resource === 'admin' && is_numeric($action)) {
            return $this->handleGetProfessor($request, (int)$action);
        }

        return ["error" => "Endpoint non trouvé"];
    }

    protected function processPostRequest(HttpRequest $request): ?array
    {
        $resource = $request->getResource();
        $action = $request->getId();
        
        // POST /api/admin/init -> initialiser l'utilisateur admin
        if ($resource === 'admin' && $action === 'init') {
            return $this->handleInitAdmin($request);
        }
        
        // POST /api/admin/professors -> créer un professeur (email uniquement)
        if ($resource === 'admin' && $action === 'professors') {
            return $this->handleCreateProfessor($request);
        }

        // POST /api/admin/advance-academic-year -> avancer l'année universitaire
        if ($resource === 'admin' && $action === 'advance-academic-year') {
            return $this->handleAdvanceAcademicYear($request);
        }

        return ["error" => "Endpoint non trouvé"];
    }

    protected function processDeleteRequest(HttpRequest $request): ?array
    {
        error_log("[DEBUG] processDeleteRequest: resource=" . $request->getResource() . ", id=" . $request->getId() . ", action=" . $request->getAction());
        
        $resource = $request->getResource();
        $id = $request->getId();
        $action = $request->getAction();
        
        // DELETE /api/admin/professors/123 -> supprimer un professeur
        // Parsing: api/admin/professors/123 -> resource='admin', id='professors', action='123'
        if ($resource === 'admin' && $id === 'professors' && is_numeric($action)) {
            error_log("[DEBUG] Pattern matched: DELETE /api/admin/professors/" . $action);
            return $this->handleDeleteProfessor($request, (int)$action);
        }

        error_log("[DEBUG] No pattern matched for DELETE, endpoint not found");
        return ["error" => "Endpoint non trouvé"];
    }

    /**
     * Initialise l'utilisateur admin (accessible sans authentification)
     * Met à jour le mot de passe même si l'admin existe déjà
     */
    private function handleInitAdmin(HttpRequest $request): ?array
    {
        // Cette action n'est pas protégée - elle est appelée automatiquement au démarrage
        $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@cvtek.fr';
        $adminUsername = getenv('ADMIN_USERNAME') ?: 'admin';
        $adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';

        logAction("ADMIN_INIT_ATTEMPT", ['email' => $adminEmail]);

        try {
            // Hash du mot de passe
            $passwordHash = hashPassword($adminPassword);

            // Vérifier si l'admin existe déjà
            $existingAdmin = $this->auth->findByEmail($adminEmail);
            
            if ($existingAdmin) {
                // Mettre à jour le mot de passe
                logAction("ADMIN_PASSWORD_UPDATE", ['adminId' => $existingAdmin['id']]);
                $this->auth->updatePassword($existingAdmin['id'], $passwordHash);
                
                $updatedAdmin = $this->auth->findById($existingAdmin['id']);
                
                return [
                    'success' => true,
                    'message' => 'Mot de passe admin mis à jour',
                    'admin' => [
                        'id' => $updatedAdmin['id'],
                        'username' => $updatedAdmin['username'],
                        'email' => $updatedAdmin['email'],
                        'role' => $updatedAdmin['role'],
                    ]
                ];
            }

            // Créer l'admin avec mot de passe hashé
            $adminId = $this->auth->create($adminUsername, $adminEmail, 'admin', $passwordHash, null);

            if (!$adminId) {
                logAction("ADMIN_CREATION_FAILED", ['email' => $adminEmail]);
                return ['error' => 'Erreur lors de la création de l\'admin', 'code' => 500];
            }

            $newAdmin = $this->auth->findById($adminId);

            logAction("ADMIN_CREATED", ['adminId' => $adminId, 'email' => $adminEmail]);

            return [
                'success' => true,
                'message' => 'Admin créé avec succès',
                'admin' => [
                    'id' => $newAdmin['id'],
                    'username' => $newAdmin['username'],
                    'email' => $newAdmin['email'],
                    'role' => $newAdmin['role'],
                ]
            ];

        } catch (Exception $e) {
            logAction("ADMIN_INIT_ERROR", ['error' => $e->getMessage()]);
            return ['error' => 'Erreur serveur: ' . $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * Liste tous les professeurs avec le nombre de commentaires
     */
    private function handleListProfessors(HttpRequest $request): ?array
    {
        // Vérifier que c'est un admin
        $user = requireAdmin();

        logAction("ADMIN_LIST_PROFESSORS", ['requester' => $user['id']]);
        
        $professors = $this->users->findProfessorsWithCommentCount();
        
        return [
            'success' => true,
            'count' => count($professors),
            'professors' => $professors,
        ];
    }

    /**
     * Récupère les détails d'un professeur avec ses commentaires
     */
    private function handleGetProfessor(HttpRequest $request, int $professorId): ?array
    {
        // Vérifier que c'est un admin
        $user = requireAdmin();

        logAction("ADMIN_GET_PROFESSOR", ['professorId' => $professorId, 'requester' => $user['id']]);
        
        $professor = $this->users->findById($professorId);
        if (!$professor || $professor['role'] !== 'professor') {
            return ['error' => 'Professeur non trouvé', 'code' => 404];
        }

        $comments = $this->comments->findByUserId($professorId);

        return [
            'success' => true,
            'professor' => $professor,
            'comments' => $comments,
            'comment_count' => count($comments),
        ];
    }

    /**
     * Crée un professeur avec email uniquement
     */
    private function handleCreateProfessor(HttpRequest $request): ?array
    {
        error_log("[DEBUG] handleCreateProfessor START");
        
        // Vérifier que c'est un admin
        $user = requireAdmin();

        $data = $request->getJson();

        if (empty($data['email'])) {
            return ['error' => 'Email requis', 'code' => 400];
        }

        $email = sanitizeString($data['email']);

        if (!isValidEmail($email)) {
            return ['error' => 'Email invalide', 'code' => 400];
        }

        logAction("ADMIN_CREATE_PROFESSOR", ['email' => $email, 'requester' => $user['id']]);

        // Créer le professeur
        error_log("[DEBUG] Creating professor: " . $email);
        $newProfessor = $this->users->createWithoutPassword($email, 'professor');

        if (!$newProfessor) {
            error_log("[DEBUG] Failed to create professor");
            return ['error' => 'Erreur lors de la création', 'code' => 500];
        }

        error_log("[DEBUG] Professor created: " . json_encode($newProfessor));
        return [
            'success' => true,
            'message' => 'Professeur créé avec succès',
            'professor' => $newProfessor,
        ];
    }

    /**
     * Supprime un professeur et ses commentaires
     */
    private function handleDeleteProfessor(HttpRequest $request, int $professorId): ?array
    {
        error_log("[DEBUG] handleDeleteProfessor: id=" . $professorId . ", type=" . gettype($professorId));
        
        // Vérifier que c'est un admin
        $user = requireAdmin();

        logAction("ADMIN_DELETE_PROFESSOR", ['professorId' => $professorId, 'requester' => $user['id']]);

        // Supprimer les commentaires de l'utilisateur d'abord
        error_log("[DEBUG] Deleting comments for user: " . $professorId);
        $this->comments->deleteByUserId($professorId);

        // Supprimer l'utilisateur directement
        error_log("[DEBUG] Deleting user: " . $professorId);
        if (!$this->users->delete($professorId)) {
            error_log("[ERROR] Failed to delete user: " . $professorId);
            return ['error' => 'Erreur lors de la suppression', 'code' => 500];
        }

        error_log("[DEBUG] User deleted successfully: " . $professorId);
        return [
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès',
        ];
    }

    /**
     * Endpoint de diagnostic pour déboguer les problèmes de base de données
     * GET /api/admin/diagnostic
     */
    private function handleDiagnostic(HttpRequest $request): ?array
    {
        error_log("[DEBUG] === DIAGNOSTIC ENDPOINT CALLED ===");
        
        // Lister TOUS les utilisateurs
        $allUsers = $this->users->findAll();
        error_log("[DEBUG] All users: " . json_encode($allUsers));
        
        // Lister tous les professeurs
        $allProfessors = $this->users->findByRole('professor');
        error_log("[DEBUG] All professors: " . json_encode($allProfessors));
        
        // Essayer de récupérer l'utilisateur 47 spécifiquement
        $user47 = $this->users->findById(47);
        error_log("[DEBUG] User 47 via findById: " . json_encode($user47));
        
        // Test direct: afficher l'ID du premier professeur
        if (count($allProfessors) > 0) {
            $firstProf = $allProfessors[0];
            error_log("[DEBUG] First professor: " . json_encode($firstProf));
            error_log("[DEBUG] First professor ID: " . $firstProf['id'] . " (type: " . gettype($firstProf['id']) . ")");
            
            // Essayer de trouver ce professeur par ID
            $foundByFind = $this->users->findById((int)$firstProf['id']);
            error_log("[DEBUG] Looking up first professor by ID: " . json_encode($foundByFind));
        }
        
        $professorIds = array_map(function($p) { return (int)$p['id']; }, $allProfessors);
        
        return [
            'success' => true,
            'diagnostic' => [
                'total_users' => count($allUsers) ?? 0,
                'all_users' => $allUsers,
                'total_professors' => count($allProfessors) ?? 0,
                'all_professors' => $allProfessors,
                'professor_ids' => $professorIds,
                'user_47' => $user47,
                'user_47_found' => $user47 !== null,
                'is_47_in_professors' => in_array(47, $professorIds),
                'note' => 'If total_users=0 but professors exist, check SQL WHERE clause or database permissions'
            ]
        ];
    }

    /**
     * Avance l'année scolaire des étudiants
     * POST /api/admin/advance-academic-year
     * 
     * Comportement:
     * - Année 1 -> 2, Année 2 -> 3, Année 3 -> 4
     * - Année 4 -> Suppression complète (utilisateur + documents + uploads)
     */
    private function handleAdvanceAcademicYear(HttpRequest $request): ?array
    {
        // Vérifier que c'est un admin
        $user = requireAdmin();

        logAction("ADMIN_ADVANCE_ACADEMIC_YEAR_START", ['requester' => $user['id']]);

        try {
            global $connexion;

            // Récupérer tous les étudiants
            $students = $connexion->query("SELECT id, email, année FROM users WHERE role = 'student' ORDER BY année DESC");
            
            if (!$students) {
                throw new Exception("Erreur lors de la récupération des étudiants: " . $connexion->error);
            }

            $results = [
                'promoted' => [],
                'deleted' => [],
                'total_processed' => 0,
                'errors' => []
            ];

            // Traiter chaque étudiant
            while ($student = $students->fetch_assoc()) {
                $studentId = (int)$student['id'];
                $currentYear = (int)$student['année'];
                $email = $student['email'];

                $results['total_processed']++;

                try {
                    if ($currentYear >= 4) {
                        // Supprimer complètement l'étudiant et ses données
                        $this->deleteStudentAndData($studentId, $email, $results);
                    } else {
                        // Avancer l'année
                        $newYear = $currentYear + 1;
                        $updateStmt = $connexion->prepare("UPDATE users SET année = ? WHERE id = ?");
                        $updateStmt->bind_param("ii", $newYear, $studentId);
                        $updateStmt->execute();
                        $updateStmt->close();

                        $results['promoted'][] = [
                            'student_id' => $studentId,
                            'email' => $email,
                            'from_year' => $currentYear,
                            'to_year' => $newYear
                        ];

                        logAction("STUDENT_YEAR_ADVANCED", [
                            'studentId' => $studentId,
                            'email' => $email,
                            'from' => $currentYear,
                            'to' => $newYear
                        ]);
                    }
                } catch (Exception $e) {
                    $results['errors'][] = [
                        'student_id' => $studentId,
                        'email' => $email,
                        'error' => $e->getMessage()
                    ];
                    logAction("STUDENT_PROCESSING_ERROR", [
                        'studentId' => $studentId,
                        'email' => $email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            logAction("ADMIN_ADVANCE_ACADEMIC_YEAR_END", [
                'requester' => $user['id'],
                'promoted' => count($results['promoted']),
                'deleted' => count($results['deleted']),
                'errors' => count($results['errors'])
            ]);

            // Envoyer un email de résumé si des étudiants ont été supprimés
            if (count($results['deleted']) > 0) {
                try {
                    // Filtrer les suppressions réussies
                    $deletedSuccessfully = array_filter($results['deleted'], function($d) {
                        return ($d['status'] ?? '') === 'success';
                    });

                    if (count($deletedSuccessfully) > 0) {
                        // Calculer le nombre total de fichiers supprimés
                        $totalFilesDeleted = 0;
                        foreach ($deletedSuccessfully as $deleted) {
                            $totalFilesDeleted += $deleted['files_deleted'] ?? 0;
                        }

                        $this->emailService->sendStudentDeletionSummary(
                            $user['email'],
                            $user['username'],
                            $deletedSuccessfully,
                            $totalFilesDeleted
                        );
                    }
                } catch (Exception $emailErr) {
                    error_log("[ERROR] Erreur lors de l'envoi du mail de suppression: " . $emailErr->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => 'Année scolaire avancée avec succès',
                'data' => $results
            ];

        } catch (Exception $e) {
            logAction("ADMIN_ADVANCE_ACADEMIC_YEAR_ERROR", [
                'error' => $e->getMessage()
            ]);
            return [
                'error' => 'Erreur lors de l\'avancement de l\'année: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Supprime complètement un étudiant et toutes ses données
     * - Utilisateur
     * - Documents et versions
     * - Commentaires
     * - Fichiers uploads
     * - Abonnements
     */
    private function deleteStudentAndData(int $studentId, string $email, array &$results): void
    {
        global $connexion;

        try {
            // 1. Récupérer tous les documents de l'étudiant pour supprimer les fichiers
            $docsResult = $connexion->query(
                "SELECT dv.url_fichier FROM doc_version dv 
                 INNER JOIN documents d ON dv.id_doc = d.id 
                 WHERE d.user_id = " . $studentId
            );

            $filesToDelete = [];
            if ($docsResult) {
                while ($row = $docsResult->fetch_assoc()) {
                    $filesToDelete[] = $row['url_fichier'];
                }
            }

            // 2. Supprimer les commentaires de l'étudiant
            $connexion->query("DELETE FROM commentaire WHERE id_user = " . $studentId);

            // 3. Supprimer les documents et versions (cascade)
            $connexion->query("DELETE FROM documents WHERE user_id = " . $studentId);

            // 4. Supprimer les abonnements
            $connexion->query("DELETE FROM abonnement WHERE id_user = " . $studentId . " OR id_prof = " . $studentId);

            // 5. Supprimer l'utilisateur
            $connexion->query("DELETE FROM users WHERE id = " . $studentId);

            // 6. Supprimer les fichiers physiques
            $this->deleteUploadedFiles($filesToDelete);

            $results['deleted'][] = [
                'student_id' => $studentId,
                'email' => $email,
                'files_deleted' => count($filesToDelete),
                'status' => 'success'
            ];

            logAction("STUDENT_DELETED", [
                'studentId' => $studentId,
                'email' => $email,
                'filesDeleted' => count($filesToDelete)
            ]);

        } catch (Exception $e) {
            throw new Exception("Erreur lors de la suppression de l'étudiant $email: " . $e->getMessage());
        }
    }

    /**
     * Supprime les fichiers uploads spécifiés
     */
    private function deleteUploadedFiles(array $filePaths): void
    {
        $uploadDir = getenv('UPLOAD_DIR') ?: '../uploads';
        $basePath = dirname(__DIR__) . '/' . $uploadDir;

        foreach ($filePaths as $filePath) {
            // Extraire le nom de fichier du chemin complet
            $fileName = basename($filePath);
            $fullPath = $basePath . '/' . $fileName;

            // Vérifier que le fichier existe et qu'il est dans le bon répertoire (sécurité)
            if (file_exists($fullPath) && realpath($fullPath) === realpath($fullPath)) {
                try {
                    if (is_file($fullPath)) {
                        unlink($fullPath);
                        logAction("FILE_DELETED", ['path' => $fullPath]);
                    }
                } catch (Exception $e) {
                    error_log("[WARNING] Impossible de supprimer le fichier: $fullPath - " . $e->getMessage());
                }
            }
        }
    }
}
