<?php
/**
 * LuigiTals Wallet Management System
 * Transactions Management Page
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
    <title>Transactions - LuigiTals Wallet</title>
    
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
        
        .transaction-row {
            transition: all 0.2s ease;
        }
        
        .transaction-row:hover {
            background-color: rgba(59, 130, 246, 0.05);
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
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Transaction History</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">View and manage all your financial transactions.</p>
        </div>

        <!-- Filters and Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow mb-8">
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <!-- Filters -->
                <div class="flex flex-wrap gap-4 items-center">
                    <div>
                        <select id="typeFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Types</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    
                    <div>
                        <select id="categoryFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    
                    <div>
                        <input type="date" id="dateFromFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <input type="date" id="dateToFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <input type="text" id="searchFilter" placeholder="Search transactions..." class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <button id="clearFilters" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <!-- Actions -->
                <div class="flex gap-2">
                    <button id="addTransactionBtn" class="btn-primary text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Add Transaction</span>
                    </button>
                    
                    <button id="exportBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium flex items-center space-x-2 transition-all duration-300">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Export</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-green-50 dark:bg-green-900/20 p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-600 dark:text-green-400 text-sm font-medium">Total Income</p>
                        <p id="totalIncome" class="text-2xl font-bold text-green-700 dark:text-green-300">KSh 0.00</p>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-red-50 dark:bg-red-900/20 p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-600 dark:text-red-400 text-sm font-medium">Total Expenses</p>
                        <p id="totalExpenses" class="text-2xl font-bold text-red-700 dark:text-red-300">KSh 0.00</p>
                    </div>
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="trending-down" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-600 dark:text-blue-400 text-sm font-medium">Net Balance</p>
                        <p id="netBalance" class="text-2xl font-bold text-blue-700 dark:text-blue-300">KSh 0.00</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="wallet" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">All Transactions</h3>
                    <div class="flex items-center space-x-2">
                        <span id="transactionCount" class="text-sm text-gray-500 dark:text-gray-400">0 transactions</span>
                        <div class="flex items-center space-x-1">
                            <button id="prevPage" class="p-2 text-gray-400 hover:text-gray-600 disabled:opacity-50" disabled>
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </button>
                            <span id="pageInfo" class="text-sm text-gray-500 px-2">Page 1 of 1</span>
                            <button id="nextPage" class="p-2 text-gray-400 hover:text-gray-600 disabled:opacity-50" disabled>
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left py-3 px-6 text-gray-600 dark:text-gray-400 font-medium">
                                <button id="sortDate" class="flex items-center space-x-1 hover:text-gray-800 dark:hover:text-gray-200">
                                    <span>Date</span>
                                    <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                                </button>
                            </th>
                            <th class="text-left py-3 px-6 text-gray-600 dark:text-gray-400 font-medium">Description</th>
                            <th class="text-left py-3 px-6 text-gray-600 dark:text-gray-400 font-medium">Category</th>
                            <th class="text-left py-3 px-6 text-gray-600 dark:text-gray-400 font-medium">Type</th>
                            <th class="text-right py-3 px-6 text-gray-600 dark:text-gray-400 font-medium">
                                <button id="sortAmount" class="flex items-center space-x-1 hover:text-gray-800 dark:hover:text-gray-200 ml-auto">
                                    <span>Amount</span>
                                    <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                                </button>
                            </th>
                            <th class="text-center py-3 px-6 text-gray-600 dark:text-gray-400 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody">
                        <!-- Transactions will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Transaction Modal -->
    <div id="transactionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 dark:text-white">Add Transaction</h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form id="transactionForm">
                <input type="hidden" id="transactionId" name="transaction_id">
                <input type="hidden" name="_token" value="<?php echo $auth->generateCsrfToken(); ?>">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                        <select id="transactionType" name="type" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount</label>
                        <input type="number" id="transactionAmount" name="amount" step="0.01" placeholder="KSh 0.00" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                        <select id="transactionCategory" name="category_id" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Loading categories...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <input type="text" id="transactionDescription" name="description" placeholder="Enter description" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                        <input type="date" id="transactionDate" name="transaction_date" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                        <select id="paymentMethod" name="payment_method" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="check">Check</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                        <textarea id="transactionNotes" name="notes" rows="2" placeholder="Additional notes..." class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <button type="button" id="cancelBtn" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                        <button type="submit" id="saveBtn" class="flex-1 btn-primary text-white px-4 py-2 rounded-lg">Save Transaction</button>
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
            
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center mb-2">Delete Transaction</h3>
            <p class="text-gray-600 dark:text-gray-400 text-center mb-6">Are you sure you want to delete this transaction? This action cannot be undone.</p>
            
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
            <span class="text-gray-900 dark:text-white font-medium">Loading...</span>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="fixed top-20 right-4 z-50 space-y-2"></div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Global variables
        const csrfToken = '<?php echo $auth->generateCsrfToken(); ?>';
        let transactions = [];
        let categories = [];
        let currentPage = 1;
        let totalPages = 1;
        let currentFilters = {};
        let sortField = 'transaction_date';
        let sortDirection = 'desc';
        let editingTransaction = null;
        let transactionToDelete = null;
        
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
            loadTransactions();
            initEventListeners();
            setDefaultDates();
        });
        
        function setDefaultDates() {
            const today = new Date();
            const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            
            document.getElementById('dateFromFilter').value = firstDayOfMonth.toISOString().split('T')[0];
            document.getElementById('dateToFilter').value = today.toISOString().split('T')[0];
        }
        
        // Event listeners
        function initEventListeners() {
            // Modal controls
            document.getElementById('addTransactionBtn').addEventListener('click', () => showTransactionModal());
            document.getElementById('closeModal').addEventListener('click', hideTransactionModal);
            document.getElementById('cancelBtn').addEventListener('click', hideTransactionModal);
            
            // Form submission
            document.getElementById('transactionForm').addEventListener('submit', handleTransactionSubmit);
            
            // Delete modal
            document.getElementById('cancelDelete').addEventListener('click', hideDeleteModal);
            document.getElementById('confirmDelete').addEventListener('click', handleTransactionDelete);
            
            // Filters
            document.getElementById('typeFilter').addEventListener('change', applyFilters);
            document.getElementById('categoryFilter').addEventListener('change', applyFilters);
            document.getElementById('dateFromFilter').addEventListener('change', applyFilters);
            document.getElementById('dateToFilter').addEventListener('change', applyFilters);
            document.getElementById('searchFilter').addEventListener('input', debounce(applyFilters, 500));
            document.getElementById('clearFilters').addEventListener('click', clearFilters);
            
            // Pagination
            document.getElementById('prevPage').addEventListener('click', () => changePage(currentPage - 1));
            document.getElementById('nextPage').addEventListener('click', () => changePage(currentPage + 1));
            
            // Sorting
            document.getElementById('sortDate').addEventListener('click', () => toggleSort('transaction_date'));
            document.getElementById('sortAmount').addEventListener('click', () => toggleSort('amount'));
            
            // Export
            document.getElementById('exportBtn').addEventListener('click', exportTransactions);
        }
        
        // Load categories
        async function loadCategories() {
            try {
                const response = await fetch('api/categories.php?action=list');
                const result = await response.json();
                
                if (result.success) {
                    categories = result.data || [];
                    updateCategorySelects();
                }
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        }
        
        function updateCategorySelects() {
            // Update transaction form select
            const transactionSelect = document.getElementById('transactionCategory');
            transactionSelect.innerHTML = categories.map(category => 
                `<option value="${category.id}">${escapeHtml(category.name)}</option>`
            ).join('');
            
            // Update filter select
            const filterSelect = document.getElementById('categoryFilter');
            filterSelect.innerHTML = '<option value="">All Categories</option>' + categories.map(category => 
                `<option value="${category.id}">${escapeHtml(category.name)}</option>`
            ).join('');
        }
        
        // Load transactions
        async function loadTransactions() {
            try {
                showLoading();
                
                const params = new URLSearchParams({
                    page: currentPage,
                    per_page: 25,
                    order_by: `${sortField} ${sortDirection.toUpperCase()}`,
                    ...currentFilters
                });
                
                const response = await fetch(`api/transactions.php?action=list&${params}`);
                const result = await response.json();
                
                hideLoading();
                
                if (result.success) {
                    transactions = result.data || [];
                    updatePagination(result.pagination);
                    renderTransactions();
                    updateSummary();
                } else {
                    showNotification(result.message || 'Failed to load transactions', 'error');
                }
            } catch (error) {
                hideLoading();
                console.error('Failed to load transactions:', error);
                showNotification('Failed to load transactions', 'error');
            }
        }
        
        function renderTransactions() {
            const tbody = document.getElementById('transactionsTableBody');
            
            if (transactions.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                            <p class="text-lg font-medium mb-2">No transactions found</p>
                            <p class="text-sm">Try adjusting your filters or add a new transaction.</p>
                        </td>
                    </tr>
                `;
                lucide.createIcons();
                return;
            }
            
            tbody.innerHTML = transactions.map(transaction => `
                <tr class="transaction-row border-b border-gray-100 dark:border-gray-700">
                    <td class="py-4 px-6 text-gray-900 dark:text-white">
                        ${formatDate(transaction.transaction_date)}
                    </td>
                    <td class="py-4 px-6">
                        <div class="text-gray-900 dark:text-white font-medium">${escapeHtml(transaction.description)}</div>
                        ${transaction.notes ? `<div class="text-sm text-gray-500 dark:text-gray-400">${escapeHtml(transaction.notes)}</div>` : ''}
                    </td>
                    <td class="py-4 px-6">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                            ${escapeHtml(transaction.category_name)}
                        </span>
                    </td>
                    <td class="py-4 px-6">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            transaction.type === 'income' 
                                ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                                : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
                       }">
                           ${transaction.type === 'income' ? 'Income' : 'Expense'}
                       </span>
                   </td>
                   <td class="py-4 px-6 text-right">
                       <span class="${transaction.type === 'income' ? 'text-green-600' : 'text-red-600'} font-semibold">
                           ${transaction.type === 'income' ? '+' : '-'}${formatCurrency(transaction.amount)}
                       </span>
                       <div class="text-xs text-gray-500 dark:text-gray-400">${transaction.payment_method}</div>
                   </td>
                   <td class="py-4 px-6 text-center">
                       <div class="flex items-center justify-center space-x-2">
                           <button onclick="editTransaction(${transaction.id})" class="p-1 text-gray-400 hover:text-blue-600 transition-colors">
                               <i data-lucide="edit" class="w-4 h-4"></i>
                           </button>
                           <button onclick="deleteTransaction(${transaction.id})" class="p-1 text-gray-400 hover:text-red-600 transition-colors">
                               <i data-lucide="trash-2" class="w-4 h-4"></i>
                           </button>
                       </div>
                   </td>
               </tr>
           `).join('');
           
           lucide.createIcons();
       }
       
       function updatePagination(pagination) {
           currentPage = pagination.current_page;
           totalPages = pagination.total_pages;
           
           document.getElementById('transactionCount').textContent = `${pagination.total_count} transactions`;
           document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
           
           document.getElementById('prevPage').disabled = !pagination.has_previous;
           document.getElementById('nextPage').disabled = !pagination.has_next;
       }
       
       function updateSummary() {
           let totalIncome = 0;
           let totalExpenses = 0;
           
           transactions.forEach(transaction => {
               if (transaction.type === 'income') {
                   totalIncome += parseFloat(transaction.amount);
               } else {
                   totalExpenses += parseFloat(transaction.amount);
               }
           });
           
           const netBalance = totalIncome - totalExpenses;
           
           document.getElementById('totalIncome').textContent = formatCurrency(totalIncome);
           document.getElementById('totalExpenses').textContent = formatCurrency(totalExpenses);
           document.getElementById('netBalance').textContent = formatCurrency(netBalance);
       }
       
       // Filter functions
       function applyFilters() {
           currentFilters = {};
           currentPage = 1;
           
           const typeFilter = document.getElementById('typeFilter').value;
           const categoryFilter = document.getElementById('categoryFilter').value;
           const dateFromFilter = document.getElementById('dateFromFilter').value;
           const dateToFilter = document.getElementById('dateToFilter').value;
           const searchFilter = document.getElementById('searchFilter').value;
           
           if (typeFilter) currentFilters.type = typeFilter;
           if (categoryFilter) currentFilters.category_id = categoryFilter;
           if (dateFromFilter) currentFilters.date_from = dateFromFilter;
           if (dateToFilter) currentFilters.date_to = dateToFilter;
           if (searchFilter) currentFilters.search = searchFilter;
           
           loadTransactions();
       }
       
       function clearFilters() {
           document.getElementById('typeFilter').value = '';
           document.getElementById('categoryFilter').value = '';
           document.getElementById('searchFilter').value = '';
           setDefaultDates();
           currentFilters = {};
           currentPage = 1;
           loadTransactions();
       }
       
       // Sorting
       function toggleSort(field) {
           if (sortField === field) {
               sortDirection = sortDirection === 'desc' ? 'asc' : 'desc';
           } else {
               sortField = field;
               sortDirection = 'desc';
           }
           loadTransactions();
       }
       
       // Pagination
       function changePage(page) {
           if (page >= 1 && page <= totalPages) {
               currentPage = page;
               loadTransactions();
           }
       }
       
       // Transaction modal
       function showTransactionModal(transaction = null) {
           editingTransaction = transaction;
           
           if (transaction) {
               document.getElementById('modalTitle').textContent = 'Edit Transaction';
               document.getElementById('saveBtn').textContent = 'Update Transaction';
               
               // Populate form
               document.getElementById('transactionId').value = transaction.id;
               document.getElementById('transactionType').value = transaction.type;
               document.getElementById('transactionAmount').value = transaction.amount;
               document.getElementById('transactionCategory').value = transaction.category_id;
               document.getElementById('transactionDescription').value = transaction.description;
               document.getElementById('transactionDate').value = transaction.transaction_date;
               document.getElementById('paymentMethod').value = transaction.payment_method;
               document.getElementById('transactionNotes').value = transaction.notes || '';
           } else {
               document.getElementById('modalTitle').textContent = 'Add Transaction';
               document.getElementById('saveBtn').textContent = 'Add Transaction';
               
               // Reset form
               document.getElementById('transactionForm').reset();
               document.getElementById('transactionId').value = '';
               document.getElementById('transactionDate').value = new Date().toISOString().split('T')[0];
           }
           
           document.getElementById('transactionModal').classList.remove('hidden');
       }
       
       function hideTransactionModal() {
           document.getElementById('transactionModal').classList.add('hidden');
           editingTransaction = null;
       }
       
       // Edit transaction
       function editTransaction(transactionId) {
           const transaction = transactions.find(t => t.id == transactionId);
           if (transaction) {
               showTransactionModal(transaction);
           }
       }
       
       // Delete transaction
       function deleteTransaction(transactionId) {
           transactionToDelete = transactionId;
           document.getElementById('deleteModal').classList.remove('hidden');
       }
       
       function hideDeleteModal() {
           document.getElementById('deleteModal').classList.add('hidden');
           transactionToDelete = null;
       }
       
       // Handle form submission
       async function handleTransactionSubmit(e) {
           e.preventDefault();
           
           const formData = new FormData(e.target);
           const transactionData = {
               type: formData.get('type'),
               category_id: parseInt(formData.get('category_id')),
               amount: parseFloat(formData.get('amount')),
               description: formData.get('description'),
               transaction_date: formData.get('transaction_date'),
               payment_method: formData.get('payment_method'),
               notes: formData.get('notes'),
               _token: csrfToken
           };
           
           try {
               showLoading();
               
               const isEditing = editingTransaction !== null;
               const url = isEditing ? 
                   `api/transactions.php?action=update&id=${editingTransaction.id}` : 
                   'api/transactions.php?action=create';
               
               const method = isEditing ? 'PUT' : 'POST';
               
               const response = await fetch(url, {
                   method: method,
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify(transactionData)
               });
               
               const result = await response.json();
               hideLoading();
               
               if (result.success) {
                   showNotification(
                       isEditing ? 'Transaction updated successfully!' : 'Transaction added successfully!', 
                       'success'
                   );
                   hideTransactionModal();
                   loadTransactions();
               } else {
                   showNotification(result.message || 'Failed to save transaction', 'error');
               }
           } catch (error) {
               hideLoading();
               console.error('Transaction save error:', error);
               showNotification('Failed to save transaction', 'error');
           }
       }
       
       // Handle transaction deletion
       async function handleTransactionDelete() {
           if (!transactionToDelete) return;
           
           try {
               showLoading();
               
               const response = await fetch(`api/transactions.php?action=delete&id=${transactionToDelete}`, {
                   method: 'DELETE',
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify({ _token: csrfToken })
               });
               
               const result = await response.json();
               hideLoading();
               
               if (result.success) {
                   showNotification('Transaction deleted successfully!', 'success');
                   hideDeleteModal();
                   loadTransactions();
               } else {
                   showNotification(result.message || 'Failed to delete transaction', 'error');
               }
           } catch (error) {
               hideLoading();
               console.error('Transaction delete error:', error);
               showNotification('Failed to delete transaction', 'error');
           }
       }
       
       // Export transactions
       async function exportTransactions() {
           try {
               showLoading();
               
               const response = await fetch('api/reports.php?action=export', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify({
                       report_type: 'income-statement',
                       format: 'csv',
                       start_date: currentFilters.date_from || document.getElementById('dateFromFilter').value,
                       end_date: currentFilters.date_to || document.getElementById('dateToFilter').value,
                       _token: csrfToken
                   })
               });
               
               const result = await response.json();
               hideLoading();
               
               if (result.success) {
                   // Create download link
                   const link = document.createElement('a');
                   link.href = result.download_url;
                   link.download = result.filename;
                   document.body.appendChild(link);
                   link.click();
                   document.body.removeChild(link);
                   
                   showNotification('Transactions exported successfully!', 'success');
               } else {
                   showNotification(result.message || 'Failed to export transactions', 'error');
               }
           } catch (error) {
               hideLoading();
               console.error('Export error:', error);
               showNotification('Failed to export transactions', 'error');
           }
       }
       
       // Utility functions
       function formatCurrency(amount) {
           return new Intl.NumberFormat('en-KE', {
               style: 'currency',
               currency: 'KES'
           }).format(amount || 0);
       }
       
       function formatDate(dateString) {
           return new Date(dateString).toLocaleDateString('en-US', {
               month: 'short',
               day: 'numeric',
               year: 'numeric'
           });
       }
       
       function escapeHtml(text) {
           const div = document.createElement('div');
           div.textContent = text;
           return div.innerHTML;
       }
       
       function debounce(func, wait) {
           let timeout;
           return function executedFunction(...args) {
               const later = () => {
                   clearTimeout(timeout);
                   func(...args);
               };
               clearTimeout(timeout);
               timeout = setTimeout(later, wait);
           };
       }
       
       function showLoading() {
           document.getElementById('loadingState').classList.remove('hidden');
       }
       
       function hideLoading() {
           document.getElementById('loadingState').classList.add('hidden');
       }
       
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