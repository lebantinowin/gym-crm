<?php
// login.php — Unified with Warzone CRM interface
require_once 'auth.php';

if (is_logged_in()) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin/index.php' : 'dashboard.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid session. Please refresh and try again.";
    } else {
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        if (login($email, $password)) {
            log_activity($_SESSION['user_id'], 'login', "User logged in successfully.");
            header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin/index.php' : 'dashboard.php'));
            exit();
        } else {
            if (!isset($_SESSION['error'])) {
                $_SESSION['error'] = "Invalid email or password.";
            }
        }
    }
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Login</title>
    <!-- ✅ Clean CDN URLs -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a2e',
                        secondary: '#16213e',
                        accent: '#0f3460',
                        highlight: '#e94560'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #1a1a2e;
            --secondary: #16213e;
            --highlight: #e94560;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8f9fa;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-primary {
            background: linear-gradient(45deg, #e94560, #0f3460);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(233, 69, 96, 0.3);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- ✅ EXACT HEADER FROM dashboard.php -->
    <nav class="bg-primary text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-dumbbell text-highlight text-2xl"></i>
                    <h1 class="text-xl font-bold">Warzone Gym CRM</h1>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="hover:text-highlight transition">Home</a>
                    <a href="login.php" class="hover:text-highlight transition font-semibold">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <div class="gradient-border">
                <div class="bg-white rounded-xl shadow p-8">
                    <div class="text-center mb-8">
                        <div class="flex justify-center mb-4">
                            <i class="fas fa-dumbbell text-highlight text-4xl"></i>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-800">Welcome Back</h1>
                        <p class="text-gray-600">Log in to your Warzone account</p>
                    </div>

                    <?php 
                    $disp_error = $_SESSION['error'] ?? null;
                    if ($disp_error): unset($_SESSION['error']); ?>
                        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                            <?= htmlspecialchars($disp_error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <?= csrf_field() ?>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="email"
                                   name="email"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight focus:border-transparent"
                                   placeholder="Email"
                                   required>
                        </div>

                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="password"
                                   name="password"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight focus:border-transparent"
                                   placeholder="Password"
                                   required>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit"
                                    name="login"
                                    class="w-full btn-primary py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                                <i class="fas fa-sign-in-alt mr-2"></i>Login to Warzone
                            </button>
                        </div>
                        <div class="text-center mt-2">
                            <a href="forgot_password.php" class="text-sm text-gray-500 hover:text-highlight transition">Forgot password?</a>
                        </div>
                    </form>

                    <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                        <p class="text-gray-600 text-sm">Don’t have an account?</p>
                        <a href="register.php" class="text-highlight font-semibold hover:underline">
                            Register now
                        </a>
                    </div>

                    <div class="mt-6 text-center text-xs text-gray-500">
                        <p>Default Admin: <code>admin@gymcrm.com</code> / <code>admin123</code></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>