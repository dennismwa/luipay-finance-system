<?php
/**
 * LuigiTals Wallet Management System
 * Settings Page
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
    <title>Settings - LuigiTals Wallet</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
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
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Settings</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Manage your account and application preferences.</p>
        </div>

        <!-- Settings Sections -->
        <div class="space-y-8">
            <!-- Account Information -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                        <i data-lucide="user" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Account Information</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <input type="email" id="userEmail" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter your email" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                        <input type="text" id="userFullName" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Enter your full name" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Last Login</label>
                        <input type="text" value="<?php echo $user['last_login'] ? date('F j, Y \a\t g:i A', strtotime($user['last_login'])) : 'Never'; ?>" readonly class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="mt-6">
                    <button id="updateAccountBtn" class="btn-primary text-white px-6 py-2 rounded-lg font-medium">
                        Update Account
                    </button>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-4">
                        <i data-lucide="shield" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Security</h3>
                </div>
                
                <form id="passwordForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Password</label>
                        <input type="password" id="currentPassword" name="current_password" placeholder="Enter current password" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Password</label>
                        <input type="password" id="newPassword" name="new_password" placeholder="Enter new password" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm new password" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" required>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium transition-all duration-300">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preferences -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-4">
                        <i data-lucide="settings" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Preferences</h3>
                </div>
                
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Dark Mode</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Toggle between light and dark theme</p>
                        </div>
                        <button id="themeToggleSwitch" class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 dark:bg-blue-600 transition-colors">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform dark:translate-x-6"></span>
                        </button>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Default Currency</label>
                        <select id="defaultCurrency" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="KES">Kenyan Shilling (KES)</option>
                            <option value="USD">US Dollar (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                            <option value="GBP">British Pound (GBP)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Format</label>
                        <select id="dateFormat" class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                            <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                            <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email Notifications</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Receive budget alerts and summaries via email</p>
                        </div>
                        <button id="emailNotifications" class="relative inline-flex h-6 w-11 items-center rounded-full bg-blue-600 transition-colors">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-6"></span>
                        </button>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button id="savePreferencesBtn" class="btn-primary text-white px-6 py-2 rounded-lg font-medium">
                        Save Preferences
                    </button>
                </div>
            </div>

            <!-- Data Management -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 card-shadow">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center mr-4">
                        <i data-lucide="database" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Data Management</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h4 class="font-medium text-gray-900 dark:text-white">Export Data</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Download your financial data for backup or analysis.</p>
                        <button id="exportDataBtn" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition-all duration-300 flex items-center justify-center space-x-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Export All Data</span>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="font-medium text-gray-900 dark:text-white">Clear Data</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Remove all transactions while keeping categories and settings.</p>
                        <button id="clearDataBtn" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg font-medium transition-all duration-300 flex items-center justify-center space-x-2">
                            <i data-lucide="trash" class="w-4 h-4"></i>
                            <span>Clear Transactions</span>
                        </button>
                    </div>
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
        
        // Theme management
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        }
        
        document.getElementById('themeToggle').addEventListener('click', toggleTheme);
        document.getElementById('themeToggleSwitch').addEventListener('click', toggleTheme);
        
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('theme', currentTheme);
        }
        
        // Update account information
        document.getElementById('updateAccountBtn').addEventListener('click', async function() {
            const email = document.getElementById('userEmail').value;
            const fullName = document.getElementById('userFullName').value;
            
            try {
                const response = await fetch('api/user.php?action=update', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: email,
                        full_name: fullName,
                        _token: csrfToken
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Account updated successfully!', 'success');
                } else {
                    showNotification(result.message || 'Failed to update account', 'error');
                }
            } catch (error) {
                console.error('Update account error:', error);
                showNotification('Failed to update account', 'error');
            }
        });
        
        // Change password
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showNotification('New passwords do not match', 'error');
                return;
            }
            
            if (newPassword.length < 8) {
                showNotification('Password must be at least 8 characters long', 'error');
                return;
            }
            
            try {
                const response = await fetch('api/auth.php?action=change-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword,
                        confirm_password: confirmPassword,
                        _token: csrfToken
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Password changed successfully!', 'success');
                    this.reset();
                } else {
                    showNotification(result.message || 'Failed to change password', 'error');
                }
            } catch (error) {
                console.error('Change password error:', error);
                showNotification('Failed to change password', 'error');
            }
        });
        
        // Save preferences
        document.getElementById('savePreferencesBtn').addEventListener('click', function() {
            const preferences = {
                theme: document.documentElement.classList.contains('dark') ? 'dark' :'light',
               currency: document.getElementById('defaultCurrency').value,
               date_format: document.getElementById('dateFormat').value,
               email_notifications: document.getElementById('emailNotifications').classList.contains('bg-blue-600')
           };
           
           localStorage.setItem('userPreferences', JSON.stringify(preferences));
           showNotification('Preferences saved successfully!', 'success');
       });
       
       // Export data
       document.getElementById('exportDataBtn').addEventListener('click', async function() {
           try {
               const response = await fetch('api/reports.php?action=export', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify({
                       report_type: 'complete-export',
                       format: 'csv',
                       _token: csrfToken
                   })
               });
               
               const result = await response.json();
               
               if (result.success) {
                   const link = document.createElement('a');
                   link.href = result.download_url;
                   link.download = result.filename;
                   document.body.appendChild(link);
                   link.click();
                   document.body.removeChild(link);
                   
                   showNotification('Data exported successfully!', 'success');
               } else {
                   showNotification(result.message || 'Failed to export data', 'error');
               }
           } catch (error) {
               console.error('Export data error:', error);
               showNotification('Failed to export data', 'error');
           }
       });
       
       // Clear data
       document.getElementById('clearDataBtn').addEventListener('click', function() {
           if (confirm('Are you sure you want to clear all transactions? This action cannot be undone.')) {
               clearTransactions();
           }
       });
       
       async function clearTransactions() {
           try {
               const response = await fetch('api/transactions.php?action=clear-all', {
                   method: 'DELETE',
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify({
                       _token: csrfToken
                   })
               });
               
               const result = await response.json();
               
               if (result.success) {
                   showNotification('All transactions cleared successfully!', 'success');
               } else {
                   showNotification(result.message || 'Failed to clear transactions', 'error');
               }
           } catch (error) {
               console.error('Clear transactions error:', error);
               showNotification('Failed to clear transactions', 'error');
           }
       }
       
       // Toggle switches
       document.getElementById('emailNotifications').addEventListener('click', function() {
           this.classList.toggle('bg-blue-600');
           this.classList.toggle('bg-gray-300');
           const span = this.querySelector('span');
           span.classList.toggle('translate-x-6');
       });
       
       // Load saved preferences
       function loadPreferences() {
           const saved = localStorage.getItem('userPreferences');
           if (saved) {
               const preferences = JSON.parse(saved);
               
               if (preferences.currency) {
                   document.getElementById('defaultCurrency').value = preferences.currency;
               }
               if (preferences.date_format) {
                   document.getElementById('dateFormat').value = preferences.date_format;
               }
               if (preferences.email_notifications === false) {
                   const toggle = document.getElementById('emailNotifications');
                   toggle.classList.remove('bg-blue-600');
                   toggle.classList.add('bg-gray-300');
                   toggle.querySelector('span').classList.remove('translate-x-6');
               }
           }
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
       
       function escapeHtml(text) {
           const div = document.createElement('div');
           div.textContent = text;
           return div.innerHTML;
       }
       
       // Initialize page
       document.addEventListener('DOMContentLoaded', () => {
           loadPreferences();
       });
   </script>
</body>
</html>