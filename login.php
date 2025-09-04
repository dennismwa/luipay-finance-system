<?php
/**
 * LuigiTals Wallet Management System
 * Login Page
 * 
 * @version 1.0.0
 * @author LuigiTals Development Team
 */

define('APP_ROOT', __DIR__);
require_once 'classes/Auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isAuthenticated()) {
    header('Location: index.html');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    $result = $auth->login($username, $password, $rememberMe);
    
    if ($result['success']) {
        $success = $result['message'];
        // Redirect after successful login
        header('refresh:2;url=index.html');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LuigiTals Wallet</title>
    
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
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glassmorphism {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
        
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .input-field {
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .floating-elements::before,
        .floating-elements::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-elements::before {
            width: 200px;
            height: 200px;
            background: #3b82f6;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-elements::after {
            width: 150px;
            height: 150px;
            background: #8b5cf6;
            bottom: 10%;
            right: 10%;
            animation-delay: 3s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient flex items-center justify-center p-4">
    <div class="floating-elements"></div>
    
    <div class="relative z-10 w-full max-w-md animate-fade-in">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl">
                <i data-lucide="wallet" class="w-10 h-10 text-blue-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">LuigiTals Wallet</h1>
            <p class="text-white/80">Premium Financial Management</p>
        </div>
        
        <!-- Login Form -->
        <div class="glassmorphism rounded-2xl p-8 shadow-2xl">
            <h2 class="text-2xl font-bold text-white text-center mb-6">Welcome Back</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/30 text-white px-4 py-3 rounded-lg mb-4 flex items-center">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500/30 text-white px-4 py-3 rounded-lg mb-4 flex items-center">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <div class="ml-auto">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="username" class="block text-white/90 text-sm font-medium mb-2">
                        <i data-lucide="user" class="w-4 h-4 inline mr-2"></i>
                        Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        class="input-field w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-white/60 focus:outline-none focus:border-white/60"
                        placeholder="Enter your username"
                    >
                </div>
                
                <div>
                    <label for="password" class="block text-white/90 text-sm font-medium mb-2">
                        <i data-lucide="lock" class="w-4 h-4 inline mr-2"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="input-field w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-white/60 focus:outline-none focus:border-white/60 pr-12"
                            placeholder="Enter your password"
                        >
                        <button 
                            type="button" 
                            id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white/60 hover:text-white transition-colors"
                        >
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-white/90 text-sm">
                        <input 
                            type="checkbox" 
                            name="remember_me" 
                            class="w-4 h-4 text-blue-600 bg-white/20 border-white/30 rounded focus:ring-blue-500 focus:ring-2"
                        >
                        <span class="ml-2">Remember me</span>
                    </label>
                    
                    <a href="#" class="text-white/80 hover:text-white text-sm transition-colors">
                        Forgot password?
                    </a>
                </div>
                
                <button 
                    type="submit" 
                    class="btn-primary w-full py-3 text-white font-semibold rounded-lg flex items-center justify-center space-x-2"
                >
                    <i data-lucide="log-in" class="w-5 h-5"></i>
                    <span>Sign In</span>
                </button>
            </form>
            
            <!-- Demo Credentials -->
            <div class="mt-6 p-4 bg-white/10 rounded-lg border border-white/20">
                <h3 class="text-white font-medium mb-2 flex items-center">
                    <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                    Demo Credentials
                </h3>
                <div class="text-white/80 text-sm space-y-1">
                    <p><strong>Username:</strong> Lui</p>
                    <p><strong>Password:</strong> Luigitals#2000</p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-white/60 text-sm">
            <p>&copy; 2024 LuigiTals Development. All rights reserved.</p>
            <p class="mt-1">Professional Financial Management System</p>
        </div>
    </div>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordField.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            
            lucide.createIcons();
        });
        
        // Auto-focus on username field
        document.getElementById('username').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
        });
        
        // Add loading state to submit button
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = `
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                <span>Signing In...</span>
            `;
            submitBtn.disabled = true;
        });
        
        // Auto-hide success message and redirect
        <?php if ($success): ?>
        setTimeout(function() {
            window.location.href = 'index.html';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>