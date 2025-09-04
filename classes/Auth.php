<?php
/**
 * LuigiTals Wallet Management System
 * Authentication Handler Class
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Auth {
    
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->security = new Security();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Authenticate user login
     */
    public function login($username, $password, $rememberMe = false) {
        try {
            // Validate input
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username and password are required'];
            }
            
            // Check for rate limiting
            if ($this->isRateLimited($username)) {
                return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
            }
            
            // Get user from database
            $user = $this->db->select('users', '*', 'username = :username AND is_active = 1', [
                'username' => $username
            ])->fetch();
            
            if (!$user) {
                $this->logFailedAttempt($username);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->logFailedAttempt($username);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Check if password needs rehashing
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $this->db->update('users', ['password_hash' => $newHash], 'id = :id', ['id' => $user['id']]);
            }
            
            // Create session
            $this->createSession($user, $rememberMe);
            
            // Update last login
            $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
            
            // Clear failed attempts
            $this->clearFailedAttempts($username);
            
            return ['success' => true, 'message' => 'Login successful', 'user' => $this->sanitizeUser($user)];
            
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login'];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        try {
            // Remove remember me cookie if exists
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
                
                // Remove from database
                if (isset($_SESSION['user_id'])) {
                    $this->db->update('users', ['remember_token' => null], 'id = :id', [
                        'id' => $_SESSION['user_id']
                    ]);
                }
            }
            
            // Destroy session
            session_destroy();
            
            return ['success' => true, 'message' => 'Logout successful'];
            
        } catch (Exception $e) {
            error_log('Logout error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during logout'];
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
            // Verify session is still valid
            $user = $this->db->select('users', 'id, username, is_active', 'id = :id AND is_active = 1', [
                'id' => $_SESSION['user_id']
            ])->fetch();
            
            if ($user && $user['username'] === $_SESSION['username']) {
                return true;
            }
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            return $this->checkRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $user = $this->db->select('users', '*', 'id = :id AND is_active = 1', [
            'id' => $_SESSION['user_id']
        ])->fetch();
        
        return $user ? $this->sanitizeUser($user) : null;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Authentication required'], 401);
            } else {
                header('Location: login.php');
                exit;
            }
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get user
            $user = $this->db->select('users', 'password_hash', 'id = :id', ['id' => $userId])->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (strlen($newPassword) < DatabaseConfig::PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'Password must be at least ' . DatabaseConfig::PASSWORD_MIN_LENGTH . ' characters'];
            }
            
            // Hash new password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $updated = $this->db->update('users', ['password_hash' => $newHash], 'id = :id', ['id' => $userId]);
            
            if ($updated) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update password'];
            }
            
        } catch (Exception $e) {
            error_log('Change password error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while changing password'];
        }
    }
    
    /**
     * Create user session
     */
    private function createSession($user, $rememberMe = false) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = $this->security->generateToken();
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $token = $this->security->generateToken(32);
            $hashedToken = hash('sha256', $token);
            
            // Store token in database
            $this->db->update('users', ['remember_token' => $hashedToken], 'id = :id', ['id' => $user['id']]);
            
            // Set cookie (30 days)
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', isset($_SERVER['HTTPS']), true);
        }
    }
    
    /**
     * Check remember me token
     */
    private function checkRememberToken($token) {
        $hashedToken = hash('sha256', $token);
        
        $user = $this->db->select('users', '*', 'remember_token = :token AND is_active = 1', [
            'token' => $hashedToken
        ])->fetch();
        
        if ($user) {
            $this->createSession($user);
            return true;
        }
        
        // Invalid token, remove cookie
        setcookie('remember_token', '', time() - 3600, '/');
        return false;
    }
    
    /**
     * Check if IP is rate limited
     */
    private function isRateLimited($username) {
        $key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION[$key])) {
            return false;
        }
        
        $attempts = $_SESSION[$key];
        
        // Allow 5 attempts per 15 minutes
        if (count($attempts) >= 5) {
            $oldestAttempt = min($attempts);
            if (time() - $oldestAttempt < 900) { // 15 minutes
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log failed login attempt
     */
    private function logFailedAttempt($username) {
        $key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        $_SESSION[$key][] = time();
        
        // Keep only last 10 attempts
        $_SESSION[$key] = array_slice($_SESSION[$key], -10);
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts($username) {
        $key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
        unset($_SESSION[$key]);
    }
    
    /**
     * Sanitize user data for output
     */
    private function sanitizeUser($user) {
        unset($user['password_hash']);
        unset($user['remember_token']);
        return $user;
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Send JSON response
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = $this->security->generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Require CSRF token
     */
    public function requireCsrfToken() {
        $token = $_POST[DatabaseConfig::CSRF_TOKEN_NAME] ?? $_GET[DatabaseConfig::CSRF_TOKEN_NAME] ?? '';
        
        if (!$this->verifyCsrfToken($token)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid security token'], 403);
            } else {
                die('Invalid security token');
            }
        }
    }
}

?>