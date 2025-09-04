<?php
/**
 * LuigiTals Wallet Management System
 * Reports Page
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
    <title>Reports - LuigiTals Wallet</title>
    
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
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .report-card {
            transition: all 0.3s ease;
        }
        
        .report-card:hover {
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
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Financial Reports</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Generate comprehensive financial reports and analytics.</p>
        </div>

        <!-- Report Types -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Income Statement -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow report-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full">Popular</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Income Statement</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Compare income vs expenses over a selected period.</p>
                <button onclick="generateReport('income-statement')" class="w-full btn-primary text-white py-2 rounded-lg text-sm font-medium">
                    Generate Report
                </button>
            </div>

            <!-- Expense Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow report-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="pie-chart" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Expense Breakdown</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Detailed analysis of spending by category.</p>
                <button onclick="generateReport('expense-breakdown')" class="w-full btn-primary text-white py-2 rounded-lg text-sm font-medium">
                    Generate Report
                </button>
            </div>

            <!-- Budget Analysis -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow report-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="target" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Budget Analysis</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Compare actual spending vs budgets.</p>
                <button onclick="generateReport('budget-analysis')" class="w-full btn-primary text-white py-2 rounded-lg text-sm font-medium">
                    Generate Report
                </button>
            </div>

            <!-- Monthly Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow report-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Monthly Summary</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Complete overview of a specific month.</p>
                <button onclick="generateReport('monthly-summary')" class="w-full btn-primary text-white py-2 rounded-lg text-sm font-medium">
                    Generate Report
                </button>
            </div>

            <!-- Trend Analysis -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow report-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="activity" class="w-6 h-6 text-white"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Trend Analysis</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Track financial trends over time.</p>
                <button onclick="generateReport('trend-analysis')" class="w-full btn-primary text-white py-2 rounded-lg text-sm font-medium">
                    Generate Report
                </button>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow report-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="zap" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-2 py-1 rounded-full">Quick</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Quick Stats</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Instant overview of key metrics.</p>
                <button onclick="generateReport('quick-stats')" class="w-full btn-primary text-white py-2 rounded-lg text-sm font-medium">
                    View Stats
                </button>
            </div>
        </div>

        <!-- Report Display Area -->
        <div id="reportDisplay" class="hidden">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="reportTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Report</h3>
                    <div class="flex space-x-2">
                        <button id="exportCsvBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center space-x-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Export CSV</span>
                        </button>
                        <button id="closeReportBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                
                <div id="reportContent" class="space-y-6">
                    <!-- Report content will be dynamically inserted here -->
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-8 flex items-center space-x-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="text-gray-900 dark:text-white font-medium">Generating report...</span>
            </div>
        </div>
    </div>

    <!-- Date Range Modal -->
    <div id="dateRangeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select Date Range</h3>
                <button id="closeDateModal" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                    <input type="date" id="startDate" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                    <input type="date" id="endDate" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="flex space-x-4 mt-6">
                    <button type="button" id="cancelDateRange" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                    <button type="button" id="confirmDateRange" class="flex-1 btn-primary text-white px-4 py-2 rounded-lg">Generate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="fixed top-20 right-4 z-50 space-y-2"></div>

    <script>
    // Initialize Lucide icons
        lucide.createIcons();
        
        // Global variables
        const csrfToken = '<?php echo $auth->generateCsrfToken(); ?>';
        let currentReportType = '';
        
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
        
        // Report generation function
        async function generateReport(reportType) {
            currentReportType = reportType;
            
            // Check if report requires date range
            const requiresDateRange = ['income-statement', 'expense-breakdown'].includes(reportType);
            
            if (requiresDateRange) {
                showDateRangeModal();
                return;
            }
            
            // Generate report immediately for other types
            await executeReportGeneration(reportType);
        }
        
        // Show date range modal
        function showDateRangeModal() {
            const modal = document.getElementById('dateRangeModal');
            const today = new Date().toISOString().split('T')[0];
            const firstDayOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
            
            document.getElementById('startDate').value = firstDayOfMonth;
            document.getElementById('endDate').value = today;
            modal.classList.remove('hidden');
        }
        
        // Execute report generation
        async function executeReportGeneration(reportType, parameters = {}) {
            try {
                showLoading();
                
                let url = `api/reports.php?action=${reportType}`;
                
                // Add parameters to URL
                const urlParams = new URLSearchParams(parameters);
                if (urlParams.toString()) {
                    url += '&' + urlParams.toString();
                }
                
                const response = await fetch(url);
                const result = await response.json();
                
                hideLoading();
                
                if (result.success) {
                    displayReport(result.data);
                } else {
                    showNotification(result.message || 'Failed to generate report', 'error');
                }
            } catch (error) {
                hideLoading();
                console.error('Report generation error:', error);
                showNotification('Failed to generate report', 'error');
            }
        }
        
        // Display report
        function displayReport(reportData) {
            const reportDisplay = document.getElementById('reportDisplay');
            const reportTitle = document.getElementById('reportTitle');
            const reportContent = document.getElementById('reportContent');
            
            reportTitle.textContent = reportData.title || 'Report';
            reportContent.innerHTML = formatReportContent(reportData);
            reportDisplay.classList.remove('hidden');
            
            // Scroll to report
            reportDisplay.scrollIntoView({ behavior: 'smooth' });
            
            // Re-initialize icons
            lucide.createIcons();
        }
        
        // Format report content based on type
        function formatReportContent(data) {
            switch (data.title) {
                case 'Income Statement':
                    return formatIncomeStatement(data);
                case 'Expense Breakdown':
                    return formatExpenseBreakdown(data);
                case 'Budget Analysis':
                    return formatBudgetAnalysis(data);
                case 'Monthly Summary':
                    return formatMonthlySummary(data);
                case 'Trend Analysis':
                    return formatTrendAnalysis(data);
                default:
                    return '<p class="text-gray-600 dark:text-gray-400">Report data not available.</p>';
            }
        }
        
        // Format income statement
        function formatIncomeStatement(data) {
            return `
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <div class="text-green-600 text-sm font-medium">Total Income</div>
                        <div class="text-2xl font-bold text-green-700 dark:text-green-400">${formatCurrency(data.summary.total_income)}</div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="text-red-600 text-sm font-medium">Total Expenses</div>
                        <div class="text-2xl font-bold text-red-700 dark:text-red-400">${formatCurrency(data.summary.total_expenses)}</div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <div class="text-blue-600 text-sm font-medium">Net Income</div>
                        <div class="text-2xl font-bold ${data.summary.net_income >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'}">${formatCurrency(data.summary.net_income)}</div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Income Sources</h4>
                        ${data.income.length > 0 ? formatCategoryList(data.income) : '<p class="text-gray-500">No income recorded</p>'}
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Expense Categories</h4>
                        ${data.expenses.length > 0 ? formatCategoryList(data.expenses) : '<p class="text-gray-500">No expenses recorded</p>'}
                    </div>
                </div>
            `;
        }
        
        
        // Format expense breakdown
        function formatExpenseBreakdown(data) {
            return `
                <div class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <div class="text-blue-600 text-sm font-medium">Total Expenses</div>
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">${formatCurrency(data.summary.total_expenses)}</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                            <div class="text-purple-600 text-sm font-medium">Categories</div>
                            <div class="text-2xl font-bold text-purple-700 dark:text-purple-400">${data.summary.category_count}</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                            <div class="text-green-600 text-sm font-medium">Avg per Category</div>
                            <div class="text-2xl font-bold text-green-700 dark:text-green-400">${formatCurrency(data.summary.avg_per_category)}</div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Expense Breakdown by Category</h4>
                    ${data.categories.length > 0 ? formatExpenseCategories(data.categories) : '<p class="text-gray-500">No expenses recorded</p>'}
                </div>
            `;
        }
        
        // Format budget analysis
        function formatBudgetAnalysis(data) {
            return `
                <div class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <div class="text-blue-600 text-sm font-medium">Total Budgets</div>
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">${data.summary.total_budgets}</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                            <div class="text-green-600 text-sm font-medium">On Track</div>
                            <div class="text-2xl font-bold text-green-700 dark:text-green-400">${data.summary.on_track}</div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                            <div class="text-yellow-600 text-sm font-medium">Warning</div>
                            <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">${data.summary.warning}</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                            <div class="text-red-600 text-sm font-medium">Over Budget</div>
                            <div class="text-2xl font-bold text-red-700 dark:text-red-400">${data.summary.over_budget}</div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Budget Performance</h4>
                    ${data.budgets.length > 0 ? formatBudgetList(data.budgets) : '<p class="text-gray-500">No budgets configured</p>'}
                </div>
            `;
        }
        
        // Format monthly summary
        function formatMonthlySummary(data) {
            return `
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">${data.period.month_name} ${data.period.year} Summary</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                            <div class="text-green-600 text-sm font-medium">Income</div>
                            <div class="text-2xl font-bold text-green-700 dark:text-green-400">${formatCurrency(data.summary.income)}</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                            <div class="text-red-600 text-sm font-medium">Expenses</div>
                            <div class="text-2xl font-bold text-red-700 dark:text-red-400">${formatCurrency(data.summary.expenses)}</div>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <div class="text-blue-600 text-sm font-medium">Net</div>
                            <div class="text-2xl font-bold ${data.summary.net >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'}">${formatCurrency(data.summary.net)}</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                            <div class="text-purple-600 text-sm font-medium">Transactions</div>
                            <div class="text-2xl font-bold text-purple-700 dark:text-purple-400">${data.summary.transaction_count}</div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Categories</h4>
                        ${data.top_categories.length > 0 ? formatCategoryList(data.top_categories) : '<p class="text-gray-500">No transactions recorded</p>'}
                    </div>
                </div>
            `;
        }
        
        // Format trend analysis
        function formatTrendAnalysis(data) {
            return `
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Trend Analysis - ${data.period}</h4>
                    ${data.summary.best_month ? `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                                <div class="text-green-600 text-sm font-medium">Best Month</div>
                                <div class="text-lg font-bold text-green-700 dark:text-green-400">${data.summary.best_month.month_name}</div>
                                <div class="text-sm text-green-600">${formatCurrency(data.summary.best_month.net)} net</div>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                                <div class="text-red-600 text-sm font-medium">Worst Month</div>
                                <div class="text-lg font-bold text-red-700 dark:text-red-400">${data.summary.worst_month.month_name}</div>
                                <div class="text-sm text-red-600">${formatCurrency(data.summary.worst_month.net)} net</div>
                            </div>
                        </div>
                    ` : ''}
                </div>
                
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Monthly Breakdown</h4>
                    ${data.monthly_data.length > 0 ? formatMonthlyData(data.monthly_data) : '<p class="text-gray-500">No data available</p>'}
                </div>
            `;
        }
        
        // Helper formatting functions
        function formatCategoryList(categories) {
            return `
                <div class="space-y-3">
                    ${categories.map(cat => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg">${cat.category_icon || '📊'}</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">${cat.category_name}</div>
                                    <div class="text-sm text-gray-500">${cat.transaction_count} transactions</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900 dark:text-white">${formatCurrency(cat.total_amount)}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function formatExpenseCategories(categories) {
            return `
                <div class="space-y-3">
                    ${categories.map(cat => `
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg">${cat.category_icon || '📊'}</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">${cat.category_name}</div>
                                    <div class="text-sm text-gray-500">${cat.transaction_count} transactions • ${parseFloat(cat.percentage || 0).toFixed(1)}%</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900 dark:text-white">${formatCurrency(cat.total_amount)}</div>
                                <div class="text-sm text-gray-500">Avg: ${formatCurrency(cat.avg_amount)}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function formatBudgetList(budgets) {
            return `
                <div class="space-y-3">
                    ${budgets.map(budget => `
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-3">
                                    <span class="text-lg">${budget.category_icon || '📊'}</span>
                                    <div class="font-medium text-gray-900 dark:text-white">${budget.category_name}</div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${getBudgetStatusColor(budget.status)}">
                                        ${budget.status.replace('_', ' ').toUpperCase()}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    ${formatCurrency(budget.actual_spending)} of ${formatCurrency(budget.budget_amount)}
                                </div>
                                <div class="text-sm font-medium ${budget.usage_percentage > 100 ? 'text-red-600' : 'text-green-600'}">
                                    ${parseFloat(budget.usage_percentage || 0).toFixed(1)}%
                                </div>
                            </div>
                            <div class="mt-2 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: ${Math.min(100, budget.usage_percentage || 0)}%"></div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function formatMonthlyData(monthlyData) {
            return `
                <div class="space-y-3">
                    ${monthlyData.map(month => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="font-medium text-gray-900 dark:text-white">${month.month_name}</div>
                            <div class="flex items-center space-x-4 text-sm">
                                <div class="text-green-600">+${formatCurrency(month.income)}</div>
                                <div class="text-red-600">-${formatCurrency(month.expenses)}</div>
                                <div class="font-bold ${month.net >= 0 ? 'text-green-600' : 'text-red-600'}">${formatCurrency(month.net)}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function getBudgetStatusColor(status) {
            switch (status) {
                case 'on_track': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                case 'warning': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                case 'over_budget': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
            }
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-KE', {
                style: 'currency',
                currency: 'KES'
            }).format(amount || 0);
        }
        
        // Loading functions
        function showLoading() {
            document.getElementById('loadingState').classList.remove('hidden');
        }
        
        function hideLoading() {
            document.getElementById('loadingState').classList.add('hidden');
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
                <span>${message}</span>
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
        
        // Event listeners
        document.getElementById('closeDateModal').addEventListener('click', () => {
            document.getElementById('dateRangeModal').classList.add('hidden');
        });
        
        document.getElementById('cancelDateRange').addEventListener('click', () => {
            document.getElementById('dateRangeModal').classList.add('hidden');
        });
        
        document.getElementById('confirmDateRange').addEventListener('click', async () => {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (!startDate || !endDate) {
                showNotification('Please select both start and end dates', 'error');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                showNotification('Start date must be before end date', 'error');
                return;
            }
            
            document.getElementById('dateRangeModal').classList.add('hidden');
            
            await executeReportGeneration(currentReportType, {
                start_date: startDate,
                end_date: endDate
            });
        });
        
        document.getElementById('closeReportBtn').addEventListener('click', () => {
            document.getElementById('reportDisplay').classList.add('hidden');
        });
        
        document.getElementById('exportCsvBtn').addEventListener('click', async () => {
            try {
                showLoading();
                
                const response = await fetch('api/reports.php?action=export', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        report_type: currentReportType,
                        format: 'csv',
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
                    
                    showNotification('Report exported successfully!', 'success');
                } else {
                    showNotification(result.message || 'Failed to export report', 'error');
                }
            } catch (error) {
                hideLoading();
                console.error('Export error:', error);
                showNotification('Failed to export report', 'error');
            }
        });
    </script>
</body>
</html>