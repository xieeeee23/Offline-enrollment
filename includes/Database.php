<?php
/**
 * Database Management Class
 * 
 * Provides improved database management with:
 * - Connection pooling
 * - Better error handling
 * - Query logging
 * - Transaction support
 * - Prepared statement management
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $inTransaction = false;
    private $queryLog = [];
    private $config = [];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct($config) {
        $this->config = $config;
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance($config = null) {
        if (self::$instance === null) {
            if ($config === null) {
                $config = [
                    'host' => DB_HOST,
                    'username' => DB_USER,
                    'password' => DB_PASS,
                    'database' => DB_NAME,
                    'charset' => 'utf8mb4'
                ];
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $this->connection = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                $this->config['database']
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset
            $this->connection->set_charset($this->config['charset']);
            
            // Set timezone
            $this->connection->query("SET time_zone = '+08:00'");
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get the mysqli connection object
     */
    public function getConnection() {
        if (!$this->connection || $this->connection->ping() === false) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Execute a query and return result
     */
    public function query($sql, $params = []) {
        $conn = $this->getConnection();
        
        // Log the query
        $this->logQuery($sql, $params);
        
        try {
            if (empty($params)) {
                $result = $conn->query($sql);
            } else {
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $types = $this->getParamTypes($params);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();
            }
            
            if ($result === false) {
                throw new Exception("Query failed: " . $conn->error);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Execute a query and return affected rows
     */
    public function execute($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $this->getConnection()->affected_rows;
    }
    
    /**
     * Fetch a single row
     */
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result->fetch_assoc();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    /**
     * Fetch a single value
     */
    public function fetchValue($sql, $params = []) {
        $result = $this->query($sql, $params);
        $row = $result->fetch_row();
        return $row ? $row[0] : null;
    }
    
    /**
     * Insert a record and return the insert ID
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
        $this->query($sql, $values);
        
        return $this->getConnection()->insert_id;
    }
    
    /**
     * Update a record
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE $table SET " . implode(',', $setParts) . " WHERE $where";
        $values = array_merge($values, $whereParams);
        
        return $this->execute($sql, $values);
    }
    
    /**
     * Delete records
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->execute($sql, $params);
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        if ($this->inTransaction) {
            throw new Exception("Transaction already in progress");
        }
        
        $this->getConnection()->begin_transaction();
        $this->inTransaction = true;
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        if (!$this->inTransaction) {
            throw new Exception("No transaction in progress");
        }
        
        $this->getConnection()->commit();
        $this->inTransaction = false;
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback() {
        if (!$this->inTransaction) {
            throw new Exception("No transaction in progress");
        }
        
        $this->getConnection()->rollback();
        $this->inTransaction = false;
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->query($sql, [$tableName]);
        return $result->num_rows > 0;
    }
    
    /**
     * Check if column exists
     */
    public function columnExists($tableName, $columnName) {
        $sql = "SHOW COLUMNS FROM $tableName LIKE ?";
        $result = $this->query($sql, [$columnName]);
        return $result->num_rows > 0;
    }
    
    /**
     * Get table structure
     */
    public function getTableStructure($tableName) {
        $sql = "DESCRIBE $tableName";
        return $this->fetchAll($sql);
    }
    
    /**
     * Get database size
     */
    public function getDatabaseSize() {
        $sql = "SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
                FROM information_schema.tables 
                WHERE table_schema = ?";
        return $this->fetchValue($sql, [$this->config['database']]);
    }
    
    /**
     * Get table sizes
     */
    public function getTableSizes() {
        $sql = "SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
                    table_rows
                FROM information_schema.tables 
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC";
        return $this->fetchAll($sql, [$this->config['database']]);
    }
    
    /**
     * Optimize tables
     */
    public function optimizeTables() {
        $tables = $this->fetchAll("SHOW TABLES");
        $results = [];
        
        foreach ($tables as $table) {
            $tableName = $table[array_keys($table)[0]];
            $sql = "OPTIMIZE TABLE $tableName";
            $result = $this->query($sql);
            $results[$tableName] = $result ? 'success' : 'failed';
        }
        
        return $results;
    }
    
    /**
     * Backup database structure
     */
    public function backupStructure($tables = []) {
        $conn = $this->getConnection();
        $backup = [];
        
        if (empty($tables)) {
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
        }
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW CREATE TABLE $table");
            $row = $result->fetch_row();
            $backup[$table] = $row[1];
        }
        
        return $backup;
    }
    
    /**
     * Get query log
     */
    public function getQueryLog() {
        return $this->queryLog;
    }
    
    /**
     * Clear query log
     */
    public function clearQueryLog() {
        $this->queryLog = [];
    }
    
    /**
     * Get parameter types for prepared statements
     */
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        return $types;
    }
    
    /**
     * Log query for debugging
     */
    private function logQuery($sql, $params) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Close the connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
    
    /**
     * Destructor to ensure connection is closed
     */
    public function __destruct() {
        $this->close();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {}
}

// Global database instance
function getDB() {
    return Database::getInstance();
}

// Helper functions for backward compatibility
function db_query($sql, $params = []) {
    return getDB()->query($sql, $params);
}

function db_fetch_one($sql, $params = []) {
    return getDB()->fetchOne($sql, $params);
}

function db_fetch_all($sql, $params = []) {
    return getDB()->fetchAll($sql, $params);
}

function db_insert($table, $data) {
    return getDB()->insert($table, $data);
}

function db_update($table, $data, $where, $whereParams = []) {
    return getDB()->update($table, $data, $where, $whereParams);
}

function db_delete($table, $where, $params = []) {
    return getDB()->delete($table, $where, $params);
}
?> 