<?php
/**
 * LuigiTals Wallet Management System
 * Report Management Class
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Report {
    
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->security = new Security();
    }
    
    /**
     * Generate income statement report
     */
    public function generateIncomeStatement($userId, $startDate, $endDate) {
        try {
            $sql = "
                SELECT 
                    c.name as category_name,
                    c.icon as category_icon,
                    t.type,
                    SUM(t.amount) as total_amount,
                    COUNT(t.id) as transaction_count
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id 
                    AND t.is_deleted = 0
                    AND t.transaction_date BETWEEN :start_date AND :end_date
                GROUP BY c.id, t.type
                ORDER BY t.type DESC, total_amount DESC
            ";
            
            $transactions = $this->db->query($sql, [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])->fetchAll();
            
            $report = [
                'title' => 'Income Statement',
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'income' => [],
                'expenses' => [],
                'summary' => [
                    'total_income' => 0,
                    'total_expenses' => 0,
                    'net_income' => 0
                ]
            ];
            
            foreach ($transactions as $transaction) {
                if ($transaction['type'] === 'income') {
                    $report['income'][] = $transaction;
                    $report['summary']['total_income'] += $transaction['total_amount'];
                } else {
                    $report['expenses'][] = $transaction;
                    $report['summary']['total_expenses'] += $transaction['total_amount'];
                }
            }
            
            $report['summary']['net_income'] = $report['summary']['total_income'] - $report['summary']['total_expenses'];
            
            return ['success' => true, 'data' => $report];
            
        } catch (Exception $e) {
            error_log('Income statement generation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate income statement'];
        }
    }
    
    /**
     * Generate expense breakdown report
     */
    public function generateExpenseBreakdown($userId, $startDate, $endDate) {
        try {
            $sql = "
                SELECT 
                    c.name as category_name,
                    c.icon as category_icon,
                    c.color as category_color,
                    SUM(t.amount) as total_amount,
                    COUNT(t.id) as transaction_count,
                    AVG(t.amount) as avg_amount,
                    MIN(t.amount) as min_amount,
                    MAX(t.amount) as max_amount,
                    (SUM(t.amount) / (
                        SELECT SUM(amount) 
                        FROM transactions 
                        WHERE user_id = :user_id 
                        AND type = 'expense' 
                        AND is_deleted = 0
                        AND transaction_date BETWEEN :start_date AND :end_date
                    ) * 100) as percentage
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id 
                    AND t.type = 'expense'
                    AND t.is_deleted = 0
                    AND t.transaction_date BETWEEN :start_date AND :end_date
                GROUP BY c.id
                ORDER BY total_amount DESC
            ";
            
            $expenses = $this->db->query($sql, [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])->fetchAll();
            
            // Get total expenses for summary
            $totalExpenses = array_sum(array_column($expenses, 'total_amount'));
            
            $report = [
                'title' => 'Expense Breakdown',
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'categories' => $expenses,
                'summary' => [
                    'total_expenses' => $totalExpenses,
                    'category_count' => count($expenses),
                    'highest_category' => $expenses[0] ?? null,
                    'avg_per_category' => count($expenses) > 0 ? $totalExpenses / count($expenses) : 0
                ]
            ];
            
            return ['success' => true, 'data' => $report];
            
        } catch (Exception $e) {
            error_log('Expense breakdown generation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate expense breakdown'];
        }
    }
    
    /**
     * Generate budget analysis report
     */
    public function generateBudgetAnalysis($userId, $period = 'month') {
        try {
            $dateCondition = $this->getPeriodCondition($period);
            
            $sql = "
                SELECT 
                    c.name as category_name,
                    c.icon as category_icon,
                    c.color as category_color,
                    b.budget_amount,
                    b.period,
                    b.alert_threshold,
                    COALESCE(SUM(t.amount), 0) as actual_spending,
                    (b.budget_amount - COALESCE(SUM(t.amount), 0)) as remaining_budget,
                    CASE 
                        WHEN b.budget_amount > 0 THEN 
                            (COALESCE(SUM(t.amount), 0) / b.budget_amount * 100)
                        ELSE 0 
                    END as usage_percentage,
                    CASE 
                        WHEN COALESCE(SUM(t.amount), 0) > b.budget_amount THEN 'over_budget'
                        WHEN COALESCE(SUM(t.amount), 0) > (b.budget_amount * b.alert_threshold / 100) THEN 'warning'
                        ELSE 'on_track'
                    END as status
                FROM budgets b
                JOIN categories c ON b.category_id = c.id
                LEFT JOIN transactions t ON b.category_id = t.category_id 
                    AND t.user_id = b.user_id
                    AND t.type = 'expense' 
                    AND t.is_deleted = 0 
                    AND {$dateCondition}
                WHERE b.user_id = :user_id AND b.is_active = 1
                GROUP BY b.id
                ORDER BY usage_percentage DESC
            ";
            
            $budgets = $this->db->query($sql, ['user_id' => $userId])->fetchAll();
            
            $summary = [
                'total_budgets' => count($budgets),
                'over_budget' => 0,
                'warning' => 0,
                'on_track' => 0,
                'total_allocated' => 0,
                'total_spent' => 0
            ];
            
            foreach ($budgets as $budget) {
                $summary['total_allocated'] += $budget['budget_amount'];
                $summary['total_spent'] += $budget['actual_spending'];
                $summary[$budget['status']]++;
            }
            
            $report = [
                'title' => 'Budget Analysis',
                'period' => $period,
                'budgets' => $budgets,
                'summary' => $summary
            ];
            
            return ['success' => true, 'data' => $report];
            
        } catch (Exception $e) {
            error_log('Budget analysis generation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate budget analysis'];
        }
    }
    
    /**
     * Generate monthly summary report
     */
    public function generateMonthlySummary($userId, $year, $month) {
        try {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Get monthly statistics
            $sql = "
                SELECT 
                    t.type,
                    COUNT(*) as transaction_count,
                    SUM(t.amount) as total_amount,
                    AVG(t.amount) as avg_amount
                FROM transactions t
                WHERE t.user_id = :user_id 
                    AND t.is_deleted = 0
                    AND t.transaction_date BETWEEN :start_date AND :end_date
                GROUP BY t.type
            ";
            
            $statistics = $this->db->query($sql, [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])->fetchAll();
            
            // Get daily breakdown
            $dailySql = "
                SELECT 
                    DATE(t.transaction_date) as date,
                    t.type,
                    SUM(t.amount) as daily_amount,
                    COUNT(*) as daily_count
                FROM transactions t
                WHERE t.user_id = :user_id 
                    AND t.is_deleted = 0
                    AND t.transaction_date BETWEEN :start_date AND :end_date
                GROUP BY DATE(t.transaction_date), t.type
                ORDER BY date ASC
            ";
            
            $dailyData = $this->db->query($dailySql, [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])->fetchAll();
            
            // Get top categories
            $categorySql = "
                SELECT 
                    c.name as category_name,
                    c.icon as category_icon,
                    t.type,
                    SUM(t.amount) as total_amount,
                    COUNT(*) as transaction_count
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id 
                    AND t.is_deleted = 0
                    AND t.transaction_date BETWEEN :start_date AND :end_date
                GROUP BY c.id, t.type
                ORDER BY total_amount DESC
                LIMIT 10
            ";
            
            $topCategories = $this->db->query($categorySql, [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])->fetchAll();
            
            // Process statistics
            $summary = [
                'income' => 0,
                'expenses' => 0,
                'net' => 0,
                'transaction_count' => 0,
                'avg_transaction' => 0
            ];
            
            foreach ($statistics as $stat) {
                if ($stat['type'] === 'income') {
                    $summary['income'] = $stat['total_amount'];
                } else {
                    $summary['expenses'] = $stat['total_amount'];
                }
                $summary['transaction_count'] += $stat['transaction_count'];
            }
            
            $summary['net'] = $summary['income'] - $summary['expenses'];
            $summary['avg_transaction'] = $summary['transaction_count'] > 0 ? 
                ($summary['income'] + $summary['expenses']) / $summary['transaction_count'] : 0;
            
            $report = [
                'title' => 'Monthly Summary',
                'period' => [
                    'year' => $year,
                    'month' => $month,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'summary' => $summary,
                'daily_breakdown' => $dailyData,
                'top_categories' => $topCategories
            ];
            
            return ['success' => true, 'data' => $report];
            
        } catch (Exception $e) {
            error_log('Monthly summary generation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate monthly summary'];
        }
    }
    
    /**
     * Generate trend analysis report
     */
    public function generateTrendAnalysis($userId, $months = 12) {
        try {
            $sql = "
                SELECT 
                    YEAR(t.transaction_date) as year,
                    MONTH(t.transaction_date) as month,
                    t.type,
                    SUM(t.amount) as total_amount,
                    COUNT(*) as transaction_count
                FROM transactions t
                WHERE t.user_id = :user_id 
                    AND t.is_deleted = 0 
                    AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY YEAR(t.transaction_date), MONTH(t.transaction_date), t.type
                ORDER BY year DESC, month DESC
            ";
            
            $trends = $this->db->query($sql, [
                'user_id' => $userId,
                'months' => $months
            ])->fetchAll();
            
            // Process trend data
            $monthlyData = [];
            $summary = [
                'avg_monthly_income' => 0,
                'avg_monthly_expenses' => 0,
                'growth_rate_income' => 0,
                'growth_rate_expenses' => 0,
                'best_month' => null,
                'worst_month' => null
            ];
            
            foreach ($trends as $trend) {
                $monthKey = $trend['year'] . '-' . sprintf('%02d', $trend['month']);
                
                if (!isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = [
                        'year' => $trend['year'],
                        'month' => $trend['month'],
                        'month_name' => date('F Y', mktime(0, 0, 0, $trend['month'], 1, $trend['year'])),
                        'income' => 0,
                        'expenses' => 0,
                        'net' => 0
                    ];
                }
                
                $monthlyData[$monthKey][$trend['type'] === 'income' ? 'income' : 'expenses'] = $trend['total_amount'];
            }
            
            // Calculate net for each month and find best/worst
            $netValues = [];
            foreach ($monthlyData as $key => &$data) {
                $data['net'] = $data['income'] - $data['expenses'];
                $netValues[$key] = $data['net'];
            }
            
            if (!empty($netValues)) {
                $bestMonthKey = array_search(max($netValues), $netValues);
                $worstMonthKey = array_search(min($netValues), $netValues);
                
                $summary['best_month'] = $monthlyData[$bestMonthKey] ?? null;
                $summary['worst_month'] = $monthlyData[$worstMonthKey] ?? null;
            }
            
            $report = [
                'title' => 'Trend Analysis',
                'period' => $months . ' months',
                'monthly_data' => array_values($monthlyData),
                'summary' => $summary
            ];
            
            return ['success' => true, 'data' => $report];
            
        } catch (Exception $e) {
            error_log('Trend analysis generation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate trend analysis'];
        }
    }
    
    /**
     * Export report to CSV
     */
    public function exportToCSV($reportData, $filename = null) {
        try {
            if (!$filename) {
                $filename = 'report_' . date('Y-m-d_H-i-s') . '.csv';
            }
            
            $filepath = DatabaseConfig::getUploadPath() . $filename;
            
            $handle = fopen($filepath, 'w');
            
            // Write header
            fputcsv($handle, ['Report: ' . $reportData['title']]);
            fputcsv($handle, ['Generated: ' . date('Y-m-d H:i:s')]);
            fputcsv($handle, []); // Empty row
            
            // Write period info if available
            if (isset($reportData['period'])) {
                if (is_array($reportData['period'])) {
                    foreach ($reportData['period'] as $key => $value) {
                        fputcsv($handle, [ucfirst($key), $value]);
                    }
                } else {
                    fputcsv($handle, ['Period', $reportData['period']]);
                }
                fputcsv($handle, []); // Empty row
            }
            
            // Write summary if available
            if (isset($reportData['summary'])) {
                fputcsv($handle, ['SUMMARY']);
                foreach ($reportData['summary'] as $key => $value) {
                    fputcsv($handle, [ucwords(str_replace('_', ' ', $key)), $value]);
                }
                fputcsv($handle, []); // Empty row
            }
            
            // Write main data based on report type
            $this->writeReportDataToCSV($handle, $reportData);
            
            fclose($handle);
            
            return [
                'success' => true,
                'filepath' => $filepath,
                'filename' => $filename,
                'url' => 'uploads/' . $filename
            ];
            
        } catch (Exception $e) {
            error_log('CSV export error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to export CSV'];
        }
    }
    
    /**
     * Write specific report data to CSV
     */
    private function writeReportDataToCSV($handle, $reportData) {
        switch ($reportData['title']) {
            case 'Income Statement':
                if (!empty($reportData['income'])) {
                    fputcsv($handle, ['INCOME']);
                    fputcsv($handle, ['Category', 'Amount', 'Transactions']);
                    foreach ($reportData['income'] as $item) {
                        fputcsv($handle, [$item['category_name'], $item['total_amount'], $item['transaction_count']]);
                    }
                    fputcsv($handle, []);
                }
                
                if (!empty($reportData['expenses'])) {
                    fputcsv($handle, ['EXPENSES']);
                    fputcsv($handle, ['Category', 'Amount', 'Transactions']);
                    foreach ($reportData['expenses'] as $item) {
                        fputcsv($handle, [$item['category_name'], $item['total_amount'], $item['transaction_count']]);
                    }
                }
                break;
                
            case 'Expense Breakdown':
                if (!empty($reportData['categories'])) {
                    fputcsv($handle, ['EXPENSE CATEGORIES']);
                    fputcsv($handle, ['Category', 'Total Amount', 'Transactions', 'Percentage', 'Average', 'Min', 'Max']);
                    foreach ($reportData['categories'] as $category) {
                        fputcsv($handle, [
                            $category['category_name'],
                            $category['total_amount'],
                            $category['transaction_count'],
                            round($category['percentage'], 2) . '%',
                            $category['avg_amount'],
                            $category['min_amount'],
                            $category['max_amount']
                        ]);
                    }
                }
                break;
                
            case 'Monthly Summary':
                if (!empty($reportData['top_categories'])) {
                    fputcsv($handle, ['TOP CATEGORIES']);
                    fputcsv($handle, ['Category', 'Type', 'Amount', 'Transactions']);
                    foreach ($reportData['top_categories'] as $category) {
                        fputcsv($handle, [
                            $category['category_name'],
                            ucfirst($category['type']),
                            $category['total_amount'],
                            $category['transaction_count']
                        ]);
                    }
                }
                break;
        }
    }
    
    /**
     * Get available report types
     */
    public function getReportTypes() {
        return [
            'income_statement' => [
                'name' => 'Income Statement',
                'description' => 'Compare income vs expenses over a period',
                'requires_date_range' => true
            ],
            'expense_breakdown' => [
                'name' => 'Expense Breakdown',
                'description' => 'Detailed analysis of spending by category',
                'requires_date_range' => true
            ],
            'budget_analysis' => [
                'name' => 'Budget Analysis',
                'description' => 'Compare actual spending vs budgets',
                'requires_date_range' => false
            ],
            'monthly_summary' => [
                'name' => 'Monthly Summary',
                'description' => 'Complete overview of a specific month',
                'requires_date_range' => false
            ],
            'trend_analysis' => [
                'name' => 'Trend Analysis',
                'description' => 'Track financial trends over time',
                'requires_date_range' => false
            ]
        ];
    }
    
    /**
     * Get period condition for SQL
     */
    private function getPeriodCondition($period) {
        switch ($period) {
            case 'today':
                return 't.transaction_date = CURDATE()';
            case 'week':
                return 't.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)';
            case 'month':
                return 't.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
            case 'quarter':
                return 't.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)';
            case 'year':
                return 't.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
            default:
                return 't.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
        }
    }
}

?>