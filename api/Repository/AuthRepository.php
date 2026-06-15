<?php

/**
 * AuthRepository
 * Gère les opérations authentification en base de données
 * 
 * Modèle d'authentification:
 * - Users réguliers: email, role, parcour (pas de password - auth externe)
 * - Admin: peut avoir un password pour accès direct
 */
class AuthRepository extends Repository
{
    /**
     * Trouve un utilisateur par email (avec password si présent, pour admin)
     */
    public function findByEmail(string $email): ?array
    {
        return $this->executeOne(
            "SELECT id, username, email, password_hash, role, parcour, created_at FROM users WHERE email = ?",
            [$email]
        );
    }

    /**
     * Trouve un utilisateur par ID (sans password)
     */
    public function findById(int $id): ?array
    {
        return $this->executeOne(
            "SELECT id, username, email, role, parcour, created_at FROM users WHERE id = ?",
            [$id]
        );
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function create(string $username, string $email, string $role = 'student', ?string $passwordHash = null, ?string $parcour = null): int
    {
        $this->executeUpdate(
            "INSERT INTO users (username, email, password_hash, role, parcour, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$username, $email, $passwordHash, $role, $parcour]
        );
        return (int)$this->getLastInsertId();
    }

    /**
     * Crée ou retourne un utilisateur existant (pour authentification externe)
     * Peut optionnellement mettre à jour le parcours si l'utilisateur existe
     * 
     * @param string $email Email de l'utilisateur
     * @param string $username Nom d'utilisateur
     * @param string $role Rôle (student, professor, admin)
     * @param string|null $parcour Parcours/cursus (optionnel)
     * @param bool $updateParcourIfExists Si true, met à jour le parcours si l'utilisateur existe
     * @return array|null Données utilisateur ou null en cas d'erreur
     */
    public function findOrCreateByEmail(string $email, string $username, string $role = 'student', ?string $parcour = null, bool $updateParcourIfExists = false): ?array
    {
        // Chercher d'abord
        $user = $this->findByEmail($email);
        if ($user) {
            // Utilisateur existe: mettre à jour le parcours si demandé et si parcour fourni
            if ($updateParcourIfExists && $parcour !== null) {
                $this->updateParcour($user['id'], $parcour);
                // Retourner l'utilisateur avec le parcours mis à jour
                $user['parcour'] = $parcour;
            }
            return $user;
        }
        
        // Créer s'il n'existe pas
        $id = $this->create($username, $email, $role, null, $parcour);
        return $this->findById($id);
    }

    /**
     * Vérifie si un email existe
     */
    public function emailExists(string $email): bool
    {
        $result = $this->executeOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        return $result !== null;
    }

    /**
     * Met à jour le mot de passe hashé d'un utilisateur
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        return $this->executeUpdate(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$passwordHash, $userId]
        );
    }

    /**
     * Met à jour le dernier token d'un utilisateur
     */
    public function updateLastToken(int $userId, string $token): bool
    {
        return $this->executeUpdate(
            "UPDATE users SET last_token = ? WHERE id = ?",
            [$token, $userId]
        );
    }

    /**
     * Met à jour le parcours (groupes/licences) d'un utilisateur
     */
    public function updateParcour(int $userId, ?string $parcour): bool
    {
        return $this->executeUpdate(
            "UPDATE users SET parcour = ? WHERE id = ?",
            [$parcour, $userId]
        );
    }

    /**
     * Cherche le(s) libellé(s) d'un parcours par son identification (groupe)
     * 
     * @param string $identification L'identifiant du groupe (ex: TLMM13-231)
     * @return array Tableau des libellés trouvés ou tableau vide
     */
    public function findLibellesByIdentification(string $identification): array
    {
        $results = $this->execute(
            "SELECT DISTINCT libellé FROM parcours WHERE identification = ? ORDER BY libellé",
            [$identification]
        );
        
        $libelles = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $libelles[] = $row['libellé'];
            }
        }
        return $libelles;
    }

    /**
     * Cherche le premier libellé correspondant parmi une liste de groupes
     * Parcourt les groupes dans l'ordre et retourne le premier libellé trouvé
     * 
     * @param array $groups Liste des identifiants de groupes
     * @return string|null Le premier libellé trouvé ou null si aucun match
     */
    public function findFirstMatchingLibelle(array $groups): ?string
    {
        if (empty($groups)) {
            return null;
        }

        // Chercher le premier groupe qui correspond dans la table parcours
        foreach ($groups as $group) {
            $libelles = $this->findLibellesByIdentification($group);
            if (!empty($libelles)) {
                // Retourner le premier libellé (s'il y en a plusieurs, prendre le premier alphabétiquement)
                $firstLibelle = reset($libelles);
                error_log("✅ Correspondance trouvée: groupe='$group' -> libellé='$firstLibelle'");
                return $firstLibelle;
            }
        }

        error_log("⚠️  Aucune correspondance trouvée pour les groupes: " . json_encode($groups));
        return null;
    }
}

