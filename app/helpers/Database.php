<?php
/**
 * Database Connection Handler
 * Singleton pattern for database connections
 */

class Database {
    private static ?PDO $instance = null;
    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Get database instance (singleton)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Connect to database
     */
    private static function connect(): void {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ':' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            
            self::$instance = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                self::$options
            );
            
            Logger::info('Database connection established successfully');
        } catch (PDOException $e) {
            Logger::error('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    /**
     * Execute a query
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $db = self::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        return $stmt->fetch() ?: null;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Insert record
     */
    public static function insert(string $table, array $data): int {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
        
        self::query($sql, array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Update record
     */
    public static function update(string $table, array $data, array $where): int {
        $set = array_map(fn($col) => $col . '=?', array_keys($data));
        $whereConditions = array_map(fn($col) => $col . '=?', array_keys($where));
        
        $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $set) . ' WHERE ' . implode(' AND ', $whereConditions);
        
        $values = array_merge(array_values($data), array_values($where));
        $stmt = self::query($sql, $values);
        
        return $stmt->rowCount();
    }

    /**
     * Delete record
     */
    public static function delete(string $table, array $where): int {
        $whereConditions = array_map(fn($col) => $col . '=?', array_keys($where));
        $sql = 'DELETE FROM ' . $table . ' WHERE ' . implode(' AND ', $whereConditions);
        
        $stmt = self::query($sql, array_values($where));
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool {
        return self::getInstance()->rollback();
    }

    /**
     * Close connection
     */
    public static function close(): void {
        self::$instance = null;
    }
}
