<?php
/**
 * LuigiTals Wallet Management System
 * Security Utilities Class
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

class Security {
    
    /**
     * Generate secure random token
     */
    public function generateToken($length = 32) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            return bin2hex(mcrypt_create_iv($length / 2, MCRYPT_DEV_URANDOM));
        }
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }
    
    /**
     * Validate email address
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < DatabaseConfig::PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . DatabaseConfig::PASSWORD_MIN_LENGTH . ' characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Hash password securely
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password hash
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data, $key = null) {
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }
        
        $cipher = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encryptedData, $key = null) {
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }
        
        $data = base64_decode($encryptedData);
        $cipher = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     */
    private function getEncryptionKey() {
        // In production, store this in environment variable or config file
        return hash('sha256', 'LuigiTalsWallet2024SecureKey' . DatabaseConfig::DB_NAME);
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file) {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed with error code: ' . $file['error'];
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > DatabaseConfig::MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds maximum allowed size of ' . 
                       number_format(DatabaseConfig::MAX_FILE_SIZE / 1024 / 1024, 1) . 'MB';
        }
        
        // Check file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, DatabaseConfig::ALLOWED_FILE_TYPES)) {
            $errors[] = 'File type not allowed. Allowed types: ' . 
                       implode(', ', DatabaseConfig::ALLOWED_FILE_TYPES);
        }
        
        // Check MIME type
        $allowedMimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $errors[] = 'Invalid file type detected';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Generate secure filename
     */
    public function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = $this->generateToken(16) . '_' . time() . '.' . $extension;
        
        return $filename;
    }
    
    /**
     * Rate limiting check
     */
    public function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        $now = time();
        $attempts = $_SESSION[$key];
        
        // Remove old attempts outside time window
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Update session
        $_SESSION[$key] = $attempts;
        
        return count($attempts) < $maxAttempts;
    }
    
    /**
     * Log rate limit attempt
     */
    public function logRateLimitAttempt($identifier) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        $_SESSION[$key][] = time();
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Clean input for SQL
     */
    public function cleanSqlInput($input) {
        return trim(strip_tags($input));
    }
    
    /**
     * Validate IP address
     */
    public function validateIpAddress($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Get client IP address
     */
    public function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if ($this->validateIpAddress($ip)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($event, $details = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? null,
            'details' => $details
        ];
        
        $logEntry = json_encode($logData) . PHP_EOL;
        file_put_contents('logs/security.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check for suspicious activity
     */
    public function checkSuspiciousActivity($patterns = []) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        $suspiciousPatterns = array_merge([
            '/select.*from/i',
            '/union.*select/i',
            '/drop.*table/i',
            '/script.*>/i',
            '/javascript:/i',
            '/<script/i',
            '/eval\(/i',
            '/base64_decode/i'
        ], $patterns);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent . ' ' . $requestUri)) {
                $this->logSecurityEvent('suspicious_activity', [
                    'pattern' => $pattern,
                    'user_agent' => $userAgent,
                    'request_uri' => $requestUri
                ]);
                return true;
            }
        }
        
        return false;
    }
}

?>