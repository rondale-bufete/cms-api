<?php
require_once __DIR__ . '/config.php';

/**
 * Database connection class
 */
class Database {
    private $conn;
    
    /**
     * Constructor - establish database connection
     */
    public function __construct() {
        try {
            // Try PostgreSQL connection first (for Replit environment)
            if (getenv('PGHOST')) {
                $dsn = "pgsql:host=".DB_HOST.";dbname=".DB_NAME.";port=".getenv('PGPORT');
                $this->conn = new PDO($dsn, DB_USER, DB_PASS);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if tables exist, if not create them
                $this->createTablesIfNotExistPg();
            } else {
                // Fall back to MySQL for local development
                $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                
                if ($this->conn->connect_error) {
                    throw new Exception("Connection failed: " . $this->conn->connect_error);
                }
                
                // Check if database exists, if not create it
                $this->createDatabaseIfNotExists();
                
                // Check if tables exist, if not create them
                $this->createTablesIfNotExist();
            }
        } catch (Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
    
    /**
     * Create database if it doesn't exist
     */
    private function createDatabaseIfNotExists() {
        $this->conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $this->conn->select_db(DB_NAME);
    }
    
    /**
     * Create necessary tables if they don't exist
     */
    private function createTablesIfNotExist() {
        // Users table
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // 3D objects table
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS objects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                file_path VARCHAR(255) NOT NULL,
                file_type ENUM('obj', 'mtl', 'glb') NOT NULL,
                file_size INT NOT NULL,
                related_files TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
    
    /**
     * Get database connection
     *
     * @return mysqli|PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute query with prepared statements
     *
     * @param string $query SQL query with placeholders
     * @param string $types Types of parameters (s - string, i - integer, d - double, b - blob) - only used for mysqli
     * @param array $params Array of parameters to bind
     * @return array|bool Result array or boolean indicating success/failure
     */
    public function executeQuery($query, $types = "", $params = []) {
        // Different handling for PDO (PostgreSQL) and mysqli (MySQL)
        if ($this->conn instanceof PDO) {
            // PDO implementation for PostgreSQL
            try {
                $stmt = $this->conn->prepare($query);
                
                if (!$stmt) {
                    return false;
                }
                
                // Bind parameters by position (PDO style)
                if (!empty($params)) {
                    $stmt->execute($params);
                } else {
                    $stmt->execute();
                }
                
                // For SELECT queries
                if (stripos($query, 'SELECT') === 0) {
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    return $data;
                }
                
                // For INSERT, UPDATE, DELETE queries
                $affectedRows = $stmt->rowCount();
                $insertId = 0;
                
                // Get last insert ID if this was an INSERT
                if (stripos($query, 'INSERT') === 0) {
                    $insertId = $this->conn->lastInsertId();
                }
                
                return [
                    'affected_rows' => $affectedRows,
                    'insert_id' => $insertId
                ];
            } catch (PDOException $e) {
                die("Query Error: " . $e->getMessage());
            }
        } else {
            // Original mysqli implementation for MySQL
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                return false;
            }
            
            if (!empty($types) && !empty($params)) {
                $bindParams = array($types);
                for ($i = 0; $i < count($params); $i++) {
                    $bindParams[] = &$params[$i];
                }
                call_user_func_array(array($stmt, 'bind_param'), $bindParams);
            }
            
            $stmt->execute();
            
            // For SELECT queries
            $result = $stmt->get_result();
            if ($result) {
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $stmt->close();
                return $data;
            }
            
            // For INSERT, UPDATE, DELETE queries
            $affectedRows = $stmt->affected_rows;
            $insertId = $stmt->insert_id;
            $stmt->close();
            
            return [
                'affected_rows' => $affectedRows,
                'insert_id' => $insertId
            ];
        }
    }
    
    /**
     * Create necessary tables for PostgreSQL if they don't exist
     */
    private function createTablesIfNotExistPg() {
        // Check if users table exists
        $result = $this->conn->query("SELECT to_regclass('public.users')");
        $exists = $result->fetchColumn();
        
        if (!$exists) {
            // Users table
            $this->conn->exec("
                CREATE TABLE users (
                    id SERIAL PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
        
        // Check if objects table exists
        $result = $this->conn->query("SELECT to_regclass('public.objects')");
        $exists = $result->fetchColumn();
        
        if (!$exists) {
            // 3D objects table
            $this->conn->exec("
                CREATE TABLE objects (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    title VARCHAR(100) NOT NULL,
                    description TEXT,
                    file_path VARCHAR(255) NOT NULL,
                    file_type VARCHAR(10) NOT NULL CHECK (file_type IN ('obj', 'mtl', 'glb')),
                    file_size INTEGER NOT NULL,
                    related_files TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        }
    }

    /**
     * Close the database connection
     */
    public function closeConnection() {
        if ($this->conn instanceof PDO) {
            $this->conn = null;
        } else {
            $this->conn->close();
        }
    }
}
?>
