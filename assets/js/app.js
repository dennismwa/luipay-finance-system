/**
 * LuigiTals Wallet Management System
 * Main Application JavaScript (FIXED VERSION)
 * 
 * @version 1.0.1
 * @author LuigiTals Development Team
 */

// Global application object
window.WalletApp = {
    apiBase: 'api/',
    csrfToken: window.csrfToken || null,
    user: window.currentUser || null,
    transactions: [],
    categories: [],
    
    // Initialize the application
    init: function() {
        console.log('🚀 Initializing LuigiTals Wallet...');
        
        try {
            // Initialize components
            this.initEventListeners();
            this.initTheme();
            this.initPWA();
            this.loadInitialData();
            
            console.log('✅ LuigiTals Wallet initialized successfully!');
        } catch (error) {
            console.error('❌ App initialization failed:', error);
            this.showNotification('Failed to initialize application', 'error');
        }
    },
    
    // Set up all event listeners
    initEventListeners: function() {
        console.log('🔧 Setting up event listeners...');
        
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }
        
        // User menu
        this.initUserMenu();
        
        // Transaction modal
        this.initTransactionModal();
        
        // Quick action buttons
        this.initQuickActions();
        
        console.log('✅ Event listeners initialized');
    },
    
    // Initialize user menu
    initUserMenu: function() {
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');
        
        if (userMenuBtn && userMenu) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', () => {
                userMenu.classList.add('hidden');
            });
        }
    },
    
    // Initialize transaction modal
    initTransactionModal: function() {
        const modal = document.getElementById('addTransactionModal');
        const openBtn = document.getElementById('addTransactionBtn');
        const closeBtn = document.getElementById('closeTransactionModal');
        const cancelBtn = document.getElementById('cancelTransaction');
        const form = document.getElementById('transactionForm');
        
        // Open modal
        if (openBtn && modal) {
            openBtn.addEventListener('click', () => {
                console.log('📝 Opening transaction modal...');
                modal.classList.remove('hidden');
                this.resetTransactionForm();
            });
        }
        
        // Close modal
        [closeBtn, cancelBtn].forEach(btn => {
            if (btn && modal) {
                btn.addEventListener('click', () => {
                    console.log('❌ Closing transaction modal...');
                    modal.classList.add('hidden');
                });
            }
        });
        
        // Handle form submission
        if (form) {
            form.addEventListener('submit', (e) => this.handleTransactionSubmit(e));
        }
    },
    
    // Initialize quick action buttons
    initQuickActions: function() {
        // Add Income button
        const addIncomeBtn = document.getElementById('addIncomeBtn');
        if (addIncomeBtn) {
            addIncomeBtn.addEventListener('click', () => {
                console.log('💰 Opening income transaction...');
                const modal = document.getElementById('addTransactionModal');
                modal.classList.remove('hidden');
                document.getElementById('transactionType').value = 'income';
                this.resetTransactionForm();
            });
        }
        
        // View Reports button
        const viewReportsBtn = document.getElementById('viewReportsBtn');
        if (viewReportsBtn) {
            viewReportsBtn.addEventListener('click', () => {
                console.log('📊 View reports clicked - redirecting to reports page');
                window.location.href = 'reports.php';
            });
        }
        
        // Manage Categories button
        const manageCategoriesBtn = document.getElementById('manageCategoriesBtn');
        if (manageCategoriesBtn) {
            manageCategoriesBtn.addEventListener('click', () => {
                console.log('⚙️ Manage categories clicked - redirecting to categories page');
                window.location.href = 'categories.php';
            });
        }
        
        // View All Transactions button
        const viewAllTransactionsBtn = document.getElementById('viewAllTransactionsBtn');
        if (viewAllTransactionsBtn) {
            viewAllTransactionsBtn.addEventListener('click', () => {
                console.log('📋 View all transactions clicked');
                this.showNotification('Full transaction view coming soon!', 'info');
            });
        }
    },
    
    // Reset transaction form
    resetTransactionForm: function() {
        const form = document.getElementById('transactionForm');
        if (form) {
            form.reset();
            document.getElementById('transactionDate').value = new Date().toISOString().split('T')[0];
        }
        
        // Load categories if not already loaded
        if (this.categories.length === 0) {
            this.loadCategories();
        }
    },
    
    // Handle transaction form submission
    handleTransactionSubmit: async function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        const transactionData = {
            type: formData.get('type'),
            category_id: parseInt(formData.get('category_id')),
            amount: parseFloat(formData.get('amount')),
            description: formData.get('description'),
            transaction_date: formData.get('transaction_date'),
            _token: this.csrfToken
        };
        
        console.log('💾 Submitting transaction:', transactionData);
        
        try {
            const result = await this.createTransaction(transactionData);
            if (result) {
                document.getElementById('addTransactionModal').classList.add('hidden');
                this.loadDashboardData();
            }
        } catch (error) {
            console.error('❌ Transaction submission failed:', error);
            this.showNotification('Failed to create transaction', 'error');
        }
    },
    
    // Load initial data
    loadInitialData: function() {
        console.log('📊 Loading initial dashboard data...');
        this.loadCategories();
        this.loadDashboardData();
    },
    
    // Load dashboard data
    loadDashboardData: async function() {
        try {
            console.log('📈 Loading dashboard statistics...');
            
            // For now, set some demo values
            this.updateDashboardCards({
                total_income: 0,
                total_expenses: 0,
                balance: 0
            });
            
            this.loadRecentTransactions();
            
        } catch (error) {
            console.error('❌ Failed to load dashboard data:', error);
            this.showNotification('Failed to load dashboard data', 'error');
        }
    },
    
    // Load categories
    loadCategories: async function() {
        try {
            console.log('📂 Loading categories...');
            
            const response = await fetch(`${this.apiBase}categories.php?action=list`);
            const result = await response.json();
            
            if (result.success) {
                this.categories = result.data || [];
                this.updateCategorySelects();
                console.log(`✅ Loaded ${this.categories.length} categories`);
            } else {
                console.warn('⚠️ Failed to load categories:', result.message);
                // Set default categories if API fails
                this.setDefaultCategories();
            }
        } catch (error) {
            console.error('❌ Category loading error:', error);
            this.setDefaultCategories();
        }
    },
    
    // Set default categories if API fails
    setDefaultCategories: function() {
        this.categories = [
            {id: 1, name: '🏠 Rent'},
            {id: 2, name: '⚡ Electricity'},
            {id: 3, name: '🛒 Shopping'},
            {id: 4, name: '⛽ Fuel'},
            {id: 5, name: '🍽️ Food'},
            {id: 6, name: '💰 Salary'},
            {id: 7, name: '💼 Freelance'}
        ];
        this.updateCategorySelects();
    },
    
    // Update category select options
    updateCategorySelects: function() {
        const selects = document.querySelectorAll('#transactionCategory, .category-select');
        
        selects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = this.categories.map(category => 
                `<option value="${category.id}" ${currentValue == category.id ? 'selected' : ''}>
                    ${this.escapeHtml(category.name)}
                </option>`
            ).join('');
        });
    },
    
    // Load recent transactions
    loadRecentTransactions: async function() {
        try {
            console.log('📋 Loading recent transactions...');
            
            const response = await fetch(`${this.apiBase}transactions.php?action=recent&limit=5`);
            const result = await response.json();
            
            if (result.success) {
                this.transactions = result.data || [];
                this.updateRecentTransactionsTable();
                console.log(`✅ Loaded ${this.transactions.length} recent transactions`);
            } else {
                console.warn('⚠️ Failed to load transactions:', result.message);
            }
        } catch (error) {
            console.error('❌ Transaction loading error:', error);
        }
    },
    
    // Create new transaction
    createTransaction: async function(transactionData) {
        try {
            const response = await fetch(`${this.apiBase}transactions.php?action=create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(transactionData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Transaction created successfully!', 'success');
                return true;
            } else {
                this.showNotification(result.message || 'Failed to create transaction', 'error');
                return false;
            }
        } catch (error) {
            console.error('❌ Transaction creation error:', error);
            this.showNotification('Failed to create transaction', 'error');
            return false;
        }
    },
    
    // Update dashboard cards
    updateDashboardCards: function(stats) {
        const balance = (stats.total_income || 0) - (stats.total_expenses || 0);
        
        this.updateElement('totalBalance', this.formatCurrency(balance));
        this.updateElement('monthlyIncome', this.formatCurrency(stats.total_income || 0));
        this.updateElement('monthlyExpenses', this.formatCurrency(stats.total_expenses || 0));
        this.updateElement('savingsGoal', this.formatCurrency(0)); // Placeholder
    },
    
    // Update recent transactions table
    updateRecentTransactionsTable: function() {
        const tbody = document.getElementById('recentTransactions');
        
        if (!this.transactions || this.transactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                        <p>No transactions yet. Add your first transaction!</p>
                    </td>
                </tr>
            `;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }
        
        tbody.innerHTML = this.transactions.map(transaction => `
            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <td class="py-3 px-4 text-gray-900 dark:text-white">
                    ${this.formatDate(transaction.transaction_date)}
                </td>
                <td class="py-3 px-4 text-gray-900 dark:text-white">
                    ${this.escapeHtml(transaction.description)}
                </td>
                <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                        ${this.escapeHtml(transaction.category_name || 'Unknown')}
                    </span>
                </td>
                <td class="py-3 px-4 text-right">
                    <span class="${transaction.type === 'income' ? 'text-green-600' : 'text-red-600'} font-medium">
                        ${transaction.type === 'income' ? '+' : '-'}${this.formatCurrency(transaction.amount)}
                    </span>
                </td>
                <td class="py-3 px-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                        Completed
                    </span>
                </td>
            </tr>
        `).join('');
    },
    
    // Theme management
    initTheme: function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        }
    },
    
    toggleTheme: function() {
        document.documentElement.classList.toggle('dark');
        const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        localStorage.setItem('theme', currentTheme);
        console.log(`🎨 Theme switched to: ${currentTheme}`);
    },
    
    // PWA initialization
    initPWA: function() {
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(registration => {
                    console.log('✅ Service Worker registered successfully');
                })
                .catch(error => {
                    console.error('❌ Service Worker registration failed:', error);
                });
        }
        
        // Install Prompt
        let deferredPrompt;
        const installBtn = document.getElementById('installPWA');
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (installBtn) {
                installBtn.classList.remove('hidden');
            }
        });
        
        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        installBtn.classList.add('hidden');
                    }
                    deferredPrompt = null;
                }
            });
        }
    },
    
    // Utility functions
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-KE', {
            style: 'currency',
            currency: 'KES'
        }).format(amount || 0);
    },
    
    formatDate: function(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    },
    
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    updateElement: function(id, content) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = content;
        }
    },
    
    showNotification: function(message, type = 'info') {
        const container = document.getElementById('notificationContainer');
        if (!container) {
            console.log(`📢 ${type.toUpperCase()}: ${message}`);
            return;
        }
        
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
            <span>${this.escapeHtml(message)}</span>
            <button class="ml-auto" onclick="this.parentElement.remove()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        `;
        
        container.appendChild(notification);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
        
        console.log(`📢 ${type.toUpperCase()}: ${message}`);
    }
};

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🌟 DOM loaded, initializing wallet app...');
    WalletApp.init();
});

// Initialize Lucide icons after DOM load
if (typeof lucide !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });
}

// Global error handlers
window.addEventListener('error', (e) => {
    console.error('🚨 Global error:', e.error);
    if (window.WalletApp) {
        WalletApp.showNotification('An unexpected error occurred', 'error');
    }
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('🚨 Unhandled promise rejection:', e.reason);
    if (window.WalletApp) {
        WalletApp.showNotification('An unexpected error occurred', 'error');
    }
});