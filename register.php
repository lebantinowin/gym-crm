<?php
// register.php — Unified with Warzone CRM interface
require_once 'auth.php';

if (is_logged_in()) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Register</title>
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
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .gradient-border {
            position: relative;
            border-radius: 0.75rem;
        }
        .gradient-border::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, #e94560, #0f3460, #16213e);
            border-radius: 0.85rem;
            z-index: -1;
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
                    <a href="login.php" class="hover:text-highlight transition">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <div class="gradient-border">
                <div class="bg-white rounded-xl shadow p-8">
                    <div class="text-center mb-8">
                        <div class="flex justify-center mb-4">
                            <i class="fas fa-dumbbell text-highlight text-4xl"></i>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-800">Join Warzone</h1>
                        <p class="text-gray-600">Start your fitness journey with AI coaching</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <!-- Profile Picture -->
                        <div class="text-center">
                            <img id="profilePreview"
                                 src="uploads/default.png"
                                 alt="Profile Preview"
                                 class="w-24 h-24 rounded-full object-cover border-4 border-highlight mx-auto mb-4">
                            <label class="inline-block px-4 py-2 bg-highlight text-white rounded-lg text-sm font-medium hover:bg-opacity-90 transition cursor-pointer">
                                <i class="fas fa-upload mr-2"></i>Upload Photo
                                <input type="file" name="profile_picture" id="profilePicture" class="hidden" accept="image/*">
                            </label>
                            <p class="text-xs text-gray-500 mt-2">JPG, PNG, or GIF (max 5MB)</p>
                        </div>

                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text"
                                   name="name"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight focus:border-transparent"
                                   placeholder="Full Name"
                                   required>
                        </div>

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
                                   placeholder="Password (min 6 chars)"
                                   required>
                        </div>

                        <div class="relative">
                            <i class="fas fa-bullseye absolute left-3 top-3 transform text-gray-400"></i>
                            <textarea name="goal"
                                      rows="2"
                                      class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight focus:border-transparent resize-none"
                                      placeholder="What are your fitness goals? (Optional)"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="relative">
                                <i class="fas fa-weight-hanging absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="number" step="0.1" name="weight"
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight focus:border-transparent"
                                       placeholder="Weight (kg)">
                            </div>
                            <div class="relative">
                                <i class="fas fa-ruler-vertical absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="number" step="0.1" name="height"
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight focus:border-transparent"
                                       placeholder="Height (cm)">
                            </div>
                        </div>

                        <button type="submit"
                                name="register"
                                class="w-full btn-primary py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                            <i class="fas fa-user-plus mr-2"></i>Create Account
                        </button>
                    </form>

                    <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                        <p class="text-gray-600 text-sm">Already have an account?</p>
                        <a href="login.php" class="text-highlight font-semibold hover:underline">
                            Login here
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('profilePicture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.match('image.*')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profilePreview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>

</body>
</html>