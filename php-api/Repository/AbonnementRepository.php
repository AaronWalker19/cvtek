<?php

/**
 * AbonnementRepository
 * Gère les opérations d'abonnement en base de données
 */
class AbonnementRepository extends Repository
{
    /**
     * Récupère tous les abonnements d'un professeur
     */
    public function findByProf(int $profId): array
    {
        $results = $this->execute(
            "SELECT a.id, a.id_prof, a.id_user, a.created_at, 
                    u.username, u.email, u.parcour
             FROM abonnement a
             LEFT JOIN users u ON a.id_user = u.id
             WHERE a.id_prof = ?
             ORDER BY a.created_at DESC",
            [$profId]
        );

        return $results ?? [];
    }

    /**
     * Récupère tous les abonnements d'un étudiant
     */
    public function findByUser(int $userId): array
    {
        $results = $this->execute(
            "SELECT a.id, a.id_prof, a.id_user, a.created_at, 
                    u.username, u.email
             FROM abonnement a
             LEFT JOIN users u ON a.id_prof = u.id
             WHERE a.id_user = ?
             ORDER BY a.created_at DESC",
            [$userId]
        );

        return $results ?? [];
    }

    /**
     * Vérifie si un professeur suit un étudiant
     */
    public function isSubscribed(int $profId, int $userId): bool
    {
        $result = $this->executeOne(
            "SELECT id FROM abonnement WHERE id_prof = ? AND id_user = ?",
            [$profId, $userId]
        );

        return $result !== null;
    }

    /**
     * Crée un abonnement
     */
    public function create(int $profId, int $userId): bool
    {
        try {
            error_log("[AbonnementRepository::create] Début - profId=$profId, userId=$userId");
            
            // Vérifier que le prof et l'étudiant existent
            $prof = $this->executeOne("SELECT id FROM users WHERE id = ? AND role = 'professor'", [$profId]);
            if (!$prof) {
                error_log("[AbonnementRepository::create] Prof non trouvé: $profId");
                return false;
            }
            
            $student = $this->executeOne("SELECT id FROM users WHERE id = ?", [$userId]);
            if (!$student) {
                error_log("[AbonnementRepository::create] Étudiant non trouvé: $userId");
                return false;
            }

            // Vérifier que c'est pas déjà abonné
            if ($this->isSubscribed($profId, $userId)) {
                error_log("[AbonnementRepository::create] Déjà abonné: prof=$profId, user=$userId");
                return false;
            }

            // Créer l'abonnement
            $sql = "INSERT INTO abonnement (id_prof, id_user, created_at) VALUES (?, ?, NOW())";
            error_log("[AbonnementRepository::create] SQL: $sql");
            
            $result = $this->executeUpdate($sql, [$profId, $userId]);
            
            if ($result) {
                error_log("[AbonnementRepository::create] Succès - abonnement créé");
            } else {
                error_log("[AbonnementRepository::create] Erreur - executeUpdate a retourné false");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("[AbonnementRepository::create] Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un abonnement
     */
    public function delete(int $profId, int $userId): bool
    {
        try {
            error_log("[AbonnementRepository::delete] Suppression: prof=$profId, user=$userId");
            
            $result = $this->executeUpdate(
                "DELETE FROM abonnement WHERE id_prof = ? AND id_user = ?",
                [$profId, $userId]
            );
            
            error_log("[AbonnementRepository::delete] Résultat: " . ($result ? 'true' : 'false'));
            return $result;
        } catch (Exception $e) {
            error_log("[AbonnementRepository::delete] Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un abonnement par ID
     */
    public function deleteById(int $id): bool
    {
        try {
            error_log("[AbonnementRepository::deleteById] Suppression: id=$id");
            
            $result = $this->executeUpdate(
                "DELETE FROM abonnement WHERE id = ?",
                [$id]
            );
            
            error_log("[AbonnementRepository::deleteById] Résultat: " . ($result ? 'true' : 'false'));
            return $result;
        } catch (Exception $e) {
            error_log("[AbonnementRepository::deleteById] Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère un abonnement par ID
     */
    public function findById(int $id): ?array
    {
        return $this->executeOne(
            "SELECT a.id, a.id_prof, a.id_user, a.created_at 
             FROM abonnement a
             WHERE a.id = ?",
            [$id]
        );
    }

    /**
     * Récupère les emails des professeurs abonnés à un étudiant
     */
    public function getProfEmailsByUser(int $userId): array
    {
        $results = $this->execute(
            "SELECT u.email, u.username
             FROM abonnement a
             LEFT JOIN users u ON a.id_prof = u.id
             WHERE a.id_user = ? AND u.email IS NOT NULL",
            [$userId]
        );

        $emails = [];
        if ($results) {
            foreach ($results as $row) {
                $emails[] = $row['email'];
            }
        }
        return $emails;
    }
}
?>
