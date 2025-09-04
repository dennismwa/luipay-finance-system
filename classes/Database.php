<?php
/**
 * LuigiTals Wallet Management System
 * Database Handler Class
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    
    private static $instance = null;
    private $connection;
    private $statement;
    
    /**
     * Private constructor to prevent multiple instances
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $this->connection = new PDO(
                DatabaseConfig::getDsn(),
                DatabaseConfig::DB_USER,
                DatabaseConfig::DB_PASS,
                DatabaseConfig::getOptions()
            );
        } catch (PDOException $e) {
            $this->handleError('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prepare and execute a query
     */
    public function query($sql, $params = []) {
        try {
            $this->statement = $this->connection->prepare($sql);
            $this->statement->execute($params);
            return $this;
        } catch (PDOException $e) {
            $this->handleError('Query execution failed: ' . $e->getMessage(), $sql, $params);
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetch() {
        return $this->statement->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll() {
        return $this->statement->fetchAll();
    }
    
    /**
     * Fetch single column
     */
    public function fetchColumn($column = 0) {
        return $this->statement->fetchColumn($column);
    }
    
    /**
     * Get row count
     */
    public function rowCount() {
        return $this->statement->rowCount();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Insert data into table
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        $columnsList = implode(', ', $columns);
        
        $sql = "INSERT INTO {$table} ({$columnsList}) VALUES ({$placeholders})";
        
        try {
            $this->query($sql, $data);
            return $this->lastInsertId();
        } catch (Exception $e) {
            $this->handleError('Insert failed: ' . $e->getMessage(), $sql, $data);
            return false;
        }
    }
    
    /**
     * Update data in table
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        try {
            $this->query($sql, $params);
            return $this->rowCount();
        } catch (Exception $e) {
            $this->handleError('Update failed: ' . $e->getMessage(), $sql, $params);
            return false;
        }
    }
    
    /**
     * Delete data from table
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        try {
            $this->query($sql, $params);
            return $this->rowCount();
        } catch (Exception $e) {
            $this->handleError('Delete failed: ' . $e->getMessage(), $sql, $params);
            return false;
        }
    }
    
    /**
     * Soft delete (mark as deleted)
     */
    public function softDelete($table, $where, $params = []) {
        $data = [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($table, $data, $where, $params);
    }
    
    /**
     * Select data from table
     */
    public function select($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '') {
        $sql = "SELECT {$columns} FROM {$table}";
        
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->query($sql, $params);
    }
    
    /**
     * Check if record exists
     */
    public function exists($table, $where, $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->fetchColumn() > 0;
    }
    
    /**
     * Get table row count
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table}";
        
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        return $this->query($sql, $params)->fetchColumn();
    }
    
    /**
     * Execute stored procedure
     */
    public function callProcedure($procedure, $params = []) {
        $placeholders = str_repeat('?,', count($params) - 1) . '?';
        $sql = "CALL {$procedure}({$placeholders})";
        
        try {
            $this->statement = $this->connection->prepare($sql);
            $this->statement->execute(array_values($params));
            return $this;
        } catch (PDOException $e) {
            $this->handleError('Procedure execution failed: ' . $e->getMessage(), $sql, $params);
        }
    }
    
    /**
     * Paginate results
     */
    public function paginate($table, $page = 1, $perPage = null, $where = '', $params = [], $orderBy = '') {
        if ($perPage === null) {
            $perPage = DatabaseConfig::DEFAULT_PAGE_SIZE;
        }
        
        $perPage = min($perPage, DatabaseConfig::MAX_PAGE_SIZE);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $totalCount = $this->count($table, $where, $params);
        $totalPages = ceil($totalCount / $perPage);
        
        // Get paginated results
        $limit = "{$offset}, {$perPage}";
        $results = $this->select('*', $table, $where, $params, $orderBy, $limit)->fetchAll();
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }
    
    /**
     * Create database backup
     */
    public function backup($filename = null) {
        if (!DatabaseConfig::BACKUP_ENABLED) {
            return false;
        }
        
        if ($filename === null) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $backupPath = DatabaseConfig::getBackupPath();
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $filepath = $backupPath . $filename;
        
        // Execute mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            DatabaseConfig::DB_USER,
            DatabaseConfig::DB_PASS,
            DatabaseConfig::DB_HOST,
            DatabaseConfig::DB_NAME,
            $filepath
        );
        
        exec($command, $output, $returnCode);
        
        return $returnCode === 0 ? $filepath : false;
    }
    
    /**
     * Clean old backups
     */
    public function cleanOldBackups() {
        if (!DatabaseConfig::BACKUP_ENABLED) {
            return false;
        }
        
        $backupPath = DatabaseConfig::getBackupPath();
        $retentionDays = DatabaseConfig::BACKUP_RETENTION_DAYS;
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        
        if (is_dir($backupPath)) {
            $files = glob($backupPath . '*.sql');
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get database statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Get table sizes
        $sql = "
            SELECT 
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = :database
            ORDER BY size_mb DESC
        ";
        
        $tables = $this->query($sql, ['database' => DatabaseConfig::DB_NAME])->fetchAll();
        $stats['tables'] = $tables;
        
        // Get total database size
        $sql = "
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS total_size_mb
            FROM information_schema.tables 
            WHERE table_schema = :database
        ";
        
        $stats['total_size'] = $this->query($sql, ['database' => DatabaseConfig::DB_NAME])->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Handle database errors
     */
    private function handleError($message, $sql = '', $params = []) {
        $error = [
            'message' => $message,
            'sql' => $sql,
            'params' => $params,
            'timestamp' => date('Y-m-d H:i:s'),
            'trace' => debug_backtrace()
        ];
        
        // Log error
        error_log(json_encode($error));
        
        if (DatabaseConfig::isDevelopment()) {
            throw new Exception($message);
        } else {
            throw new Exception('A database error occurred. Please try again later.');
        }
    }
    
    /**
     * Prevent cloning
     */
    public function __clone() {
        throw new Exception('Cannot clone Database instance');
    }
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize Database instance');
    }
    
    /**
     * Close connection on destruct
     */
    public function __destruct() {
        $this->connection = null;
    }
}

?>