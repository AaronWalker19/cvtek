<?php

/**
 * CommentRepository
 * Gère les opérations commentaires en base de données
 */
class CommentRepository extends Repository
{
    /**
     * Récupère tous les commentaires d'une version de document
     */
    public function findByDocVersionId(int $docVersionId): array
    {
        $results = $this->execute(
            "SELECT c.id, c.id_user, c.id_docversion, c.text, c.date, u.username, u.email
             FROM commentaire c
             LEFT JOIN users u ON c.id_user = u.id
             WHERE c.id_docversion = ?
             ORDER BY c.date DESC",
            [$docVersionId]
        );

        return $results ?? [];
    }

    /**
     * Récupère un commentaire par son ID
     */
    public function findById(int $id): ?array
    {
        $result = $this->executeOne(
            "SELECT c.id, c.id_user, c.id_docversion, c.text, c.date, u.username, u.email
             FROM commentaire c
             LEFT JOIN users u ON c.id_user = u.id
             WHERE c.id = ?",
            [$id]
        );

        return $result ?? null;
    }

    /**
     * Récupère tous les commentaires d'un utilisateur
     */
    public function findByUserId(int $userId): array
    {
        $results = $this->execute(
            "SELECT c.id, c.id_user, c.id_docversion, c.text, c.date, u.username, u.email
             FROM commentaire c
             LEFT JOIN users u ON c.id_user = u.id
             WHERE c.id_user = ?
             ORDER BY c.date DESC",
            [$userId]
        );

        return $results ?? [];
    }

    /**
     * Récupère tous les commentaires sur les documents d'un utilisateur (propriétaire)
     */
    public function findByDocumentOwnerId(int $ownerId): array
    {
        $results = $this->execute(
            "SELECT c.id, c.id_user, c.id_docversion, c.text, c.date, u.username, u.email
             FROM commentaire c
             LEFT JOIN users u ON c.id_user = u.id
             LEFT JOIN document_version dv ON c.id_docversion = dv.id
             LEFT JOIN documents d ON dv.id_document = d.id
             WHERE d.user_id = ?
             ORDER BY c.date DESC",
            [$ownerId]
        );

        return $results ?? [];
    }

    /**
     * Crée un nouveau commentaire
     */
    public function create(int $userId, int $docVersionId, string $text): ?array
    {
        $id = $this->insert(
            "INSERT INTO commentaire (id_user, id_docversion, text, date)
             VALUES (?, ?, ?, NOW())",
            [$userId, $docVersionId, $text]
        );

        if ($id) {
            return $this->findById($id);
        }

        return null;
    }

    /**
     * Met à jour un commentaire
     */
    public function update(int $id, string $text): bool
    {
        $success = $this->execute(
            "UPDATE commentaire SET text = ? WHERE id = ?",
            [$text, $id]
        );

        return $success !== false;
    }

    /**
     * Supprime un commentaire
     */
    public function delete(int $id): bool
    {
        $success = $this->execute(
            "DELETE FROM commentaire WHERE id = ?",
            [$id]
        );

        return $success !== false;
    }

    /**
     * Supprime tous les commentaires d'une version
     */
    public function deleteByDocVersionId(int $docVersionId): bool
    {
        $success = $this->execute(
            "DELETE FROM commentaire WHERE id_docversion = ?",
            [$docVersionId]
        );

        return $success !== false;
    }

    /**
     * Supprime tous les commentaires d'un utilisateur
     */
    public function deleteByUserId(int $userId): bool
    {
        return $this->executeUpdate(
            "DELETE FROM commentaire WHERE id_user = ?",
            [$userId]
        );
    }

    /**
     * Compte le nombre de commentaires pour une version
     */
    public function countByDocVersionId(int $docVersionId): int
    {
        $result = $this->executeOne(
            "SELECT COUNT(*) as count FROM commentaire WHERE id_docversion = ?",
            [$docVersionId]
        );

        return $result['count'] ?? 0;
    }
}
