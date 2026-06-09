<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Repository/AbonnementRepository.php';

/**
 * AbonnementController
 * Gère les abonnements entre professeurs et étudiants
 */
class AbonnementController extends Controller
{
    private AbonnementRepository $abonnements;

    public function __construct()
    {
        $this->abonnements = new AbonnementRepository();
    }

    protected function processGetRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();
        $profId = $request->getParam('prof_id');
        $userId = $request->getParam('user_id');
        $action = $request->getAction();

        // GET /api/abonnement/check?prof_id=1&user_id=2 -> Vérifier si abonné
        if ($id === 'check' && $profId && $userId) {
            $profId = (int)$profId;
            $userId = (int)$userId;
            error_log("[AbonnementController::GET check] prof_id=$profId, user_id=$userId");
            logAction("CHECK_SUBSCRIPTION", ['profId' => $profId, 'userId' => $userId]);
            
            $isSubscribed = $this->abonnements->isSubscribed($profId, $userId);
            error_log("[AbonnementController::GET check] isSubscribed=" . ($isSubscribed ? 'true' : 'false'));
            
            return [
                'success' => true,
                'subscribed' => $isSubscribed,
                'prof_id' => $profId,
                'user_id' => $userId
            ];
        }

        // GET /api/abonnement?prof_id=1 -> Récupérer les abonnements d'un prof
        if (!$id && $profId && !$userId) {
            $profId = (int)$profId;
            logAction("GET_PROF_SUBSCRIPTIONS", ['profId' => $profId]);
            
            $subs = $this->abonnements->findByProf($profId);
            
            return [
                'success' => true,
                'count' => count($subs),
                'subscriptions' => $subs,
                'prof_id' => $profId
            ];
        }

        // GET /api/abonnement?user_id=2 -> Récupérer les abonnements d'un étudiant
        if (!$id && $userId && !$profId) {
            $userId = (int)$userId;
            logAction("GET_USER_SUBSCRIPTIONS", ['userId' => $userId]);
            
            $subs = $this->abonnements->findByUser($userId);
            
            return [
                'success' => true,
                'count' => count($subs),
                'subscriptions' => $subs,
                'user_id' => $userId
            ];
        }

        return [
            'error' => 'Invalid request parameters'
        ];
    }

    protected function processPostRequest(HttpRequest $request): ?array
    {
        try {
            $data = $request->getJsonBody();
            error_log("[AbonnementController::POST] Body reçu: " . json_encode($data));
            
            if (!$data) {
                error_log("[AbonnementController::POST] Erreur: Body est null ou vide");
                return [
                    'error' => 'Body JSON invalide ou vide',
                    'code' => 400
                ];
            }
            
            $profId = $data['prof_id'] ?? null;
            $userId = $data['user_id'] ?? null;
            
            error_log("[AbonnementController::POST] profId=$profId, userId=$userId");

            // POST /api/abonnement -> Créer un abonnement
            if ($profId && $userId) {
                $profId = (int)$profId;
                $userId = (int)$userId;
                error_log("[AbonnementController::POST] Création d'abonnement: prof=$profId, user=$userId");
                logAction("CREATE_SUBSCRIPTION", ['profId' => $profId, 'userId' => $userId]);
                
                $success = $this->abonnements->create($profId, $userId);
                error_log("[AbonnementController::POST] Result de create: " . ($success ? 'true' : 'false'));
                
                if ($success) {
                    return [
                        'success' => true,
                        'message' => 'Subscription créé avec succès',
                        'prof_id' => $profId,
                        'user_id' => $userId
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Impossible de créer l\'abonnement (déjà abonné ou utilisateurs invalides)',
                        'code' => 400
                    ];
                }
            }

            error_log("[AbonnementController::POST] Paramètres manquants");
            return [
                'error' => 'Missing required parameters (prof_id, user_id)',
                'received' => ['prof_id' => $profId, 'user_id' => $userId]
            ];
        } catch (Exception $e) {
            error_log("[AbonnementController::POST] Exception CATCHÉE: " . $e->getMessage());
            error_log("[AbonnementController::POST] Stack trace: " . $e->getTraceAsString());
            
            // RETOURNER L'ERREUR AU LIEU DE RELANCER L'EXCEPTION
            return [
                'error' => 'Exception: ' . $e->getMessage(),
                'code' => 500
            ];
        } catch (Throwable $e) {
            // Capturer aussi les Throwable (erreurs fatales, etc)
            error_log("[AbonnementController::POST] Throwable: " . $e->getMessage());
            return [
                'error' => 'Erreur: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    protected function processDeleteRequest(HttpRequest $request): ?array
    {
        $id = $request->getId();
        $profId = $request->getParam('prof_id');
        $userId = $request->getParam('user_id');

        // DELETE /api/abonnement/123 -> Supprimer par ID
        if ($id) {
            $id = (int)$id;
            logAction("DELETE_SUBSCRIPTION", ['id' => $id]);
            
            $success = $this->abonnements->deleteById($id);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Subscription supprimé avec succès',
                    'id' => $id
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Impossible de supprimer l\'abonnement',
                    'code' => 500
                ];
            }
        }

        // DELETE /api/abonnement?prof_id=1&user_id=2 -> Supprimer par prof_id et user_id
        if ($profId && $userId) {
            $profId = (int)$profId;
            $userId = (int)$userId;
            logAction("DELETE_SUBSCRIPTION", ['profId' => $profId, 'userId' => $userId]);
            
            $success = $this->abonnements->delete($profId, $userId);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Subscription supprimé avec succès',
                    'prof_id' => $profId,
                    'user_id' => $userId
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Impossible de supprimer l\'abonnement',
                    'code' => 500
                ];
            }
        }

        return [
            'error' => 'Missing required parameters (id or prof_id + user_id)'
        ];
    }
}
?>
