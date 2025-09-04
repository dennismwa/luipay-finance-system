<?php
/**
 * LuigiTals Wallet Management System
 * Category Management Class
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Category {
    
    private $db;
    private $security;
    
    
    /**
     * Create new category
     */
    public function create($data) {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['user_id'])) {
                return ['success' => false, 'message' => 'Name and user ID are required'];
            }
            
            // Check if category name already exists for user
            $exists = $this->db->exists('categories', 
                'name = :name AND user_id = :user_id AND is_active = 1', [
                'name' => $data['name'],
                'user_id' => $data['user_id']
            ]);
            
            if ($exists) {
                return ['success' => false, 'message' => 'Category name already exists'];
            }
            
            // Prepare category data
            $categoryData = [
                'user_id' => (int)$data['user_id'],
                'name' => $this->security->sanitizeInput($data['name']),
                'icon' => $this->security->sanitizeInput($data['icon'] ?? '📁'),
                'color' => $this->validateColor($data['color'] ?? '#3B82F6'),
                'budget_limit' => isset($data['budget_limit']) ? round($data['budget_limit'], 2) : 0.00,
                'type' => in_array($data['type'] ?? 'expense', ['income', 'expense', 'both']) ? $data['type'] : 'expense',
                'sort_order' => (int)($data['sort_order'] ?? $this->getNextSortOrder($data['user_id']))
            ];
            
            $categoryId = $this->db->insert('categories', $categoryData);
            
            if ($categoryId) {
                $category = $this->getById($categoryId);
                return [
                    'success' => true,
                    'message' => 'Category created successfully',
                    'data' => $category
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create category'];
            }
            
        } catch (Exception $e) {
            error_log('Category creation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while creating category'];
        }
    }
    
    /**
     * Update category
     */
    public function update($id, $data, $userId) {
        try {
            // Check if category exists and belongs to user
            $existing = $this->db->select('categories', '*', 
                'id = :id AND user_id = :user_id AND is_active = 1', [
                'id' => $id,
                'user_id' => $userId
            ])->fetch();
            
            if (!$existing) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['name', 'icon', 'color', 'budget_limit', 'type', 'sort_order'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    switch ($field) {
                        case 'name':
                            // Check if new name already exists for user (excluding current category)
                            if ($data[$field] !== $existing[$field]) {
                                $nameExists = $this->db->exists('categories', 
                                    'name = :name AND user_id = :user_id AND id != :id AND is_active = 1', [
                                    'name' => $data[$field],
                                    'user_id' => $userId,
                                    'id' => $id
                                ]);
                                
                                if ($nameExists) {
                                    return ['success' => false, 'message' => 'Category name already exists'];
                                }
                            }
                            $updateData[$field] = $this->security->sanitizeInput($data[$field]);
                            break;
                        case 'icon':
                            $updateData[$field] = $this->security->sanitizeInput($data[$field]);
                            break;
                        case 'color':
                            $updateData[$field] = $this->validateColor($data[$field]);
                            break;
                        case 'budget_limit':
                            $updateData[$field] = round($data[$field], 2);
                            break;
                        case 'type':
                            if (in_array($data[$field], ['income', 'expense', 'both'])) {
                                $updateData[$field] = $data[$field];
                            }
                            break;
                        case 'sort_order':
                            $updateData[$field] = (int)$data[$field];
                            break;
                    }
                }
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $updated = $this->db->update('categories', $updateData, 'id = :id', ['id' => $id]);
            
            if ($updated) {
                $category = $this->getById($id);
                return [
                    'success' => true,
                    'message' => 'Category updated successfully',
                    'data' => $category
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update category'];
            }
            
        } catch (Exception $e) {
            error_log('Category update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while updating category'];
        }
    }
    
    /**
     * Delete category (soft delete)
     */
    public function delete($id, $userId) {
        try {
            // Check if category exists and belongs to user
            $exists = $this->db->exists('categories', 
                'id = :id AND user_id = :user_id AND is_active = 1', [
                'id' => $id,
                'user_id' => $userId
            ]);
            
            if (!$exists) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            // Check if category has transactions
            $hasTransactions = $this->db->exists('transactions', 
                'category_id = :category_id AND is_deleted = 0', [
                'category_id' => $id
            ]);
            
            if ($hasTransactions) {
                return ['success' => false, 'message' => 'Cannot delete category with existing transactions'];
            }
            
            // Soft delete
            $deleted = $this->db->update('categories', ['is_active' => 0], 'id = :id', ['id' => $id]);
            
            if ($deleted) {
                return ['success' => true, 'message' => 'Category deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete category'];
            }
            
        } catch (Exception $e) {
            error_log('Category deletion error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while deleting category'];
        }
    }
    
    /**
     * Get category by ID
     */
    public function getById($id) {
        return $this->db->select('categories', '*', 'id = :id AND is_active = 1', ['id' => $id])->fetch();
    }
    
    /**
     * Get all categories for user
     */
    public function getForUser($userId, $type = null) {
        $where = 'user_id = :user_id AND is_active = 1';
        $params = ['user_id' => $userId];
        
        if ($type && in_array($type, ['income', 'expense'])) {
            $where .= ' AND (type = :type OR type = "both")';
            $params['type'] = $type;
        }
        
        return $this->db->select('categories', '*', $where, $params, 'sort_order ASC, name ASC')->fetchAll();
    }
    
    /**
     * Get category statistics
     */
    public function getStatistics($userId, $categoryId = null, $period = 'month') {
        $dateCondition = $this->getPeriodCondition($period);
        $where = ['t.user_id = :user_id', 't.is_deleted = 0', $dateCondition];
        $params = ['user_id' => $userId];
        
        if ($categoryId) {
            $where[] = 't.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT 
                c.id,
                c.name,
                c.icon,
                c.color,
                c.type as category_type,
                c.budget_limit,
                t.type as transaction_type,
                COUNT(t.id) as transaction_count,
                SUM(t.amount) as total_amount,
                AVG(t.amount) as avg_amount,
                MIN(t.amount) as min_amount,
                MAX(t.amount) as max_amount
            FROM categories c
            LEFT JOIN transactions t ON c.id = t.category_id AND {$whereClause}
            WHERE c.user_id = :user_id AND c.is_active = 1
            " . ($categoryId ? "AND c.id = :category_id" : "") . "
            GROUP BY c.id, t.type
            ORDER BY c.sort_order ASC, c.name ASC
        ";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Get budget usage for categories
     */
    public function getBudgetUsage($userId, $period = 'month') {
        $dateCondition = $this->getPeriodCondition($period);
        
        $sql = "
            SELECT 
                c.id,
                c.name,
                c.icon,
                c.color,
                c.budget_limit,
                COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as spent_amount,
                CASE 
                    WHEN c.budget_limit > 0 THEN 
                        (COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) / c.budget_limit * 100)
                    ELSE 0 
                END as usage_percentage,
                CASE 
                    WHEN c.budget_limit > 0 THEN 
                        (c.budget_limit - COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0))
                    ELSE 0 
                END as remaining_budget
            FROM categories c
            LEFT JOIN transactions t ON c.id = t.category_id 
                AND t.user_id = c.user_id 
                AND t.is_deleted = 0 
                AND {$dateCondition}
            WHERE c.user_id = :user_id 
                AND c.is_active = 1 
                AND c.budget_limit > 0
            GROUP BY c.id
            ORDER BY usage_percentage DESC
        ";
        
        return $this->db->query($sql, ['user_id' => $userId])->fetchAll();
    }
    
    /**
     * Reorder categories
     */
    public function reorder($userId, $categoryOrders) {
        try {
            $this->db->beginTransaction();
            
            foreach ($categoryOrders as $order) {
                if (isset($order['id']) && isset($order['sort_order'])) {
                    $this->db->update('categories', 
                        ['sort_order' => (int)$order['sort_order']], 
                        'id = :id AND user_id = :user_id', [
                        'id' => $order['id'],
                        'user_id' => $userId
                    ]);
                }
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Categories reordered successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Category reorder error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while reordering categories'];
        }
    }
    
    /**
     * Get default categories
     */
    public function getDefaultCategories() {
        return [
            ['name' => '🏠 Rent', 'icon' => '🏠', 'type' => 'expense', 'color' => '#EF4444'],
            ['name' => '⚡ Electricity', 'icon' => '⚡', 'type' => 'expense', 'color' => '#F59E0B'],
            ['name' => '🛒 Shopping', 'icon' => '🛒', 'type' => 'expense', 'color' => '#3B82F6'],
            ['name' => '⛽ Fuel', 'icon' => '⛽', 'type' => 'expense', 'color' => '#10B981'],
            ['name' => '👕 Clothing', 'icon' => '👕', 'type' => 'expense', 'color' => '#8B5CF6'],
            ['name' => '💧 Water', 'icon' => '💧', 'type' => 'expense', 'color' => '#06B6D4'],
            ['name' => '🍽️ Food', 'icon' => '🍽️', 'type' => 'expense', 'color' => '#84CC16'],
            ['name' => '📱 Phone/Internet', 'icon' => '📱', 'type' => 'expense', 'color' => '#6366F1'],
            ['name' => '🚗 Transportation', 'icon' => '🚗', 'type' => 'expense', 'color' => '#F97316'],
            ['name' => '💊 Healthcare', 'icon' => '💊', 'type' => 'expense', 'color' => '#EC4899'],
            ['name' => '💰 Salary', 'icon' => '💰', 'type' => 'income', 'color' => '#10B981'],
            ['name' => '💼 Freelance', 'icon' => '💼', 'type' => 'income', 'color' => '#3B82F6'],
            ['name' => '📈 Investment', 'icon' => '📈', 'type' => 'income', 'color' => '#8B5CF6']
        ];
    }
    
    /**
     * Create default categories for user
     */
    public function createDefaultCategories($userId) {
        try {
            $defaultCategories = $this->getDefaultCategories();
            $created = 0;
            
            foreach ($defaultCategories as $index => $category) {
                $categoryData = [
                    'user_id' => $userId,
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'type' => $category['type'],
                    'color' => $category['color'],
                    'sort_order' => $index + 1
                ];
                
                if ($this->db->insert('categories', $categoryData)) {
                    $created++;
                }
            }
            
            return [
                'success' => true,
                'message' => "Created {$created} default categories",
                'count' => $created
            ];
            
        } catch (Exception $e) {
            error_log('Default categories creation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while creating default categories'];
        }
    }
    
    /**
     * Validate color format
     */
    private function validateColor($color) {
        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return $color;
        }
        return '#3B82F6'; // Default blue color
    }
    
    /**
     * Get next sort order for user
     */
    private function getNextSortOrder($userId) {
        $maxOrder = $this->db->query(
            'SELECT MAX(sort_order) FROM categories WHERE user_id = :user_id AND is_active = 1',
            ['user_id' => $userId]
        )->fetchColumn();
        
        return ($maxOrder ?? 0) + 1;
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