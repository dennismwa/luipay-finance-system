<?php
/**
 * LuigiTals Wallet Management System
 * Reports API Endpoints
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
require_once APP_ROOT . '/classes/Report.php';
require_once APP_ROOT . '/classes/Security.php';

$auth = new Auth();
$report = new Report();
$security = new Security();

// Require authentication
$auth->requireAuth();
$user = $auth->getCurrentUser();

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($endpoint, $report, $user);
            break;
        case 'POST':
            handlePost($endpoint, $report, $auth, $user);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Reports API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGet($endpoint, $report, $user) {
    switch ($endpoint) {
        case 'types':
            $types = $report->getReportTypes();
            echo json_encode([
                'success' => true,
                'data' => $types
            ]);
            break;
            
        case 'income-statement':
            $startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
            $endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
            
            if (!validateDateRange($startDate, $endDate)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid date range']);
                return;
            }
            
            $result = $report->generateIncomeStatement($user['id'], $startDate, $endDate);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;
            
        case 'expense-breakdown':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            if (!validateDateRange($startDate, $endDate)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid date range']);
                return;
            }
            
            $result = $report->generateExpenseBreakdown($user['id'], $startDate, $endDate);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;
            
        case 'budget-analysis':
            $period = $_GET['period'] ?? 'month';
            
            if (!in_array($period, ['today', 'week', 'month', 'quarter', 'year'])) {
                $period = 'month';
            }
            
            $result = $report->generateBudgetAnalysis($user['id'], $period);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;
            
        case 'monthly-summary':
            $year = intval($_GET['year'] ?? date('Y'));
            $month = intval($_GET['month'] ?? date('n'));
            
            if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid year or month']);
                return;
            }
            
            $result = $report->generateMonthlySummary($user['id'], $year, $month);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;
            
        case 'trend-analysis':
            $months = min(36, max(1, intval($_GET['months'] ?? 12)));
            
            $result = $report->generateTrendAnalysis($user['id'], $months);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;
            
        case 'quick-stats':
            // Get quick statistics for dashboard
            $currentMonth = date('Y-m');
            $lastMonth = date('Y-m', strtotime('-1 month'));
            
            // Current month stats
            $currentStats = $report->generateMonthlySummary($user['id'], date('Y'), date('n'));
            $lastStats = $report->generateMonthlySummary($user['id'], date('Y', strtotime('-1 month')), date('n', strtotime('-1 month')));
            
            $quickStats = [
                'current_month' => $currentStats['success'] ? $currentStats['data']['summary'] : null,
                'last_month' => $lastStats['success'] ? $lastStats['data']['summary'] : null,
                'growth' => []
            ];
            
            // Calculate growth rates
            if ($quickStats['current_month'] && $quickStats['last_month']) {
                $current = $quickStats['current_month'];
                $last = $quickStats['last_month'];
                
                $quickStats['growth'] = [
                    'income' => $last['income'] > 0 ? (($current['income'] - $last['income']) / $last['income'] * 100) : 0,
                    'expenses' => $last['expenses'] > 0 ? (($current['expenses'] - $last['expenses']) / $last['expenses'] * 100) : 0,
                    'net' => $last['net'] != 0 ? (($current['net'] - $last['net']) / abs($last['net']) * 100) : 0
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $quickStats
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function handlePost($endpoint, $report, $auth, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($endpoint) {
        case 'export':
            $auth->requireCsrfToken();
            
            $reportType = $input['report_type'] ?? '';
            $format = $input['format'] ?? 'csv';
            
            if (!in_array($reportType, ['income-statement', 'expense-breakdown', 'budget-analysis', 'monthly-summary', 'trend-analysis'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid report type']);
                return;
            }
            
            if (!in_array($format, ['csv', 'json'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid export format']);
                return;
            }
            
            // Generate the report first
            $reportData = null;
            switch ($reportType) {
                case 'income-statement':
                    $startDate = $input['start_date'] ?? date('Y-m-01');
                    $endDate = $input['end_date'] ?? date('Y-m-d');
                    $result = $report->generateIncomeStatement($user['id'], $startDate, $endDate);
                    break;
                    
                case 'expense-breakdown':
                    $startDate = $input['start_date'] ?? date('Y-m-01');
                    $endDate = $input['end_date'] ?? date('Y-m-d');
                    $result = $report->generateExpenseBreakdown($user['id'], $startDate, $endDate);
                    break;
                    
                case 'budget-analysis':
                    $period = $input['period'] ?? 'month';
                    $result = $report->generateBudgetAnalysis($user['id'], $period);
                    break;
                    
                case 'monthly-summary':
                    $year = $input['year'] ?? date('Y');
                    $month = $input['month'] ?? date('n');
                    $result = $report->generateMonthlySummary($user['id'], $year, $month);
                    break;
                    
                case 'trend-analysis':
                    $months = $input['months'] ?? 12;
                    $result = $report->generateTrendAnalysis($user['id'], $months);
                    break;
            }
            
            if (!$result || !$result['success']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to generate report']);
                return;
            }
            
            if ($format === 'csv') {
                $exportResult = $report->exportToCSV($result['data']);
                
                if ($exportResult['success']) {
                    echo json_encode([
                        'success' => true,
                        'download_url' => $exportResult['url'],
                        'filename' => $exportResult['filename']
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode($exportResult);
                }
            } else {
                // JSON export
                $filename = 'report_' . $reportType . '_' . date('Y-m-d_H-i-s') . '.json';
                $filepath = DatabaseConfig::getUploadPath() . $filename;
                
                if (file_put_contents($filepath, json_encode($result['data'], JSON_PRETTY_PRINT))) {
                    echo json_encode([
                        'success' => true,
                        'download_url' => 'uploads/' . $filename,
                        'filename' => $filename
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to export JSON']);
                }
            }
            break;
            
        case 'generate':
            $auth->requireCsrfToken();
            
            $reportType = $input['report_type'] ?? '';
            $parameters = $input['parameters'] ?? [];
            
            if (!in_array($reportType, ['income-statement', 'expense-breakdown', 'budget-analysis', 'monthly-summary', 'trend-analysis'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid report type']);
                return;
            }
            
            $result = null;
            switch ($reportType) {
                case 'income-statement':
                    $startDate = $parameters['start_date'] ?? date('Y-m-01');
                    $endDate = $parameters['end_date'] ?? date('Y-m-d');
                    $result = $report->generateIncomeStatement($user['id'], $startDate, $endDate);
                    break;
                    
                case 'expense-breakdown':
                    $startDate = $parameters['start_date'] ?? date('Y-m-01');
                    $endDate = $parameters['end_date'] ?? date('Y-m-d');
                    $result = $report->generateExpenseBreakdown($user['id'], $startDate, $endDate);
                    break;
                    
                case 'budget-analysis':
                    $period = $parameters['period'] ?? 'month';
                    $result = $report->generateBudgetAnalysis($user['id'], $period);
                    break;
                    
                case 'monthly-summary':
                    $year = $parameters['year'] ?? date('Y');
                    $month = $parameters['month'] ?? date('n');
                    $result = $report->generateMonthlySummary($user['id'], $year, $month);
                    break;
                    
                case 'trend-analysis':
                    $months = $parameters['months'] ?? 12;
                    $result = $report->generateTrendAnalysis($user['id'], $months);
                    break;
            }
            
            if ($result && $result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                http_response_code(400);
                echo json_encode($result ?: ['success' => false, 'message' => 'Failed to generate report']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
}

function validateDateRange($startDate, $endDate) {
    $start = DateTime::createFromFormat('Y-m-d', $startDate);
    $end = DateTime::createFromFormat('Y-m-d', $endDate);
    
    if (!$start || !$end) {
        return false;
    }
    
    // Check if start date is before end date
    if ($start > $end) {
        return false;
    }
    
    // Check if dates are not too far in the future
    $maxDate = new DateTime('+1 year');
    if ($end > $maxDate) {
        return false;
    }
    
    return true;
}

?>