<?php
/**
 * LuigiTals Wallet Management System
 * Complete Functional Dashboard
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

define('APP_ROOT', __DIR__);
require_once 'classes/Auth.php';

$auth = new Auth();

// Check authentication - redirect to login if not authenticated
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Get current user
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LuiDigitals Wallet - Premium Financial Management</title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1f2937">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="icons/icon-192x192.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js"></script>
    
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
        
        .dark .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }
        
        .animate-slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        .dark .category-card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .notification {
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg transition-colors duration-300 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i data-lucide="wallet" class="w-6 h-6 text-white"></i>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">LuiDigitals Wallet</h1>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <button id="themeToggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                        <i data-lucide="sun" class="w-5 h-5 text-gray-600 dark:text-gray-300 hidden dark:block"></i>
                        <i data-lucide="moon" class="w-5 h-5 text-gray-600 dark:text-gray-300 block dark:hidden"></i>
                    </button>
                    
                    <!-- Notifications -->
                    <button id="notificationBtn" class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                        <i data-lucide="bell" class="w-5 h-5 text-gray-600 dark:text-gray-300"></i>
                        <span id="notificationBadge" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                    </button>
                    
                    <!-- User Menu -->
                    <div class="relative">
                        <button id="userMenuBtn" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                            </div>
                            <span class="hidden md:block text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($user['username']); ?></span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                        </button>
                        
                        <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 hidden">
                            <a href="settings.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-2">
                                <i data-lucide="settings" class="w-4 h-4"></i>
                                <span>Settings</span>
                            </a>
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-2">
                                <i data-lucide="user" class="w-4 h-4"></i>
                                <span>Profile</span>
                            </a>
                            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                            <a href="?logout=1" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-2">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Message -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>!</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Here's your financial overview for today.</p>
        </div>

        <!-- Dashboard Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Balance Card -->
            <div class="glassmorphism rounded-xl p-6 category-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Balance</p>
                        <p id="totalBalance" class="text-2xl font-bold text-gray-900 dark:text-white">KSh 0.00</p>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="dollar-sign" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Income Card -->
            <div class="glassmorphism rounded-xl p-6 category-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Monthly Income</p>
                        <p id="monthlyIncome" class="text-2xl font-bold text-gray-900 dark:text-white">KSh 0.00</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Expenses Card -->
            <div class="glassmorphism rounded-xl p-6 category-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Monthly Expenses</p>
                        <p id="monthlyExpenses" class="text-2xl font-bold text-gray-900 dark:text-white">KSh 0.00</p>
                    </div>
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="trending-down" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>
            
            <!-- Savings Goal Card -->
            <div class="glassmorphism rounded-xl p-6 category-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Savings Goal</p>
                        <p id="savingsGoal" class="text-2xl font-bold text-gray-900 dark:text-white">KSh 0.00</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="target" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <div class="flex flex-wrap gap-4">
                <button id="addTransactionBtn" class="btn-primary text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    <span>Add Transaction</span>
                </button>
                
                <button id="addIncomeBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-all duration-300">
                    <i data-lucide="arrow-up-right" class="w-5 h-5"></i>
                    <span>Add Income</span>
                </button>
                
                <a href="reports.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-all duration-300 no-underline">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    <span>View Reports</span>
                </a>
                
                <a href="categories.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-all duration-300 no-underline">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span>Categories</span>
                </a>

                <a href="budgets.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-all duration-300 no-underline">
                    <i data-lucide="target" class="w-5 h-5"></i>
                    <span>Budgets</span>
                </a>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Expense Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Expense Overview</h3>
                <div class="h-64">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
            
            <!-- Income vs Expense Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Income vs Expenses</h3>
                <div class="h-64">
                    <canvas id="incomeExpenseChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
                <a href="transactions.php" class="text-blue-600 hover:text-blue-700 font-medium flex items-center space-x-1">
                    <span>View All</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-gray-600 dark:text-gray-400">Date</th>
                            <th class="text-left py-3 px-4 text-gray-600 dark:text-gray-400">Description</th>
                            <th class="text-left py-3 px-4 text-gray-600 dark:text-gray-400">Category</th>
                            <th class="text-right py-3 px-4 text-gray-600 dark:text-gray-400">Amount</th>
                            <th class="text-center py-3 px-4 text-gray-600 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentTransactions">
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                                <p>No transactions yet. Add your first transaction!</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Transaction Modal -->
    <div id="addTransactionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md animate-slide-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Transaction</h3>
                <button id="closeTransactionModal" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form id="transactionForm">
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
                    
                    <div class="flex space-x-4 mt-6">
                        <button type="button" id="cancelTransaction" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                        <button type="submit" class="flex-1 btn-primary text-white px-4 py-2 rounded-lg">Add Transaction</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="fixed top-20 right-4 z-50 space-y-2"></div>

    <!-- PWA Install Button -->
    <button id="installPWA" class="fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-full shadow-lg hidden transition-all duration-300">
        <i data-lucide="download" class="w-6 h-6"></i>
    </button>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Pass PHP data to JavaScript
        window.currentUser = <?php echo json_encode($user); ?>;
        window.csrfToken = '<?php echo $auth->generateCsrfToken(); ?>';
        
        // Global variables
        let transactions = [];
        let categories = [];
        
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
        
        // User menu functionality
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');
        
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
        });
        
        document.addEventListener('click', () => {
            userMenu.classList.add('hidden');
        });
        
        // Modal functionality
        const addTransactionModal = document.getElementById('addTransactionModal');
        const addTransactionBtn = document.getElementById('addTransactionBtn');
        const addIncomeBtn = document.getElementById('addIncomeBtn');
        const closeTransactionModal = document.getElementById('closeTransactionModal');
        const cancelTransaction = document.getElementById('cancelTransaction');
        
        function showTransactionModal(type = 'expense') {
            addTransactionModal.classList.remove('hidden');
            document.getElementById('transactionType').value = type;
            document.getElementById('transactionDate').value = new Date().toISOString().split('T')[0];
            loadCategories();
        }
        
        addTransactionBtn.addEventListener('click', () => showTransactionModal('expense'));
        addIncomeBtn.addEventListener('click', () => showTransactionModal('income'));
        
        [closeTransactionModal, cancelTransaction].forEach(btn => {
            btn.addEventListener('click', () => {
                addTransactionModal.classList.add('hidden');
            });
        });
        
        // Transaction form submission
        document.getElementById('transactionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const transactionData = {
                type: formData.get('type'),
                category_id: parseInt(formData.get('category_id')),
                amount: parseFloat(formData.get('amount')),
                description: formData.get('description'),
                transaction_date: formData.get('transaction_date'),
                _token: window.csrfToken
            };
            
            try {
                const response = await fetch('api/transactions.php?action=create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(transactionData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Transaction added successfully!', 'success');
                    addTransactionModal.classList.add('hidden');
                    this.reset();
                    loadDashboardData();
                } else {
                    showNotification(result.message || 'Failed to add transaction', 'error');
                }
            } catch (error) {
                console.error('Transaction error:', error);
                showNotification('Failed to add transaction', 'error');
            }
        });
        
        // Load categories
        async function loadCategories() {
            try {
                const response = await fetch('api/categories.php?action=list');
                const result = await response.json();
                
                if (result.success) {
                    categories = result.data || [];
                    updateCategorySelect();
                } else {
                    console.warn('Failed to load categories:', result.message);
                    setDefaultCategories();
                }
            } catch (error) {
                console.error('Category loading error:', error);
                setDefaultCategories();
            }
        }
        
        function setDefaultCategories() {
            categories = [
                {id: 1, name: '🏠 Rent'},
                {id: 2, name: '⚡ Electricity'},
                {id: 3, name: '🛒 Shopping'},
                {id: 4, name: '⛽ Fuel'},
                {id: 5, name: '🍽️ Food'},
                {id: 6, name: '💰 Salary'},
                {id: 7, name: '💼 Freelance'}
            ];
            updateCategorySelect();
        }
        
        function updateCategorySelect() {
            const select = document.getElementById('transactionCategory');
            select.innerHTML = categories.map(category => 
                `<option value="${category.id}">${escapeHtml(category.name)}</option>`
            ).join('');
        }
        
        // Load dashboard data
        async function loadDashboardData() {
            try {
                // Load transactions
                const transactionResponse = await fetch('api/transactions.php?action=recent&limit=5');
                const transactionResult = await transactionResponse.json();
                
                if (transactionResult.success) {
                    transactions = transactionResult.data || [];
                    updateRecentTransactionsTable();
                }
                
                // Load summary data
                const summaryResponse = await fetch('api/transactions.php?action=summary');
                const summaryResult = await summaryResponse.json();
                
                if (summaryResult.success) {
                    updateDashboardCards(summaryResult.data);
                }
                
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        }
        
        function updateDashboardCards(data) {
            const balance = (data.total_income || 0) - (data.total_expenses || 0);
            
            document.getElementById('totalBalance').textContent = formatCurrency(balance);
            document.getElementById('monthlyIncome').textContent = formatCurrency(data.total_income || 0);
            document.getElementById('monthlyExpenses').textContent = formatCurrency(data.total_expenses || 0);
            document.getElementById('savingsGoal').textContent = formatCurrency(0); // Placeholder
        }
        
        function updateRecentTransactionsTable() {
            const tbody = document.getElementById('recentTransactions');
            
            if (!transactions || transactions.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                            <p>No transactions yet.Add your first transaction!</p>
                        </td>
                    </tr>
                `;
                lucide.createIcons();
                return;
            }
            
            tbody.innerHTML = transactions.map(transaction => `
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <td class="py-3 px-4 text-gray-900 dark:text-white">
                        ${formatDate(transaction.transaction_date)}
                    </td>
                    <td class="py-3 px-4 text-gray-900 dark:text-white">
                        ${escapeHtml(transaction.description)}
                    </td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                            ${escapeHtml(transaction.category_name || 'Unknown')}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-right">
                        <span class="${transaction.type === 'income' ? 'text-green-600' : 'text-red-600'} font-medium">
                            ${transaction.type === 'income' ? '+' : '-'}${formatCurrency(transaction.amount)}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                            Completed
                        </span>
                    </td>
                </tr>
            `).join('');
            
            lucide.createIcons();
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
        
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            const bgColor = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            }[type] || 'bg-blue-500';
            
            const icon = {
                success: 'check-circle',
                error: 'alert-circle',
                warning: 'alert-triangle',
                info: 'info'
            }[type] || 'info';
            
            notification.className = `notification ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 max-w-sm`;
            notification.innerHTML = `
                <i data-lucide="${icon}" class="w-5 h-5"></i>
                <span>${escapeHtml(message)}</span>
                <button class="ml-auto" onclick="this.parentElement.remove()">
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
        
        // PWA functionality
        let deferredPrompt;
        const installPWABtn = document.getElementById('installPWA');
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installPWABtn.classList.remove('hidden');
        });
        
        installPWABtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    installPWABtn.classList.add('hidden');
                }
                deferredPrompt = null;
            }
        });
        
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(registration => console.log('SW registered'))
                .catch(error => console.log('SW registration failed'));
        }
        
        // Initialize dashboard on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboardData();
            loadCategories();
        });
    </script>
</body>
</html>