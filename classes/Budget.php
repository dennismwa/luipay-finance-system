<?php
/**
 * LuigiTals Wallet Management System
 * Budget Management Class
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Budget {
    
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->security = new Security();
    }
    
    /**
     * Create new budget
     */
    public function create($data) {
        try {
            // Validate required fields
            $requiredFields = ['user_id', 'category_id', 'budget_amount', 'period', 'start_date', 'end_date'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => ucfirst($field) . ' is required'];
                }
            }
            
            // Validate budget amount
            if (!is_numeric($data['budget_amount']) || $data['budget_amount'] <= 0) {
                return ['success' => false, 'message' => 'Budget amount must be a positive number'];
            }
            
            // Validate period
            if (!in_array($data['period'], ['weekly', 'monthly', 'quarterly', 'yearly'])) {
                return ['success' => false, 'message' => 'Invalid period'];
            }
            
            // Validate dates
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            
            if ($startDate >= $endDate) {
                return ['success' => false, 'message' => 'End date must be after start date'];
            }
            
            // Check if budget already exists for this category and period
            $existing = $this->db->select('budgets', 'id', 
                'user_id = :user_id AND category_id = :category_id AND period = :period AND is_active = 1 AND 
                 ((start_date <= :start_date AND end_date >= :start_date) OR 
                  (start_date <= :end_date AND end_date >= :end_date) OR 
                  (start_date >= :start_date AND end_date <= :end_date))', [
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'],
                'period' => $data['period'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date']
            ])->fetch();
            
            if ($existing) {
                return ['success' => false, 'message' => 'Budget already exists for this category and period'];
            }
            
            // Sanitize data
            $cleanData = [
                'user_id' => (int)$data['user_id'],
                'category_id' => (int)$data['category_id'],
                'budget_amount' => round($data['budget_amount'], 2),
                'period' => $this->security->sanitizeInput($data['period']),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'alert_threshold' => isset($data['alert_threshold']) ? 
                    max(0, min(100, (float)$data['alert_threshold'])) : 80.00
            ];
            
            // Verify category exists and belongs to user
            $category = $this->db->select('categories', 'id', 
                'id = :id AND user_id = :user_id AND is_active = 1', [
                'id' => $cleanData['category_id'],
                'user_id' => $cleanData['user_id']
            ])->fetch();
            
            if (!$category) {
                return ['success' => false, 'message' => 'Invalid category'];
            }
            
            // Insert budget
            $budgetId = $this->db->insert('budgets', $cleanData);
            
            if ($budgetId) {
                $budget = $this->getById($budgetId);
                return [
                    'success' => true,
                    'message' => 'Budget created successfully',
                    'data' => $budget
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create budget'];
            }
            
        } catch (Exception $e) {
            error_log('Budget creation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while creating budget'];
        }
    }
    
    /**
     * Update budget
     */
    public function update($id, $data, $userId) {
        try {
            // Check if budget exists and belongs to user
            $existing = $this->db->select('budgets', '*', 
                'id = :id AND user_id = :user_id AND is_active = 1', [
                'id' => $id,
                'user_id' => $userId
            ])->fetch();
            
            if (!$existing) {
                return ['success' => false, 'message' => 'Budget not found'];
            }
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['budget_amount', 'period', 'start_date', 'end_date', 'alert_threshold'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    switch ($field) {
                        case 'budget_amount':
                            if (!is_numeric($data[$field]) || $data[$field] <= 0) {
                                return ['success' => false, 'message' => 'Budget amount must be a positive number'];
                            }
                            $updateData[$field] = round($data[$field], 2);
                            break;
                        case 'period':
                            if (!in_array($data[$field], ['weekly', 'monthly', 'quarterly', 'yearly'])) {
                                return ['success' => false, 'message' => 'Invalid period'];
                            }
                            $updateData[$field] = $this->security->sanitizeInput($data[$field]);
                            break;
                        case 'start_date':
                        case 'end_date':
                            $updateData[$field] = $data[$field];
                            break;
                        case 'alert_threshold':
                            $updateData[$field] = max(0, min(100, (float)$data[$field]));
                            break;
                    }
                }
            }
            
            // Validate dates if both are provided
            if (isset($updateData['start_date']) && isset($updateData['end_date'])) {
                $startDate = new DateTime($updateData['start_date']);
                $endDate = new DateTime($updateData['end_date']);
                
                if ($startDate >= $endDate) {
                    return ['success' => false, 'message' => 'End date must be after start date'];
                }
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            // Update budget
            $updated = $this->db->update('budgets', $updateData, 'id = :id', ['id' => $id]);
            
            if ($updated) {
                $budget = $this->getById($id);
                return [
                    'success' => true,
                    'message' => 'Budget updated successfully',
                    'data' => $budget
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update budget'];
            }
            
        } catch (Exception $e) {
            error_log('Budget update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while updating budget'];
        }
    }
    
    /**
     * Delete budget (soft delete)
     */
    public function delete($id, $userId) {
        try {
            // Check if budget exists and belongs to user
            $exists = $this->db->exists('budgets', 
                'id = :id AND user_id = :user_id AND is_active = 1', [
                'id' => $id,
                'user_id' => $userId
            ]);
            
            if (!$exists) {
                return ['success' => false, 'message' => 'Budget not found'];
            }
            
            // Soft delete
            $deleted = $this->db->update('budgets', ['is_active' => 0], 'id = :id', ['id' => $id]);
            
            if ($deleted) {
                return ['success' => true, 'message' => 'Budget deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete budget'];
            }
            
        } catch (Exception $e) {
            error_log('Budget deletion error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while deleting budget'];
        }
    }
    
    /**
     * Get budget by ID
     */
    public function getById($id) {
        $sql = "
            SELECT b.*, c.name as category_name, c.icon as category_icon, c.color as category_color
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            WHERE b.id = :id AND b.is_active = 1
        ";
        
        return $this->db->query($sql, ['id' => $id])->fetch();
    }
    
    /**
     * Get budgets for user
     */
    public function getForUser($userId, $period = null, $active = true) {
        $where = ['b.user_id = :user_id'];
        $params = ['user_id' => $userId];
        
        if ($active) {
            $where[] = 'b.is_active = 1';
        }
        
        if ($period && in_array($period, ['weekly', 'monthly', 'quarterly', 'yearly'])) {
            $where[] = 'b.period = :period';
            $params['period'] = $period;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT b.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                   COALESCE(spent.total_spent, 0) as current_spending,
                   (b.budget_amount - COALESCE(spent.total_spent, 0)) as remaining_amount,
                   CASE 
                       WHEN b.budget_amount > 0 THEN 
                           (COALESCE(spent.total_spent, 0) / b.budget_amount * 100)
                       ELSE 0 
                   END as usage_percentage,
                   CASE 
                       WHEN COALESCE(spent.total_spent, 0) > b.budget_amount THEN 'over_budget'
                       WHEN COALESCE(spent.total_spent, 0) > (b.budget_amount * b.alert_threshold / 100) THEN 'warning'
                       ELSE 'on_track'
                   END as status
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN (
                SELECT 
                    t.category_id,
                    b2.id as budget_id,
                    SUM(t.amount) as total_spent
                FROM transactions t
                JOIN budgets b2 ON t.category_id = b2.category_id
                WHERE t.user_id = :user_id 
                    AND t.type = 'expense' 
                    AND t.is_deleted = 0
                    AND t.transaction_date BETWEEN b2.start_date AND b2.end_date
                GROUP BY t.category_id, b2.id
            ) spent ON b.id = spent.budget_id
            WHERE {$whereClause}
            ORDER BY b.created_at DESC
        ";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Get budget usage statistics
     */
    public function getUsageStatistics($userId, $period = null) {
        $budgets = $this->getForUser($userId, $period);
        
        $stats = [
            'total_budgets' => count($budgets),
            'total_allocated' => 0,
            'total_spent' => 0,
            'on_track' => 0,
            'warning' => 0,
            'over_budget' => 0,
            'categories' => []
        ];
        
        foreach ($budgets as $budget) {
            $stats['total_allocated'] += $budget['budget_amount'];
            $stats['total_spent'] += $budget['current_spending'];
            $stats[$budget['status']]++;
            
            $stats['categories'][] = [
                'name' => $budget['category_name'],
                'icon' => $budget['category_icon'],
                'budget_amount' => $budget['budget_amount'],
                'spent_amount' => $budget['current_spending'],
                'usage_percentage' => $budget['usage_percentage'],
                'status' => $budget['status']
            ];
        }
        
        $stats['total_remaining'] = $stats['total_allocated'] - $stats['total_spent'];
        $stats['overall_usage'] = $stats['total_allocated'] > 0 ? 
            ($stats['total_spent'] / $stats['total_allocated'] * 100) : 0;
        
        return $stats;
    }
    
    /**
     * Check if spending exceeds budget
     */
    public function checkBudgetAlert($userId, $categoryId, $amount, $transactionDate) {
        $sql = "
            SELECT 
                b.id,
                b.budget_amount,
                b.alert_threshold,
                c.name as category_name,
                COALESCE(SUM(t.amount), 0) as current_spending
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN transactions t ON b.category_id = t.category_id 
                AND t.user_id = b.user_id
                AND t.transaction_date BETWEEN b.start_date AND b.end_date
                AND t.type = 'expense'
                AND t.is_deleted = 0
            WHERE b.user_id = :user_id 
                AND b.category_id = :category_id 
                AND b.is_active = 1
                AND :transaction_date BETWEEN b.start_date AND b.end_date
            GROUP BY b.id
            LIMIT 1
        ";
        
        $budget = $this->db->query($sql, [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'transaction_date' => $transactionDate
        ])->fetch();
        
        if (!$budget) {
            return null;
        }
        
        $newTotal = $budget['current_spending'] + $amount;
        $budgetLimit = $budget['budget_amount'];
        $alertThreshold = ($budget['alert_threshold'] / 100) * $budgetLimit;
        
        $alert = [
            'budget_id' => $budget['id'],
            'category_name' => $budget['category_name'],
            'budget_amount' => $budgetLimit,
            'current_spending' => $budget['current_spending'],
            'new_total' => $newTotal,
            'percentage_used' => ($newTotal / $budgetLimit) * 100,
            'exceeded' => $newTotal > $budgetLimit,
            'warning' => $newTotal > $alertThreshold && $newTotal <= $budgetLimit,
            'remaining' => $budgetLimit - $newTotal
        ];
        
        return $alert;
    }
    
    /**
     * Get budget alerts for user
     */
    public function getAlerts($userId) {
        $budgets = $this->getForUser($userId);
        $alerts = [];
        
        foreach ($budgets as $budget) {
            if ($budget['status'] === 'over_budget') {
                $alerts[] = [
                    'type' => 'over_budget',
                    'severity' => 'high',
                    'category' => $budget['category_name'],
                    'message' => "Budget exceeded for {$budget['category_name']}",
                    'details' => [
                        'budget_amount' => $budget['budget_amount'],
                        'spent_amount' => $budget['current_spending'],
                        'overage' => $budget['current_spending'] - $budget['budget_amount']
                    ]
                ];
            } elseif ($budget['status'] === 'warning') {
                $alerts[] = [
                    'type' => 'budget_warning',
                    'severity' => 'medium',
                    'category' => $budget['category_name'],
                    'message' => "Approaching budget limit for {$budget['category_name']}",
                    'details' => [
                        'budget_amount' => $budget['budget_amount'],
                        'spent_amount' => $budget['current_spending'],
                        'remaining' => $budget['remaining_amount'],
                        'percentage' => $budget['usage_percentage']
                    ]
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Create automatic budgets based on spending patterns
     */
    public function createAutomaticBudgets($userId, $months = 6) {
        try {
            // Get spending patterns from last X months
            $sql = "
                SELECT 
                    t.category_id,
                    c.name as category_name,
                    AVG(monthly_spending.total) as avg_monthly_spending,
                    MAX(monthly_spending.total) as max_monthly_spending
                FROM (
                    SELECT 
                        category_id,
                        YEAR(transaction_date) as year,
                        MONTH(transaction_date) as month,
                        SUM(amount) as total
                    FROM transactions 
                    WHERE user_id = :user_id 
                        AND type = 'expense' 
                        AND is_deleted = 0
                        AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                    GROUP BY category_id, YEAR(transaction_date), MONTH(transaction_date)
                ) monthly_spending
                JOIN transactions t ON monthly_spending.category_id = t.category_id
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id
                GROUP BY t.category_id
                HAVING avg_monthly_spending > 0
                ORDER BY avg_monthly_spending DESC
            ";
            
            $patterns = $this->db->query($sql, [
                'user_id' => $userId,
                'months' => $months
            ])->fetchAll();
            
            $createdCount = 0;
            $currentDate = new DateTime();
            $startDate = new DateTime($currentDate->format('Y-m-01')); // First day of current month
            $endDate = new DateTime($startDate->format('Y-m-t')); // Last day of current month
            
            foreach ($patterns as $pattern) {
                // Check if budget already exists for this category and month
                $exists = $this->db->exists('budgets',
                    'user_id = :user_id AND category_id = :category_id AND period = "monthly" AND is_active = 1 AND start_date = :start_date', [
                    'user_id' => $userId,
                    'category_id' => $pattern['category_id'],
                    'start_date' => $startDate->format('Y-m-d')
                ]);
                
                if (!$exists) {
                    // Create budget with 120% of average spending (buffer for variations)
                    $budgetAmount = ceil($pattern['avg_monthly_spending'] * 1.2);
                    
                    $budgetData = [
                        'user_id' => $userId,
                        'category_id' => $pattern['category_id'],
                        'budget_amount' => $budgetAmount,
                        'period' => 'monthly',
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'alert_threshold' => 80.0
                    ];
                    
                    $result = $this->create($budgetData);
                    if ($result['success']) {
                        $createdCount++;
                    }
                }
            }
            
            return [
                'success' => true,
                'created_count' => $createdCount,
                'message' => "Created {$createdCount} automatic budgets based on spending patterns"
            ];
            
        } catch (Exception $e) {
            error_log('Automatic budget creation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create automatic budgets'];
        }
    }
}

?>