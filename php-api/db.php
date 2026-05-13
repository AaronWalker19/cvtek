<?php
/**
 * db.php - Gestion de la connexion PDO à MySQL
 * 
 * Utilise PDO pour les requêtes préparées et la sécurité SQL injection
 * Compatible PHP 8+
 */

class Database {
    private static ?PDO $connection = null;
    
    /**
     * Récupère ou établit la connexion PDO
     */
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            self::connect();
        }
        return self::$connection;
    }
    
    /**
     * Établit la connexion à MySQL
     */
    private static function connect(): void {
        try {
            // Configuration depuis variables d'environnement
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbname = getenv('DB_NAME') ?: 'cvtek';
            $user = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASSWORD') ?: '';
            $port = getenv('DB_PORT') ?: 3306;
            
            // DSN PDO
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            
            // Options PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Connexion
            self::$connection = new PDO($dsn, $user, $password, $options);
            
            error_log("✅ Base de données connectée: {$dbname}@{$host}");
            
        } catch (PDOException $e) {
            error_log("❌ Erreur connexion BD: " . $e->getMessage());
            throw new Exception("Erreur connexion base de données", 500);
        }
    }
    
    /**
     * Exécute une requête préparée
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres liés
     * @return PDOStatement
     */
    public static function execute(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("❌ Erreur SQL: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Erreur base de données: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * Récupère une seule ligne
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::execute($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Récupère toutes les lignes
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Retourne le dernier ID inséré
     */
    public static function lastInsertId(): string {
        return self::getConnection()->lastInsertId();
    }
    
    /**
     * Compte les lignes affectées par la dernière requête
     */
    public static function rowCount(PDOStatement $stmt): int {
        return $stmt->rowCount();
    }
    
    /**
     * Commence une transaction
     */
    public static function beginTransaction(): void {
        self::getConnection()->beginTransaction();
    }
    
    /**
     * Valide une transaction
     */
    public static function commit(): void {
        self::getConnection()->commit();
    }
    
    /**
     * Annule une transaction
     */
    public static function rollback(): void {
        self::getConnection()->rollBack();
    }
}

?>
