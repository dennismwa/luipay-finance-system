<?php
/**
 * LuigiTals Wallet Management System
 * Authentication API Endpoints
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/classes/Auth.php';
require_once APP_ROOT . '/classes/Security.php';

$auth = new Auth();
$security = new Security();

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['action'] ?? '';

// Rate limiting
$clientId = $security->getClientIp() . '_' . ($endpoint ?: 'auth');
if (!$security->checkRateLimit($clientId, 10, 300)) { // 10 requests per 5 minutes
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}

$security->logRateLimitAttempt($clientId);

try {
    switch ($method) {
        case 'POST':
            handlePost($endpoint, $auth, $security);
            break;
        case 'GET':
            handleGet($endpoint, $auth);
            break;
        case 'DELETE':
            handleDelete($endpoint, $auth);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Auth API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handlePost($endpoint, $auth, $security) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($endpoint) {
        case 'login':
            $username = $security->sanitizeInput($input['username'] ?? '');
            $password = $input['password'] ?? '';
            $rememberMe = $input['remember_me'] ?? false;
            
            if (empty($username) || empty($password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                return;
            }
            
            $result = $auth->login($username, $password, $rememberMe);
            
            if ($result['success']) {
                $security->logSecurityEvent('login_success', ['username' => $username]);
                http_response_code(200);
            } else {
                $security->logSecurityEvent('login_failed', ['username' => $username]);
                http_response_code(401);
            }
            
            echo json_encode($result);
            break;
            
        case 'change-password':
            $auth->requireAuth();
            $auth->requireCsrfToken();
            
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'All password fields are required']);
                return;
            }
            
            if ($newPassword !== $confirmPassword) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
                return;
            }
            
            $passwordValidation = $security->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Password validation failed',
                    'errors' => $passwordValidation['errors']
                ]);
                return;
            }
            
            $user = $auth->getCurrentUser();
            $result = $auth->changePassword($user['id'], $currentPassword, $newPassword);
            
            if ($result['success']) {
                $security->logSecurityEvent('password_changed', ['user_id' => $user['id']]);
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'verify-session':
            if ($auth->isAuthenticated()) {
                $user = $auth->getCurrentUser();
                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'user' => $user,
                    'csrf_token' => $auth->generateCsrfToken()
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'authenticated' => false
                ]);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handleGet($endpoint, $auth) {
    switch ($endpoint) {
        case 'user':
            $auth->requireAuth();
            
            $user = $auth->getCurrentUser();
            if ($user) {
                echo json_encode([
                    'success' => true,
                    'data' => $user,
                    'csrf_token' => $auth->generateCsrfToken()
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            break;
            
        case 'csrf-token':
            $auth->requireAuth();
            echo json_encode([
                'success' => true,
                'csrf_token' => $auth->generateCsrfToken()
            ]);
            break;
            
        case 'session-info':
            if ($auth->isAuthenticated()) {
                $user = $auth->getCurrentUser();
                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'full_name' => $user['full_name'],
                        'last_login' => $user['last_login']
                    ],
                    'session' => [
                        'login_time' => $_SESSION['login_time'] ?? null,
                        'expires_in' => DatabaseConfig::SESSION_LIFETIME - (time() - ($_SESSION['login_time'] ?? time()))
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'authenticated' => false
                ]);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handleDelete($endpoint, $auth) {
    switch ($endpoint) {
        case 'logout':
            if ($auth->isAuthenticated()) {
                $user = $auth->getCurrentUser();
                $result = $auth->logout();
                
                if ($result['success']) {
                    $security = new Security();
                    $security->logSecurityEvent('logout', ['user_id' => $user['id']]);
                }
                
                echo json_encode($result);
            } else {
                echo json_encode(['success' => true, 'message' => 'Already logged out']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

?>