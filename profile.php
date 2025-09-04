<?php
/**
 * LuigiTals Wallet Management System
 * Profile Page
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
    <title>Profile - LuigiTals Wallet</title>
    
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
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Profile Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-8 card-shadow mb-8">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
                <!-- Profile Picture -->
                <div class="relative">
                    <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-4xl font-bold text-white">
                        <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                    </div>
                    <button class="absolute bottom-0 right-0 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white hover:bg-blue-700 transition-colors">
                        <i data-lucide="camera" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <!-- Profile Info -->
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <div class="flex flex-wrap justify-center md:justify-start gap-4 mb-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-lg">
                            <div class="text-blue-600 dark:text-blue-400 text-sm font-medium">Member Since</div>
                            <div class="text-blue-800 dark:text-blue-300 font-semibold">
                                <?php echo date('F Y', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 dark:bg-green-900/20 px-4 py-2 rounded-lg">
                            <div class="text-green-600 dark:text-green-400 text-sm font-medium">Last Login</div>
                            <div class="text-green-800 dark:text-green-300 font-semibold">
                                <?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap justify-center md:justify-start gap-3">
                        <a href="settings.php" class="btn-primary text-white px-6 py-2 rounded-lg font-medium flex items-center space-x-2">
                            <i data-lucide="settings" class="w-4 h-4"></i>
                            <span>Edit Profile</span>
                        </a>
                        
                        <button id="shareProfileBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium flex items-center space-x-2 transition-all duration-300">
                            <i data-lucide="share-2" class="w-4 h-4"></i>
                            <span>Share</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow text-center">
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="receipt" class="w-6 h-6 text-white"></i>
                </div>
                <div id="totalTransactions" class="text-2xl font-bold text-gray-900 dark:text-white mb-1">0</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Transactions</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow text-center">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="trending-up" class="w-6 h-6 text-white"></i>
                </div>
                <div id="totalIncome" class="text-2xl font-bold text-gray-900 dark:text-white mb-1">KSh 0</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Income</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow text-center">
                <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="trending-down" class="w-6 h-6 text-white"></i>
                </div>
                <div id="totalExpenses" class="text-2xl font-bold text-gray-900 dark:text-white mb-1">KSh 0</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Expenses</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow text-center">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="folder" class="w-6 h-6 text-white"></i>
                </div>
                <div id="totalCategories" class="text-2xl font-bold text-gray-900 dark:text-white mb-1">0</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Categories</div>
            </div>
        </div>

        <!-- Charts and Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Monthly Trends -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Trends</h3>
                <div class="h-64">
                    <canvas id="monthlyTrendsChart"></canvas>
                </div>
            </div>
            
            <!-- Category Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Category Breakdown</h3>
                <div class="h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
                <a href="transactions.php" class="text-blue-600 hover:text-blue-700 font-medium">View All</a>
            </div>
            
            <div id="recentActivity" class="space-y-4">
                <!-- Activity items will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="fixed top-20 right-4 z-50 space-y-2"></div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
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
        
        // Global variables
        let monthlyTrendsChart = null;
        let categoryChart = null;
        
        // Load profile data
        async function loadProfileData() {
            try {
                // Load user statistics
                const statsResponse = await fetch('api/transactions.php?action=statistics&period=year');
                const statsResult = await statsResponse.json();
                
                if (statsResult.success) {
                    updateProfileStats(statsResult.data);
                }
                
                // Load trends data
                const trendsResponse = await fetch('api/transactions.php?action=trends&months=6');
                const trendsResult = await trendsResponse.json();
                
                if (trendsResult.success) {
                    createMonthlyTrendsChart(trendsResult.data);
                }
                
                // Load category statistics
                const categoryResponse = await fetch('api/categories.php?action=statistics&period=year');
                const categoryResult = await categoryResponse.json();
                
                if (categoryResult.success) {
                    createCategoryChart(categoryResult.data);
                }
                
                // Load recent activity
                loadRecentActivity();
                
            } catch (error) {
                console.error('Failed to load profile data:', error);
            }
        }
        
        function updateProfileStats(data) {
            document.getElementById('totalTransactions').textContent = data.totals.total_count || 0;
            document.getElementById('totalIncome').textContent = formatCurrency(data.totals.income || 0);
            document.getElementById('totalExpenses').textContent = formatCurrency(data.totals.expenses || 0);
            document.getElementById('totalCategories').textContent = data.by_category?.length || 0;
        }
        
        function createMonthlyTrendsChart(data) {
            const ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
            
            // Process data for chart
            const months = [];
            const income = [];
            const expenses = [];
            
            // Group by month
            const monthlyData = {};
            data.forEach(item => {
                const monthKey = `${item.year}-${String(item.month).padStart(2, '0')}`;
                if (!monthlyData[monthKey]) {
                    monthlyData[monthKey] = { income: 0, expenses: 0 };
                }
                monthlyData[monthKey][item.type === 'income' ? 'income' : 'expenses'] += parseFloat(item.total_amount);
            });
            
            // Sort by date and prepare arrays
            Object.keys(monthlyData).sort().forEach(monthKey => {
                const [year, month] = monthKey.split('-');
                const monthName = new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                months.push(monthName);
                income.push(monthlyData[monthKey].income);
                expenses.push(monthlyData[monthKey].expenses);
            });
            
            monthlyTrendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Income',
                        data: income,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Expenses',
                        data: expenses,
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280',
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB'
                            }
                        },
                        x: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280'
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB'
                            }
                        }
                    }
                }
            });
        }
        
        function createCategoryChart(data) {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            
            // Process expense categories only
            const expenseCategories = data.filter(item => item.transaction_type === 'expense' && item.total_amount > 0);
            const labels = expenseCategories.map(item => item.name);
            const amounts = expenseCategories.map(item => parseFloat(item.total_amount));
            const colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'];
            
            categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: amounts,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: document.documentElement.classList.contains('dark') ? '#1F2937' : '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151',
                                padding: 20
                            }
                        }
                    }
                }
            });
        }
        
        async function loadRecentActivity() {
            try {
                const response = await fetch('api/transactions.php?action=recent&limit=10');
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    renderRecentActivity(result.data);
                } else {
                    document.getElementById('recentActivity').innerHTML = `
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i data-lucide="activity" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                            <p>No recent activity</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Failed to load recent activity:', error);
            }
        }
        
        function renderRecentActivity(activities) {
            const container = document.getElementById('recentActivity');
            
            container.innerHTML = activities.map(activity => `
                <div class="flex items-center space-x-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <div class="w-10 h-10 ${activity.type === 'income' ? 'bg-green-500' : 'bg-red-500'} rounded-full flex items-center justify-center">
                        <i data-lucide="${activity.type === 'income' ? 'arrow-up' : 'arrow-down'}" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-white">${escapeHtml(activity.description)}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">${escapeHtml(activity.category_name)} • ${formatDate(activity.transaction_date)}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold ${activity.type === 'income' ? 'text-green-600' : 'text-red-600'}">
                            ${activity.type === 'income' ? '+' : '-'}${formatCurrency(activity.amount)}
                        </div>
                    </div>
                </div>
            `).join('');
            
            lucide.createIcons();
        }
        
        // Share profile functionality
        document.getElementById('shareProfileBtn').addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: 'My LuigiTals Wallet Profile',
                    text: 'Check out my financial management with LuigiTals Wallet!',
                    url: window.location.href
                });
            } else {
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showNotification('Profile link copied to clipboard!', 'success');
                });
            }
        });
        
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
                day: 'numeric'
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
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            loadProfileData();
        });
    </script>
</body>
</html>