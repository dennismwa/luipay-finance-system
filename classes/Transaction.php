<?php
/**
 * LuigiTals Wallet Management System
 * Transaction Management Class
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Transaction {
    
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->security = new Security();
    }
    
    /**
     * Create new transaction
     */
    public function create($data) {
        try {
            // Validate required fields
            $requiredFields = ['user_id', 'category_id', 'type', 'amount', 'description', 'transaction_date'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => ucfirst($field) . ' is required'];
                }
            }
            
            // Validate transaction type
            if (!in_array($data['type'], ['income', 'expense'])) {
                return ['success' => false, 'message' => 'Invalid transaction type'];
            }
            
            // Validate amount
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                return ['success' => false, 'message' => 'Amount must be a positive number'];
            }
            
            // Sanitize data
            $cleanData = [
                'user_id' => (int)$data['user_id'],
                'category_id' => (int)$data['category_id'],
                'type' => $this->security->sanitizeInput($data['type']),
                'amount' => round($data['amount'], 2),
                'description' => $this->security->sanitizeInput($data['description']),
                'transaction_date' => $data['transaction_date'],
                'payment_method' => $this->security->sanitizeInput($data['payment_method'] ?? 'cash'),
                'reference_number' => $this->security->sanitizeInput($data['reference_number'] ?? ''),
                'notes' => $this->security->sanitizeInput($data['notes'] ?? ''),
                'status' => $data['status'] ?? 'completed'
            ];
            
            // Validate category exists and belongs to user
            $category = $this->db->select('categories', 'id, type', 
                'id = :id AND user_id = :user_id AND is_active = 1', [
                'id' => $cleanData['category_id'],
                'user_id' => $cleanData['user_id']
            ])->fetch();
            
            if (!$category) {
                return ['success' => false, 'message' => 'Invalid category'];
            }
            
            // Check if category supports this transaction type
            if ($category['type'] !== 'both' && $category['type'] !== $cleanData['type']) {
                return ['success' => false, 'message' => 'Category does not support this transaction type'];
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Insert transaction
            $transactionId = $this->db->insert('transactions', $cleanData);
            
            if (!$transactionId) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Failed to create transaction'];
            }
            
            // Check budget limits for expenses
            if ($cleanData['type'] === 'expense') {
                $budgetCheck = $this->checkBudgetLimits($cleanData['user_id'], $cleanData['category_id'], 
                                                      $cleanData['amount'], $cleanData['transaction_date']);
                
                if ($budgetCheck['exceeded']) {
                    // Create notification for budget exceeded
                    $this->createBudgetNotification($cleanData['user_id'], $cleanData['category_id'], 
                                                  $transactionId, $budgetCheck);
                }
            }
            
            $this->db->commit();
            
            // Get created transaction with category info
            $transaction = $this->getById($transactionId);
            
            return [
                'success' => true, 
                'message' => 'Transaction created successfully',
                'data' => $transaction
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Transaction creation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while creating transaction'];
        }
    }
    
    /**
     * Update transaction
     */
    public function update($id, $data, $userId) {
        try {
            // Check if transaction exists and belongs to user
            $existing = $this->db->select('transactions', '*', 
                'id = :id AND user_id = :user_id AND is_deleted = 0', [
                'id' => $id,
                'user_id' => $userId
            ])->fetch();
            
            if (!$existing) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['category_id', 'type', 'amount', 'description', 'transaction_date', 
                            'payment_method', 'reference_number', 'notes', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $this->security->sanitizeInput($data[$field]);
                }
            }
            
            // Validate amount if provided
            if (isset($updateData['amount'])) {
                if (!is_numeric($updateData['amount']) || $updateData['amount'] <= 0) {
                    return ['success' => false, 'message' => 'Amount must be a positive number'];
                }
                $updateData['amount'] = round($updateData['amount'], 2);
            }
            
            // Validate category if provided
            if (isset($updateData['category_id'])) {
                $category = $this->db->select('categories', 'id', 
                    'id = :id AND user_id = :user_id AND is_active = 1', [
                    'id' => $updateData['category_id'],
                    'user_id' => $userId
                ])->fetch();
                
                if (!$category) {
                    return ['success' => false, 'message' => 'Invalid category'];
                }
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            // Update transaction
            $updated = $this->db->update('transactions', $updateData, 'id = :id', ['id' => $id]);
            
            if ($updated) {
                $transaction = $this->getById($id);
                return [
                    'success' => true, 
                    'message' => 'Transaction updated successfully',
                    'data' => $transaction
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update transaction'];
            }
            
        } catch (Exception $e) {
            error_log('Transaction update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while updating transaction'];
        }
    }
    
    /**
     * Delete transaction (soft delete)
     */
    public function delete($id, $userId) {
        try {
            // Check if transaction exists and belongs to user
            $exists = $this->db->exists('transactions', 
                'id = :id AND user_id = :user_id AND is_deleted = 0', [
                'id' => $id,
                'user_id' => $userId
            ]);
            
            if (!$exists) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }
            
            // Soft delete
            $deleted = $this->db->softDelete('transactions', 'id = :id', ['id' => $id]);
            
            if ($deleted) {
                return ['success' => true, 'message' => 'Transaction deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete transaction'];
            }
            
        } catch (Exception $e) {
            error_log('Transaction deletion error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while deleting transaction'];
        }
    }
    
    /**
     * Get transaction by ID
     */
    public function getById($id) {
        $sql = "
            SELECT t.*, c.name as category_name, c.icon as category_icon, c.color as category_color
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.id = :id AND t.is_deleted = 0
        ";
        
        return $this->db->query($sql, ['id' => $id])->fetch();
    }
    
    /**
     * Get transactions for user with filters
     */
    public function getForUser($userId, $filters = [], $page = 1, $perPage = 25) {
        $where = ['t.user_id = :user_id', 't.is_deleted = 0'];
        $params = ['user_id' => $userId];
        
        // Apply filters
        if (!empty($filters['type'])) {
            $where[] = 't.type = :type';
            $params['type'] = $filters['type'];
        }
        
        if (!empty($filters['category_id'])) {
            $where[] = 't.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 't.transaction_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 't.transaction_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(t.description LIKE :search OR t.notes LIKE :search OR t.reference_number LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['amount_min'])) {
            $where[] = 't.amount >= :amount_min';
            $params['amount_min'] = $filters['amount_min'];
        }
        
        if (!empty($filters['amount_max'])) {
            $where[] = 't.amount <= :amount_max';
            $params['amount_max'] = $filters['amount_max'];
        }
        
        $whereClause = implode(' AND ', $where);
        $orderBy = $filters['order_by'] ?? 't.transaction_date DESC, t.created_at DESC';
        
        // Count total records
        $countSql = "
            SELECT COUNT(*)
            FROM transactions t
            WHERE {$whereClause}
        ";
        
        $totalCount = $this->db->query($countSql, $params)->fetchColumn();
        $totalPages = ceil($totalCount / $perPage);
        
        // Get paginated results
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT t.*, c.name as category_name, c.icon as category_icon, c.color as category_color
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$offset}, {$perPage}
        ";
        
        $transactions = $this->db->query($sql, $params)->fetchAll();
        
        return [
            'data' => $transactions,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }
    
    /**
     * Get transaction statistics
     */
    public function getStatistics($userId, $period = 'month') {
        $dateCondition = $this->getPeriodCondition($period);
        
        $sql = "
            SELECT 
                t.type,
                COUNT(*) as count,
                SUM(t.amount) as total_amount,
                AVG(t.amount) as avg_amount,
                c.name as category_name,
                c.icon as category_icon
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = :user_id 
                AND t.is_deleted = 0 
                AND {$dateCondition}
            GROUP BY t.type, c.id
            ORDER BY total_amount DESC
        ";
        
        $stats = $this->db->query($sql, ['user_id' => $userId])->fetchAll();
        
        // Calculate totals
        $totalIncome = 0;
        $totalExpenses = 0;
        $incomeCount = 0;
        $expenseCount = 0;
        
        foreach ($stats as $stat) {
            if ($stat['type'] === 'income') {
                $totalIncome += $stat['total_amount'];
                $incomeCount += $stat['count'];
            } else {
                $totalExpenses += $stat['total_amount'];
                $expenseCount += $stat['count'];
            }
        }
        
        return [
            'by_category' => $stats,
            'totals' => [
                'income' => $totalIncome,
                'expenses' => $totalExpenses,
                'balance' => $totalIncome - $totalExpenses,
                'income_count' => $incomeCount,
                'expense_count' => $expenseCount,
                'total_count' => $incomeCount + $expenseCount
            ]
        ];
    }
    
    /**
     * Get monthly trends
     */
    public function getMonthlyTrends($userId, $months = 12) {
        $sql = "
            SELECT 
                YEAR(t.transaction_date) as year,
                MONTH(t.transaction_date) as month,
                t.type,
                SUM(t.amount) as total_amount,
                COUNT(*) as count
            FROM transactions t
            WHERE t.user_id = :user_id 
                AND t.is_deleted = 0 
                AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY YEAR(t.transaction_date), MONTH(t.transaction_date), t.type
            ORDER BY year DESC, month DESC
        ";
        
        return $this->db->query($sql, [
            'user_id' => $userId,
            'months' => $months
        ])->fetchAll();
    }
    
    /**
     * Check budget limits
     */
    private function checkBudgetLimits($userId, $categoryId, $amount, $transactionDate) {
        $sql = "
            SELECT 
                b.budget_amount,
                b.alert_threshold,
                COALESCE(SUM(t.amount), 0) as current_spending
            FROM budgets b
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
            return ['exceeded' => false];
        }
        
        $newTotal = $budget['current_spending'] + $amount;
        $budgetLimit = $budget['budget_amount'];
        $alertThreshold = ($budget['alert_threshold'] / 100) * $budgetLimit;
        
        return [
            'exceeded' => $newTotal > $budgetLimit,
            'near_limit' => $newTotal > $alertThreshold,
            'budget_amount' => $budgetLimit,
            'current_spending' => $budget['current_spending'],
            'new_total' => $newTotal,
            'percentage_used' => ($newTotal / $budgetLimit) * 100
        ];
    }
    
    /**
     * Create budget notification
     */
    private function createBudgetNotification($userId, $categoryId, $transactionId, $budgetInfo) {
        $message = $budgetInfo['exceeded'] 
            ? 'Budget exceeded! You have spent ' . DatabaseConfig::formatCurrency($budgetInfo['new_total']) . 
              ' out of ' . DatabaseConfig::formatCurrency($budgetInfo['budget_amount'])
            : 'Budget warning! You are approaching your budget limit.';
        
        $priority = $budgetInfo['exceeded'] ? 'high' : 'medium';
        
        $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => 'budget_alert',
            'title' => $budgetInfo['exceeded'] ? 'Budget Exceeded' : 'Budget Warning',
            'message' => $message,
            'related_id' => $transactionId,
            'related_type' => 'transaction',
            'priority' => $priority
        ]);
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