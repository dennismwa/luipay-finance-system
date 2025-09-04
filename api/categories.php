<?php
/**
 * LuigiTals Wallet Management System
 * Categories API Endpoints
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/classes/Auth.php';
require_once APP_ROOT . '/classes/Category.php';
require_once APP_ROOT . '/classes/Security.php';

$auth = new Auth();
$category = new Category();
$security = new Security();

// Require authentication
$auth->requireAuth();
$user = $auth->getCurrentUser();

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'POST':
            handlePost($endpoint, $category, $auth, $user, $security);
            break;
        case 'GET':
            handleGet($endpoint, $category, $user, $id);
            break;
        case 'PUT':
            handlePut($endpoint, $category, $auth, $user, $id);
            break;
        case 'DELETE':
            handleDelete($endpoint, $category, $auth, $user, $id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Categories API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handlePost($endpoint, $category, $auth, $user, $security) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($endpoint) {
        case 'create':
            $auth->requireCsrfToken();
            
            $data = [
                'user_id' => $user['id'],
                'name' => $input['name'] ?? '',
                'icon' => $input['icon'] ?? '📁',
                'color' => $input['color'] ?? '#3B82F6',
                'budget_limit' => $input['budget_limit'] ?? 0,
                'type' => $input['type'] ?? 'expense',
                'sort_order' => $input['sort_order'] ?? null
            ];
            
            $result = $category->create($data);
            
            if ($result['success']) {
                http_response_code(201);
                $security->logSecurityEvent('category_created', [
                    'category_id' => $result['data']['id'],
                    'name' => $data['name']
                ]);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'create-defaults':
            $auth->requireCsrfToken();
            
            $result = $category->createDefaultCategories($user['id']);
            
            if ($result['success']) {
                http_response_code(201);
                $security->logSecurityEvent('default_categories_created', [
                    'user_id' => $user['id'],
                    'count' => $result['count']
                ]);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'reorder':
            $auth->requireCsrfToken();
            
            $categoryOrders = $input['categories'] ?? [];
            
            if (empty($categoryOrders) || !is_array($categoryOrders)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Categories array is required']);
                return;
            }
            
            $result = $category->reorder($user['id'], $categoryOrders);
            
            if ($result['success']) {
                http_response_code(200);
                $security->logSecurityEvent('categories_reordered', [
                    'user_id' => $user['id'],
                    'count' => count($categoryOrders)
                ]);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handleGet($endpoint, $category, $user, $id) {
    switch ($endpoint) {
        case 'list':
            $type = $_GET['type'] ?? null;
            
            if ($type && !in_array($type, ['income', 'expense'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid type parameter']);
                return;
            }
            
            $categories = $category->getForUser($user['id'], $type);
            
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
            break;
            
        case 'get':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category ID is required']);
                return;
            }
            
            $categoryData = $category->getById($id);
            
            if ($categoryData && $categoryData['user_id'] == $user['id']) {
                echo json_encode([
                    'success' => true,
                    'data' => $categoryData
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Category not found']);
            }
            break;
            
        case 'statistics':
            $period = $_GET['period'] ?? 'month';
            $categoryId = $_GET['category_id'] ?? null;
            
            if (!in_array($period, ['today', 'week', 'month', 'quarter', 'year'])) {
                $period = 'month';
            }
            
            $stats = $category->getStatistics($user['id'], $categoryId, $period);
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'period' => $period
            ]);
            break;
            
        case 'budget-usage':
            $period = $_GET['period'] ?? 'month';
            
            if (!in_array($period, ['today', 'week', 'month', 'quarter', 'year'])) {
                $period = 'month';
            }
            
            $budgetUsage = $category->getBudgetUsage($user['id'], $period);
            
            echo json_encode([
                'success' => true,
                'data' => $budgetUsage,
                'period' => $period
            ]);
            break;
            
        case 'defaults':
            $defaults = $category->getDefaultCategories();
            
            echo json_encode([
                'success' => true,
                'data' => $defaults
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handlePut($endpoint, $category, $auth, $user, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category ID is required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($endpoint) {
        case 'update':
            $auth->requireCsrfToken();
            
            $result = $category->update($id, $input, $user['id']);
            
            if ($result['success']) {
                $security = new Security();
                $security->logSecurityEvent('category_updated', [
                    'category_id' => $id,
                    'user_id' => $user['id']
                ]);
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handleDelete($endpoint, $category, $auth, $user, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Category ID is required']);
        return;
    }
    
    switch ($endpoint) {
        case 'delete':
            $auth->requireCsrfToken();
            
            $result = $category->delete($id, $user['id']);
            
            if ($result['success']) {
                $security = new Security();
                $security->logSecurityEvent('category_deleted', [
                    'category_id' => $id,
                    'user_id' => $user['id']
                ]);
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

?>