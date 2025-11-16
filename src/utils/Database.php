<?php
/**
 * BuzzarFeed - Database Connection Class
 * 
 * Singleton pattern implementation for database connections
 * Following ISO 9241: Maintainability and Reusability
 * 
 * @package BuzzarFeed\Utils
 * @version 1.0
 */

namespace BuzzarFeed\Utils;

use PDO;
use PDOException;

class Database {
    
    /**
     * @var Database|null Singleton instance
     */
    private static ?Database $instance = null;
    
    /**
     * @var PDO|null PDO connection object
     */
    private ?PDO $connection = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    /**
     * Get singleton instance
     * 
     * @return Database The singleton instance
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     * 
     * @return void
     * @throws PDOException If connection fails
     */
    private function connect(): void {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            
            if (DEVELOPMENT_MODE) {
                throw $e;
            } else {
                throw new PDOException("Database connection failed");
            }
        }
    }
    
    /**
     * Get the PDO connection object
     * 
     * @return PDO The PDO connection
     */
    public function getConnection(): PDO {
        // Reconnect if connection is lost
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Execute a query and return results
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array Query results
     */
    public function query(string $query, array $params = []): array {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Execute a query and return single result
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|null Single result or null
     */
    public function querySingle(string $query, array $params = []): ?array {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Execute an insert, update, or delete query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return int Number of affected rows
     */
    public function execute(string $query, array $params = []): int {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Execute Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string Last insert ID
     */
    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool Success status
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool Success status
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }
}
