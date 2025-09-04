<?php
/**
 * LuigiTals Wallet Management System
 * Transactions API Endpoints
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
require_once APP_ROOT . '/classes/Transaction.php';
require_once APP_ROOT . '/classes/Security.php';

$auth = new Auth();
$transaction = new Transaction();
$security = new Security();

// Require authentication for all endpoints
$auth->requireAuth();
$user = $auth->getCurrentUser();

// Rate limiting
$clientId = $security->getClientIp() . '_transactions';
if (!$security->checkRateLimit($clientId, 30, 300)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}

$security->logRateLimitAttempt($clientId);

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'POST':
            handlePost($endpoint, $transaction, $auth, $user, $security);
            break;
        case 'GET':
            handleGet($endpoint, $transaction, $user, $id);
            break;
        case 'PUT':
            handlePut($endpoint, $transaction, $auth, $user, $id, $security);
            break;
        case 'DELETE':
            handleDelete($endpoint, $transaction, $auth, $user, $id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Transactions API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handlePost($endpoint, $transaction, $auth, $user, $security) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($endpoint) {
        case 'create':
            $auth->requireCsrfToken();
            
            $data = [
                'user_id' => $user['id'],
                'category_id' => $input['category_id'] ?? null,
                'type' => $input['type'] ?? '',
                'amount' => $input['amount'] ?? 0,
                'description' => $input['description'] ?? '',
                'transaction_date' => $input['transaction_date'] ?? date('Y-m-d'),
                'payment_method' => $input['payment_method'] ?? 'cash',
                'reference_number' => $input['reference_number'] ?? '',
                'notes' => $input['notes'] ?? ''
            ];
            
            $result = $transaction->create($data);
            
            if ($result['success']) {
                http_response_code(201);
                $security->logSecurityEvent('transaction_created', [
                    'transaction_id' => $result['data']['id'],
                    'type' => $data['type'],
                    'amount' => $data['amount']
                ]);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'bulk-create':
            $auth->requireCsrfToken();
            
            $transactions = $input['transactions'] ?? [];
            if (empty($transactions) || !is_array($transactions)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Transactions array is required']);
                return;
            }
            
            if (count($transactions) > 50) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Maximum 50 transactions allowed per bulk operation']);
                return;
            }
            
            $results = [];
            $successCount = 0;
            $errors = [];
            
            foreach ($transactions as $index => $transactionData) {
                $transactionData['user_id'] = $user['id'];
                $result = $transaction->create($transactionData);
                
                if ($result['success']) {
                    $successCount++;
                    $results[] = $result['data'];
                } else {
                    $errors[] = "Transaction {$index}: " . $result['message'];
                }
            }
            
            if ($successCount > 0) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => "Created {$successCount} transactions",
                    'data' => $results,
                    'errors' => $errors
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create any transactions',
                    'errors' => $errors
                ]);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handleGet($endpoint, $transaction, $user, $id) {
    switch ($endpoint) {
        case 'list':
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = min(100, max(1, intval($_GET['per_page'] ?? 25)));
            
            $filters = [
                'type' => $_GET['type'] ?? '',
                'category_id' => $_GET['category_id'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'search' => $_GET['search'] ?? '',
                'amount_min' => $_GET['amount_min'] ?? '',
                'amount_max' => $_GET['amount_max'] ?? '',
                'order_by' => $_GET['order_by'] ?? 't.transaction_date DESC'
            ];
            
            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== '';
            });
            
            $result = $transaction->getForUser($user['id'], $filters, $page, $perPage);
            
            echo json_encode([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ]);
            break;
            
        case 'get':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
                return;
            }
            
            $transactionData = $transaction->getById($id);
            
            if ($transactionData && $transactionData['user_id'] == $user['id']) {
                echo json_encode([
                    'success' => true,
                    'data' => $transactionData
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            }
            break;
            
        case 'statistics':
            $period = $_GET['period'] ?? 'month';
            if (!in_array($period, ['today', 'week', 'month', 'quarter', 'year'])) {
                $period = 'month';
            }
            
            $stats = $transaction->getStatistics($user['id'], $period);
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'period' => $period
            ]);
            break;
            
        case 'trends':
            $months = min(36, max(1, intval($_GET['months'] ?? 12)));
            $trends = $transaction->getMonthlyTrends($user['id'], $months);
            
            echo json_encode([
                'success' => true,
                'data' => $trends,
                'months' => $months
            ]);
            break;
            
        case 'recent':
            $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
            
            $result = $transaction->getForUser($user['id'], [], 1, $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $result['data']
            ]);
            break;
            
        case 'summary':
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
            $dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today
            
            $filters = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ];
            
            $allTransactions = $transaction->getForUser($user['id'], $filters, 1, 1000);
            
            $summary = [
                'total_income' => 0,
                'total_expenses' => 0,
                'transaction_count' => count($allTransactions['data']),
                'by_category' => [],
                'by_payment_method' => []
            ];
            
            foreach ($allTransactions['data'] as $t) {
                if ($t['type'] === 'income') {
                    $summary['total_income'] += $t['amount'];
                } else {
                    $summary['total_expenses'] += $t['amount'];
                }
                
                // Group by category
                $categoryKey = $t['category_name'];
                if (!isset($summary['by_category'][$categoryKey])) {
                    $summary['by_category'][$categoryKey] = [
                        'name' => $t['category_name'],
                        'icon' => $t['category_icon'],
                        'color' => $t['category_color'],
                        'income' => 0,
                        'expenses' => 0,
                        'count' => 0
                    ];
                }
                
                $summary['by_category'][$categoryKey][$t['type'] === 'income' ? 'income' : 'expenses'] += $t['amount'];
                $summary['by_category'][$categoryKey]['count']++;
                
                // Group by payment method
                $paymentMethod = $t['payment_method'];
                if (!isset($summary['by_payment_method'][$paymentMethod])) {
                    $summary['by_payment_method'][$paymentMethod] = [
                        'method' => $paymentMethod,
                        'amount' => 0,
                        'count' => 0
                    ];
                }
                
                $summary['by_payment_method'][$paymentMethod]['amount'] += $t['amount'];
                $summary['by_payment_method'][$paymentMethod]['count']++;
            }
            
            $summary['balance'] = $summary['total_income'] - $summary['total_expenses'];
            $summary['by_category'] = array_values($summary['by_category']);
            $summary['by_payment_method'] = array_values($summary['by_payment_method']);
            
            echo json_encode([
                'success' => true,
                'data' => $summary,
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ]
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handlePut($endpoint, $transaction, $auth, $user, $id, $security) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($endpoint) {
        case 'update':
            $auth->requireCsrfToken();
            
            $result = $transaction->update($id, $input, $user['id']);
            
            if ($result['success']) {
                $security->logSecurityEvent('transaction_updated', [
                    'transaction_id' => $id,
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

function handleDelete($endpoint, $transaction, $auth, $user, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
        return;
    }
    
    switch ($endpoint) {
        case 'delete':
            $auth->requireCsrfToken();
            
            $result = $transaction->delete($id, $user['id']);
            
            if ($result['success']) {
                $security = new Security();
                $security->logSecurityEvent('transaction_deleted', [
                    'transaction_id' => $id,
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