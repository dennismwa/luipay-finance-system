<?php
/**
 * LuigiTals Wallet Management System
 * Database Configuration File
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Database Configuration
class DatabaseConfig {
    
    // Database connection parameters
    const DB_HOST = 'localhost';
    const DB_NAME = 'vxjtgclw_luigitals_wallet';
    const DB_USER = 'vxjtgclw_luigitals_wallet';  // Change this for production
    const DB_PASS = 'LUdWc&Uc6T0Z(Q.H';      // Change this for production
    const DB_CHARSET = 'utf8mb4';
    
    // Connection options
    const DB_OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    // Application settings
    const APP_NAME = 'LuiDigitals Wallet';
    const APP_VERSION = '1.0.0';
    const APP_ENV = 'development'; // development, staging, production
    
    // Security settings
    const SESSION_NAME = 'luigitals_wallet_session';
    const SESSION_LIFETIME = 7200; // 2 hours
    const CSRF_TOKEN_NAME = '_token';
    const PASSWORD_MIN_LENGTH = 8;
    
    // File upload settings
    const MAX_FILE_SIZE = 5242880; // 5MB
    const ALLOWED_FILE_TYPES = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    const UPLOAD_PATH = 'uploads/';
    
    // Currency settings
    const DEFAULT_CURRENCY = 'KES';
    const CURRENCY_SYMBOL = 'KSH';
    const CURRENCY_POSITION = 'before'; // before, after
    
    // Timezone
    const DEFAULT_TIMEZONE = 'UTC';
    
    // Pagination
    const DEFAULT_PAGE_SIZE = 25;
    const MAX_PAGE_SIZE = 100;
    
    // Cache settings
    const CACHE_ENABLED = true;
    const CACHE_LIFETIME = 3600; // 1 hour
    
    // Email settings (for notifications)
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = '';
    const SMTP_PASSWORD = '';
    const SMTP_ENCRYPTION = 'tls';
    const FROM_EMAIL = 'noreply@luigitals.com';
    const FROM_NAME = 'LuigiTals Wallet';
    
    // API settings
    const API_VERSION = 'v1';
    const API_RATE_LIMIT = 100; // requests per minute
    
    // Backup settings
    const BACKUP_ENABLED = true;
    const BACKUP_RETENTION_DAYS = 30;
    const BACKUP_PATH = 'backups/';
    
    /**
     * Get database DSN
     */
    public static function getDsn() {
        return sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            self::DB_HOST,
            self::DB_NAME,
            self::DB_CHARSET
        );
    }
    
    /**
     * Get database connection options
     */
    public static function getOptions() {
        return self::DB_OPTIONS;
    }
    
    /**
     * Check if application is in production
     */
    public static function isProduction() {
        return self::APP_ENV === 'production';
    }
    
    /**
     * Check if application is in development
     */
    public static function isDevelopment() {
        return self::APP_ENV === 'development';
    }
    
    /**
     * Get upload directory path
     */
    public static function getUploadPath() {
        return APP_ROOT . '/' . self::UPLOAD_PATH;
    }
    
    /**
     * Get backup directory path
     */
    public static function getBackupPath() {
        return APP_ROOT . '/' . self::BACKUP_PATH;
    }
    
    /**
     * Format currency amount
     */
    public static function formatCurrency($amount) {
        $formatted = number_format($amount, 2);
        
        if (self::CURRENCY_POSITION === 'before') {
            return self::CURRENCY_SYMBOL . $formatted;
        } else {
            return $formatted . self::CURRENCY_SYMBOL;
        }
    }
    
    /**
     * Get application settings as array
     */
    public static function getSettings() {
        return [
            'app_name' => self::APP_NAME,
            'app_version' => self::APP_VERSION,
            'app_env' => self::APP_ENV,
            'currency' => self::DEFAULT_CURRENCY,
            'currency_symbol' => self::CURRENCY_SYMBOL,
            'currency_position' => self::CURRENCY_POSITION,
            'timezone' => self::DEFAULT_TIMEZONE,
            'page_size' => self::DEFAULT_PAGE_SIZE,
            'max_file_size' => self::MAX_FILE_SIZE,
            'allowed_file_types' => self::ALLOWED_FILE_TYPES
        ];
    }
}

// Error reporting based on environment
if (DatabaseConfig::isDevelopment()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Set timezone
date_default_timezone_set(DatabaseConfig::DEFAULT_TIMEZONE);

// Set session parameters
ini_set('session.name', DatabaseConfig::SESSION_NAME);
ini_set('session.gc_maxlifetime', DatabaseConfig::SESSION_LIFETIME);
ini_set('session.cookie_lifetime', DatabaseConfig::SESSION_LIFETIME);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

?>