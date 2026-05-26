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
        return $this->executeOne(
            "SELECT id, username, email, role, parcour, created_at, updated_at 
             FROM users 
             WHERE id = ?",
            [$id]
        );
    }

    /**
     * Récupère un utilisateur par email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->executeOne(
            "SELECT id, username, email, role, parcour, created_at, updated_at 
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
            "SELECT id, username, email, role, parcour, created_at, updated_at 
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
     * Récupère tous les utilisateurs
     */
    public function findAll(): array
    {
        $results = $this->execute(
            "SELECT id, username, email, role, parcour, created_at, updated_at 
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
