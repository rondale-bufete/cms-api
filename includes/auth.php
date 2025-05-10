<?php
require_once __DIR__ . '/database.php';

/**
 * Authentication class for user management
 */
class Auth {
    private $db;
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Register a new user
     *
     * @param string $username Username
     * @param string $email Email
     * @param string $password Password
     * @return array Result array with status and message
     */
    public function register($username, $email, $password) {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return [
                'status' => 'error',
                'message' => 'All fields are required'
            ];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'message' => 'Invalid email format'
            ];
        }
        
        if (strlen($password) < 6) {
            return [
                'status' => 'error',
                'message' => 'Password must be at least 6 characters long'
            ];
        }
        
        // Check if username or email already exists
        $query = "SELECT * FROM users WHERE username = ? OR email = ?";
        $result = $this->db->executeQuery($query, "ss", [$username, $email]);
        
        if (count($result) > 0) {
            return [
                'status' => 'error',
                'message' => 'Username or email already exists'
            ];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $result = $this->db->executeQuery($query, "sss", [$username, $email, $hashedPassword]);
        
        if ($result['affected_rows'] > 0) {
            return [
                'status' => 'success',
                'message' => 'Registration successful',
                'user_id' => $result['insert_id']
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Registration failed'
            ];
        }
    }
    
    /**
     * Login a user
     *
     * @param string $username Username or email
     * @param string $password Password
     * @return array Result array with status and message
     */
    public function login($username, $password) {
        // Validate input
        if (empty($username) || empty($password)) {
            return [
                'status' => 'error',
                'message' => 'All fields are required'
            ];
        }
        
        // Get user by username or email
        $query = "SELECT * FROM users WHERE username = ? OR email = ?";
        $result = $this->db->executeQuery($query, "ss", [$username, $username]);
        
        if (count($result) === 0) {
            return [
                'status' => 'error',
                'message' => 'Invalid username or password'
            ];
        }
        
        $user = $result[0];
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            
            return [
                'status' => 'success',
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Invalid username or password'
            ];
        }
    }
    
    /**
     * Check if user is logged in
     *
     * @return bool True if logged in, false otherwise
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user
     *
     * @return array|null User data or null if not logged in
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $query = "SELECT id, username, email, created_at FROM users WHERE id = ?";
        $result = $this->db->executeQuery($query, "i", [$_SESSION['user_id']]);
        
        return count($result) > 0 ? $result[0] : null;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
    }
}
?>
