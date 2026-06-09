<?php

/**
 * UploadRepository
 * Gère les opérations fichiers uploadés
 */
class UploadRepository extends Repository
{
    /**
     * Enregistre un upload en base de données
     */
    public function logUpload(
        int $userId,
        string $fileName,
        string $uniqueName,
        int $fileSize,
        string $mimeType = ''
    ): int {
        $this->executeUpdate(
            "INSERT INTO uploads (user_id, original_name, unique_name, file_size, mime_type, uploaded_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$userId, $fileName, $uniqueName, $fileSize, $mimeType]
        );

        return (int)$this->getLastInsertId();
    }

    /**
     * Récupère les uploads d'un utilisateur
     */
    public function findByUserId(int $userId): array
    {
        return $this->execute(
            "SELECT * FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC",
            [$userId]
        ) ?? [];
    }

    /**
     * Récupère un upload par ID
     */
    public function findById(int $id): ?array
    {
        return $this->executeOne(
            "SELECT * FROM uploads WHERE id = ?",
            [$id]
        );
    }

    /**
     * Récupère un upload par nom unique
     */
    public function findByUniqueName(string $uniqueName): ?array
    {
        return $this->executeOne(
            "SELECT * FROM uploads WHERE unique_name = ?",
            [$uniqueName]
        );
    }

    /**
     * Supprime un enregistrement d'upload
     */
    public function delete(int $id): bool
    {
        return $this->executeUpdate(
            "DELETE FROM uploads WHERE id = ?",
            [$id]
        );
    }

    /**
     * Supprime un upload par nom unique
     */
    public function deleteByUniqueName(string $uniqueName): bool
    {
        return $this->executeUpdate(
            "DELETE FROM uploads WHERE unique_name = ?",
            [$uniqueName]
        );
    }
}
