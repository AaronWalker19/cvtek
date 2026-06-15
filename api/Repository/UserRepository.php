<?php

/**
 * UserRepository
 * Gère les opérations utilisateurs en base de données
 */
class UserRepository extends Repository
{
    /**
     * Récupère un utilisateur par ID
     */
    public function findById(int $id): ?array
    {
        error_log("[DEBUG] findById: id=" . $id . ", type=" . gettype($id));
        
        $sql = "SELECT id, username, email, role, parcour, created_at FROM users WHERE id = ?";
        error_log("[DEBUG] findById SQL: " . $sql);
        error_log("[DEBUG] findById params: " . json_encode([$id]));
        
        $result = $this->executeOne($sql, [$id]);
        
        error_log("[DEBUG] findById result type: " . gettype($result));
        error_log("[DEBUG] findById result: " . json_encode($result));
        
        // Test: essayer avec convertir l'ID en string aussi
        if ($result === null) {
            error_log("[DEBUG] findById returned null, trying alternative...");
            error_log("[DEBUG] Comparing with all users to debug:");
            $allUsers = $this->execute("SELECT id, username, email, role FROM users");
            error_log("[DEBUG] All users in DB: " . json_encode($allUsers));
        }
        
        return $result;
    }

    /**
     * Récupère un utilisateur par email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->executeOne(
            "SELECT id, username, email, role, parcour, created_at 
             FROM users 
             WHERE email = ?",
            [$email]
        );
    }

    /**
     * Récupère un utilisateur par username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->executeOne(
            "SELECT id, username, email, role, parcour, created_at 
             FROM users 
             WHERE username = ?",
            [$username]
        );
    }

    /**
     * Récupère tous les utilisateurs avec un rôle spécifique
     */
    public function findByRole(string $role): array
    {
        $results = $this->execute(
            "SELECT id, username, email, role, parcour, created_at 
             FROM users 
             WHERE role = ? 
             ORDER BY username ASC",
            [$role]
        );

        return $results ?? [];
    }

    /**
     * Récupère les professeurs avec le nombre de commentaires
     */
    public function findProfessorsWithCommentCount(): array
    {
        $results = $this->execute(
            "SELECT u.id, u.username, u.email, u.role, u.parcour, u.created_at,
                    COUNT(c.id) as comment_count
             FROM users u
             LEFT JOIN commentaire c ON u.id = c.id_user
             WHERE u.role = 'professor'
             GROUP BY u.id
             ORDER BY u.username ASC"
        );

        return $results ?? [];
    }

    /**
     * Crée un utilisateur sans password (pour l'intégration université)
     */
    public function createWithoutPassword(
        string $email,
        string $role = 'professor'
    ): ?array {
        error_log("[DEBUG] createWithoutPassword: START email=" . $email . ", role=" . $role);
        
        // Vérifier que l'utilisateur n'existe pas déjà
        error_log("[DEBUG] Checking if user exists by email");
        $existing = $this->findByEmail($email);
        error_log("[DEBUG] findByEmail result: " . json_encode($existing));
        
        if ($existing) {
            error_log("[DEBUG] User already exists, returning existing");
            return $existing;
        }

        // Créer un username à partir de l'email
        // Format: prenom nom domain (ex: jean.dupont@unilim.fr -> "jean dupont unilim")
        list($localPart, $domain) = explode('@', $email);
        error_log("[DEBUG] Email parts: localPart=" . $localPart . ", domain=" . $domain);
        
        // Remplacer les points par des espaces dans la partie locale
        $localPart = str_replace('.', ' ', $localPart);
        error_log("[DEBUG] After dot replacement: " . $localPart);
        
        // Générer le username final (sans le domaine)
        $username = trim($localPart);
        
        // Capitaliser chaque mot (première lettre en majuscule)
        $username = ucwords(strtolower($username));
        error_log("[DEBUG] Initial generated username: " . $username);
        
        // Si le username existe déjà, ajouter un suffixe unique
        $baseUsername = $username;
        $counter = 1;
        while ($this->findByUsername($username)) {
            error_log("[DEBUG] Username already exists: " . $username . ", trying with suffix");
            $username = $baseUsername . ' ' . $counter;
            $counter++;
        }
        
        error_log("[DEBUG] Final username after uniqueness check: " . $username);
        
        // Insérer l'utilisateur
        error_log("[DEBUG] Executing INSERT query with username=" . $username . ", email=" . $email . ", role=" . $role);
        $success = $this->executeUpdate(
            "INSERT INTO users (username, email, role) 
             VALUES (?, ?, ?)",
            [$username, $email, $role]
        );
        
        error_log("[DEBUG] INSERT result: " . ($success ? "true" : "false"));

        if (!$success) {
            error_log("[DEBUG] INSERT failed, returning null");
            return null;
        }

        // Après INSERT réussi, essayer de récupérer avec retry
        error_log("[DEBUG] INSERT succeeded, attempting to retrieve user");
        
        // Retry jusqu'à 5 fois avec délai augmentant
        $result = null;
        for ($i = 0; $i < 5; $i++) {
            error_log("[DEBUG] Retrieval attempt " . ($i + 1) . "/5 by username");
            $result = $this->findByUsername($username);
            if ($result) {
                error_log("[DEBUG] Found by username on attempt " . ($i + 1));
                break;
            }
            if ($i < 4) {
                $delay = 50000 + ($i * 50000); // 50ms, 100ms, 150ms, 200ms
                error_log("[DEBUG] Retry attempt " . ($i + 1) . ", waiting " . ($delay / 1000) . "ms");
                usleep($delay);
            }
        }
        
        if (!$result) {
            error_log("[DEBUG] findByUsername failed all retries, trying findByEmail");
            $result = $this->findByEmail($email);
        }
        
        if (!$result) {
            error_log("[DEBUG] findByEmail also failed, using manual SELECT with latest created_at");
            $result = $this->executeOne(
                "SELECT id, username, email, role, parcour, created_at 
                 FROM users 
                 WHERE email = ? 
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$email]
            );
            if ($result) {
                error_log("[DEBUG] Manual SELECT succeeded, found user with ID=" . $result['id']);
            }
        }
        
        if (!$result) {
            error_log("[ERROR] Could not retrieve user after INSERT for email=" . $email . ", all methods failed!");
            return null;
        }
        
        error_log("[DEBUG] createWithoutPassword: SUCCESS returning: " . json_encode($result));
        return $result;
    }

    /**
     * Récupère tous les utilisateurs
     */
    public function findAll(): array
    {
        $results = $this->execute(
            "SELECT id, username, email, role, parcour, created_at 
             FROM users 
             ORDER BY username ASC"
        );

        return $results ?? [];
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function create(
        string $username,
        string $email,
        string $passwordHash,
        string $role = 'student',
        string $parcour = null
    ): int {
        $this->executeUpdate(
            "INSERT INTO users (username, email, password_hash, role, parcour) 
             VALUES (?, ?, ?, ?, ?)",
            [$username, $email, $passwordHash, $role, $parcour]
        );

        return (int)$this->getLastInsertId();
    }

    /**
     * Met à jour les informations d'un utilisateur
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['username', 'email', 'password_hash', 'role', 'parcour'];
        $updates = [];
        $values = [];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                $values[] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";

        return $this->executeUpdate($sql, $values);
    }

    /**
     * Supprime un utilisateur
     */
    public function delete(int $id): bool
    {
        return $this->executeUpdate(
            "DELETE FROM users WHERE id = ?",
            [$id]
        );
    }
}
