<?php
/**
 * LuigiTals Wallet Management System
 * Categories Management Page
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

define('APP_ROOT', __DIR__);
require_once 'classes/Auth.php';

$auth = new Auth();

// Check authentication
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - LuigiTals Wallet</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .glassmorphism {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glassmorphism {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .category-card {
            transition: all 0.3s ease;
        }
        
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg transition-colors duration-300 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i data-lucide="wallet" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="hidden md:block">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">LuigiTals Wallet</h1>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Back to Dashboard -->
                    <a href="index.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 flex items-center space-x-2">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        <span class="hidden md:block">Dashboard</span>
                    </a>
                    
                    <!-- Theme Toggle -->
                    <button id="themeToggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                        <i data-lucide="sun" class="w-5 h-5 text-gray-600 dark:text-gray-300 hidden dark:block"></i>
                        <i data-lucide="moon" class="w-5 h-5 text-gray-600 dark:text-gray-300 block dark:hidden"></i>
                    </button>
                    
                    <!-- User Info -->
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-medium"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                        </div>
                        <span class="hidden md:block text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Category Management</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Organize your transactions with custom categories.</p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-4 mb-8">
            <button id="addCategoryBtn" class="btn-primary text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>Add Category</span>
            </button>
            
            <button id="createDefaultsBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-all duration-300">
                <i data-lucide="package" class="w-5 h-5"></i>
                <span>Create Defaults</span>
            </button>
            
            <button id="sortCategoriesBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-all duration-300">
                <i data-lucide="move" class="w-5 h-5"></i>
                <span>Reorder</span>
            </button>
        </div>

        <!-- Categories Grid -->
        <div id="categoriesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Categories will be loaded here -->
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="text-center py-12 hidden">
            <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="folder" class="w-12 h-12 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Categories Yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Create your first category to start organizing transactions.</p>
            <button onclick="showAddCategoryModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                Create First Category
            </button>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 dark:text-white">Add Category</h3>
                <button id="closeCategoryModal" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="category_id">
                <input type="hidden" name="_token" value="<?php echo $auth->generateCsrfToken(); ?>">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category Name</label>
                        <input type="text" id="categoryName" name="name" placeholder="e.g., Groceries" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Icon</label>
                            <input type="text" id="categoryIcon" name="icon" placeholder="📁" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-center text-xl" maxlength="2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                            <input type="color" id="categoryColor" name="color" value="#3B82F6" class="w-full h-12 border border-gray-300 dark:border-gray-600 rounded-lg">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                        <select id="categoryType" name="type" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Budget Limit (Optional)</label>
                        <input type="number" id="categoryBudget" name="budget_limit" step="0.01" placeholder="0.00" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <button type="button" id="cancelCategory" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                        <button type="submit" id="saveCategoryBtn" class="flex-1 btn-primary text-white px-4 py-2 rounded-lg">Save Category</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-center mb-4">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                    <i data-lucide="trash-2" class="w-6 h-6 text-red-600"></i>
                </div>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center mb-2">Delete Category</h3>
            <p class="text-gray-600 dark:text-gray-400 text-center mb-6">Are you sure you want to delete this category? This action cannot be undone.</p>
            
            <div class="flex space-x-4">
                <button id="cancelDelete" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                <button id="confirmDelete" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">Delete</button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-8 flex items-center space-x-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="text-gray-900 dark:text-white font-medium">Loading categories...</span>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="fixed top-20 right-4 z-50 space-y-2"></div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Global variables
        const csrfToken = '<?php echo $auth->generateCsrfToken(); ?>';
        let categories = [];
        let editingCategory = null;
        let categoryToDelete = null;
        
        // Theme management
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        }
        
        document.getElementById('themeToggle').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('theme', currentTheme);
        });
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            loadCategories();
            initEventListeners();
        });
        
        // Event listeners
        function initEventListeners() {
            // Add category button
            document.getElementById('addCategoryBtn').addEventListener('click', () => {
                showAddCategoryModal();
            });
            
            // Create defaults button
            document.getElementById('createDefaultsBtn').addEventListener('click', () => {
                createDefaultCategories();
            });
            
            // Modal controls
            document.getElementById('closeCategoryModal').addEventListener('click', hideCategoryModal);
            document.getElementById('cancelCategory').addEventListener('click', hideCategoryModal);
            
            // Form submission
            document.getElementById('categoryForm').addEventListener('submit', handleCategorySubmit);
            
            // Delete modal controls
            document.getElementById('cancelDelete').addEventListener('click', hideDeleteModal);
            document.getElementById('confirmDelete').addEventListener('click', handleCategoryDelete);
        }
        
        // Load categories
        async function loadCategories() {
            try {
                showLoading();
                
                const response = await fetch('api/categories.php?action=list');
                const result = await response.json();
                
                hideLoading();
                
                if (result.success) {
                    categories = result.data || [];
                    renderCategories();
                } else {
                    showNotification(result.message || 'Failed to load categories', 'error');
                }
            } catch (error) {
                hideLoading();
                console.error('Failed to load categories:', error);
                showNotification('Failed to load categories', 'error');
            }
        }
        
        // Render categories
        function renderCategories() {
            const grid = document.getElementById('categoriesGrid');
            const emptyState = document.getElementById('emptyState');
            
            if (categories.length === 0) {
                grid.classList.add('hidden');
                emptyState.classList.remove('hidden');
                return;
            }
            
            grid.classList.remove('hidden');
            emptyState.classList.add('hidden');
            
            grid.innerHTML = categories.map(category => `
                <div class="category-card bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl" style="background-color: ${category.color}20; color: ${category.color}">
                                ${category.icon || '📁'}
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">${escapeHtml(category.name)}</h3>
                                <p class="text-sm text-gray-500 capitalize">${category.type}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <button onclick="editCategory(${category.id})" class="p-2 text-gray-400 hover:text-blue-600 transition-colors">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteCategory(${category.id})" class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    
                    ${category.budget_limit > 0 ? `
                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Budget</span>
                                <span class="font-medium text-gray-900 dark:text-white">${formatCurrency(category.budget_limit)}</span>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `).join('');
            
            // Re-initialize icons
            lucide.createIcons();
        }
        
        // Show add category modal
        function showAddCategoryModal() {
            editingCategory = null;
            document.getElementById('modalTitle').textContent = 'Add Category';
            document.getElementById('saveCategoryBtn').textContent = 'Save Category';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryColor').value = '#3B82F6';
            document.getElementById('categoryModal').classList.remove('hidden');
        }
        
        // Edit category
        function editCategory(categoryId) {
            const category = categories.find(c => c.id == categoryId);
            if (!category) return;
            
            editingCategory = category;
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('saveCategoryBtn').textContent = 'Update Category';
            
            // Populate form
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryIcon').value = category.icon || '';
            document.getElementById('categoryColor').value = category.color || '#3B82F6';
            document.getElementById('categoryType').value = category.type || 'expense';
            document.getElementById('categoryBudget').value = category.budget_limit || '';
            
            document.getElementById('categoryModal').classList.remove('hidden');
        }
        
        // Delete category
        function deleteCategory(categoryId) {
            categoryToDelete = categoryId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        // Handle category form submission
        async function handleCategorySubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const categoryData = {
                name: formData.get('name'),
                icon: formData.get('icon'),
                color: formData.get('color'),
                type: formData.get('type'),
                budget_limit: parseFloat(formData.get('budget_limit')) || 0,
                _token: csrfToken
            };
            
            try {
                showLoading();
                
                const isEditing = editingCategory !== null;
                const url = isEditing ? 
                    `api/categories.php?action=update&id=${editingCategory.id}` : 
                    'api/categories.php?action=create';
                
                const method = isEditing ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(categoryData)
                });
                
                const result = await response.json();
                hideLoading();
                
                if (result.success) {
                    showNotification(
                        isEditing ? 'Category updated successfully!' : 'Category created successfully!', 
                        'success'
                    );
                    hideCategoryModal();
                    loadCategories();
                } else {
                    showNotification(result.message || 'Failed to save category', 'error');
                }
            } catch (error) {
                hideLoading();
                console.error('Category save error:', error);
                showNotification('Failed to save category', 'error');
            }
        }
        
        // Handle category deletion
        async function handleCategoryDelete() {
            if (!categoryToDelete) return;
            
            try {
                showLoading();
                
                const response = await fetch(`api/categories.php?action=delete&id=${categoryToDelete}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _token: csrfToken })
                });
                
                const result = await response.json();
                hideLoading();
                
                if (result.success) {
                    showNotification('Category deleted successfully!', 'success');
                    hideDeleteModal();
                    loadCategories();
                } else {
                    showNotification(result.message || 'Failed to delete category', 'error');
                }
            } catch (error) {
                hideLoading();
                console.error('Category delete error:', error);
                showNotification('Failed to delete category', 'error');
            }
        }
        
        // Create default categories
        async function createDefaultCategories() {
            try {
                showLoading();
                
                const response = await fetch('api/categories.php?action=create-defaults', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _token: csrfToken })
                });
                
                const result = await response.json();
                hideLoading();
                
                if (result.success) {
                    showNotification(`Created ${result.count} default categories!`, 'success');
                    loadCategories();
                } else {
                    showNotification(result.message || 'Failed to create default categories', 'error');
                }
            } catch (error) {
                hideLoading();
                console.error('Default categories error:', error);
                showNotification('Failed to create default categories', 'error');
            }
        }
        
        // Modal controls
        function hideCategoryModal() {
            document.getElementById('categoryModal').classList.add('hidden');
            editingCategory = null;
        }
        
        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            categoryToDelete = null;
        }
        
        // Loading functions
        function showLoading() {
            document.getElementById('loadingState').classList.remove('hidden');
        }
        
        function hideLoading() {
            document.getElementById('loadingState').classList.add('hidden');
        }
        
        // Utility functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-KE', {
                style: 'currency',
                currency: 'KES'
            }).format(amount || 0);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Notification function
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            const bgColor = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            }[type] || 'bg-blue-500';
            
            notification.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 max-w-sm`;
            notification.innerHTML = `
                <span>${escapeHtml(message)}</span>
                <button onclick="this.parentElement.remove()" class="ml-auto">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            `;
            
            container.appendChild(notification);
            lucide.createIcons();
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>