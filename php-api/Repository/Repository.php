<?php

/**
 * Class Repository (abstraite)
 * 
 * Classe de base pour tous les repositories
 * Accès à la base de données
 */
abstract class Repository
{
    protected $cnx;

    public function __construct()
    {
        $this->cnx = getConnection();
    }

    /**
     * Exécute une requête SQL et retourne les résultats
     */
    protected function execute(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->cnx->prepare($sql);
            if ($stmt->execute($params)) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return null;
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Exécute une requête et retourne une seule ligne
     */
    protected function executeOne(string $sql, array $params = []): ?array
    {
        $results = $this->execute($sql, $params);
        return $results[0] ?? null;
    }

    /**
     * Exécute une requête de modification (INSERT, UPDATE, DELETE)
     */
    protected function executeUpdate(string $sql, array $params = []): bool
    {
        try {
            $stmt = $this->cnx->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retourne l'ID inséré
     */
    protected function getLastInsertId(): string
    {
        return $this->cnx->lastInsertId();
    }
}
