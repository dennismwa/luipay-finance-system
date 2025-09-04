<?php
/**
 * LuigiTals Wallet Management System
 * Budgets API Endpoints
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
require_once APP_ROOT . '/classes/Budget.php';
require_once APP_ROOT . '/classes/Security.php';

$auth = new Auth();
$budget = new Budget();
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
            handlePost($endpoint, $budget, $auth, $user, $security);
            break;
        case 'GET':
            handleGet($endpoint, $budget, $user, $id);
            break;
        case 'PUT':
            handlePut($endpoint, $budget, $auth, $user, $id);
            break;
        case 'DELETE':
            handleDelete($endpoint, $budget, $auth, $user, $id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Budgets API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handlePost($endpoint, $budget, $auth, $user, $security) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($endpoint) {
        case 'create':
            $auth->requireCsrfToken();
            
            $data = [
                'user_id' => $user['id'],
                'category_id' => $input['category_id'] ?? null,
                'budget_amount' => $input['budget_amount'] ?? 0,
                'period' => $input['period'] ?? 'monthly',
                'start_date' => $input['start_date'] ?? date('Y-m-01'),
                'end_date' => $input['end_date'] ?? date('Y-m-t'),
                'alert_threshold' => $input['alert_threshold'] ?? 80
            ];
            
            $result = $budget->create($data);
            
            if ($result['success']) {
                http_response_code(201);
                $security->logSecurityEvent('budget_created', [
                    'budget_id' => $result['data']['id'],
                    'category_id' => $data['category_id'],
                    'amount' => $data['budget_amount']
                ]);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'create-automatic':
            $auth->requireCsrfToken();
            
            $months = max(1, min(12, intval($input['months'] ?? 6)));
            
            $result = $budget->createAutomaticBudgets($user['id'], $months);
            
            if ($result['success']) {
                http_response_code(201);
                $security->logSecurityEvent('automatic_budgets_created', [
                    'user_id' => $user['id'],
                    'count' => $result['created_count'],
                    'months_analyzed' => $months
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

function handleGet($endpoint, $budget, $user, $id) {
    switch ($endpoint) {
        case 'list':
            $period = $_GET['period'] ?? null;
            $active = !isset($_GET['active']) || $_GET['active'] !== 'false';
            
            if ($period && !in_array($period, ['weekly', 'monthly', 'quarterly', 'yearly'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid period']);
                return;
            }
            
            $budgets = $budget->getForUser($user['id'], $period, $active);
            
            echo json_encode([
                'success' => true,
                'data' => $budgets
            ]);
            break;
            
        case 'get':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Budget ID is required']);
                return;
            }
            
            $budgetData = $budget->getById($id);
            
            if ($budgetData && $budgetData['user_id'] == $user['id']) {
                echo json_encode([
                    'success' => true,
                    'data' => $budgetData
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Budget not found']);
            }
            break;
            
        case 'statistics':
            $period = $_GET['period'] ?? null;
            
            if ($period && !in_array($period, ['weekly', 'monthly', 'quarterly', 'yearly'])) {
                $period = null;
            }
            
            $stats = $budget->getUsageStatistics($user['id'], $period);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'alerts':
            $alerts = $budget->getAlerts($user['id']);
            
            echo json_encode([
                'success' => true,
                'data' => $alerts
            ]);
            break;
            
        case 'check-alert':
            $categoryId = $_GET['category_id'] ?? null;
            $amount = $_GET['amount'] ?? 0;
            $transactionDate = $_GET['transaction_date'] ?? date('Y-m-d');
            
            if (!$categoryId || !is_numeric($amount)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category ID and amount are required']);
                return;
            }
            
            $alert = $budget->checkBudgetAlert($user['id'], $categoryId, $amount, $transactionDate);
            
            echo json_encode([
                'success' => true,
                'data' => $alert
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handlePut($endpoint, $budget, $auth, $user, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Budget ID is required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($endpoint) {
        case 'update':
            $auth->requireCsrfToken();
            
            $result = $budget->update($id, $input, $user['id']);
            
            if ($result['success']) {
                $security = new Security();
                $security->logSecurityEvent('budget_updated', [
                    'budget_id' => $id,
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

function handleDelete($endpoint, $budget, $auth, $user, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Budget ID is required']);
        return;
    }
    
    switch ($endpoint) {
        case 'delete':
            $auth->requireCsrfToken();
            
            $result = $budget->delete($id, $user['id']);
            
            if ($result['success']) {
                $security = new Security();
                $security->logSecurityEvent('budget_deleted', [
                    'budget_id' => $id,
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