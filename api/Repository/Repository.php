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
        $this->cnx = Database::getConnection();
    }

    /**
     * Exécute une requête SQL et retourne les résultats
     */
    protected function execute(string $sql, array $params = []): ?array
    {
        try {
            error_log("[DEBUG] SQL EXECUTE: " . $sql);
            error_log("[DEBUG] SQL PARAMS: " . json_encode($params));
            
            $stmt = $this->cnx->prepare($sql);
            if ($stmt->execute($params)) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("[DEBUG] SQL RESULT: " . json_encode($result));
                return $result;
            }
            $errorInfo = $stmt->errorInfo();
            error_log("[ERROR] SQL Execute failed: " . json_encode($errorInfo));
            return null;
        } catch (Exception $e) {
            error_log("[ERROR] Database error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Exécute une requête et retourne une seule ligne
     */
    protected function executeOne(string $sql, array $params = []): ?array
    {
        error_log("[DEBUG] executeOne called - SQL: " . $sql);
        error_log("[DEBUG] executeOne params: " . json_encode($params));
        
        $results = $this->execute($sql, $params);
        
        if ($results === null) {
            error_log("[DEBUG] executeOne: results is NULL");
            return null;
        }
        
        error_log("[DEBUG] executeOne: results array has " . count($results) . " items");
        $returnValue = $results[0] ?? null;
        error_log("[DEBUG] executeOne: returning " . ($returnValue === null ? "NULL" : json_encode($returnValue)));
        
        return $returnValue;
    }

    /**
     * Exécute une requête de modification (INSERT, UPDATE, DELETE)
     */
    protected function executeUpdate(string $sql, array $params = []): bool
    {
        try {
            error_log("[DEBUG] SQL: " . $sql);
            $stmt = $this->cnx->prepare($sql);
            $result = $stmt->execute($params);
            if (!$result) {
                error_log("[ERROR] SQL Error: " . json_encode($stmt->errorInfo()));
            }
            return $result;
        } catch (Exception $e) {
            error_log("[ERROR] Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Exécute une requête INSERT et retourne l'ID inséré
     */
    protected function insert(string $sql, array $params = []): ?string
    {
        try {
            $stmt = $this->cnx->prepare($sql);
            if ($stmt->execute($params)) {
                return $this->getLastInsertId();
            }
            return null;
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
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
