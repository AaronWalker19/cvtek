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
     */
    public function findOrCreateByEmail(string $email, string $username, string $role = 'student', ?string $parcour = null): ?array
    {
        // Chercher d'abord
        $user = $this->findByEmail($email);
        if ($user) {
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
     * Met à jour le dernier token d'un utilisateur
     */
    public function updateLastToken(int $userId, string $token): bool
    {
        return $this->executeUpdate(
            "UPDATE users SET last_token = ? WHERE id = ?",
            [$token, $userId]
        );
    }
}
