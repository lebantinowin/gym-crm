<?php
// admin/users.php - User Management
require_once '../auth.php';
require_once '../config.php';

require_admin();

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    if ($user_id !== $_SESSION['user_id']) { // Prevent self-deletion
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        header('Location: users.php?deleted=1');
        exit();
    }
}

// Handle role change
if (isset($_GET['toggle_role'])) {
    $user_id = intval($_GET['toggle_role']);
    if ($user_id !== $_SESSION['user_id']) { // Prevent self-role change
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_role = $stmt->fetchColumn();
        
        $new_role = ($current_role === 'admin') ? 'user' : 'admin';
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
        
        header('Location: users.php?role_changed=1');
        exit();
    }
}

// Get all users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - User Management</title>
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
        .user-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-primary text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-dumbbell text-highlight text-2xl"></i>
                    <h1 class="text-xl font-bold">Warzone Admin</h1>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="hover:text-highlight transition font-semibold">Dashboard</a>
                    <a href="users.php" class="hover:text-highlight transition">Users</a>
                    <a href="reports.php" class="hover:text-highlight transition">Reports</a>
                    <a href="messages.php" class="hover:text-highlight transition">Messages</a>
                    <!-- <a href="../dashboard.php" class="hover:text-highlight transition">Member View</a> -->
                </div>
                <div class="flex items-center space-x-4">
                    <!-- <div class="relative">
                        <i class="fas fa-bell text-xl cursor-pointer"></i>
                        <span class="absolute top-0 right-0 bg-highlight text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                    </div> -->
                    <div class="flex items-center space-x-2 cursor-pointer">
                        <img src="<?php echo file_exists('uploads/' . $_SESSION['user_profile_picture']) ? 'uploads/' . $_SESSION['user_profile_picture'] : 'uploads/default.png'; ?>" alt="Profile" class="rounded-full w-10 h-10">
                        <!-- <span class="hidden md:inline"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span> -->
                        <a href="../logout.php" 
                        class="text-gray-400 hover:text-highlight transition ml-1 opacity-75 group-hover:opacity-100" 
                        title="Logout">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">User Management</h2>
            <a href="../register.php" class="btn-primary px-4 py-2 rounded-lg text-sm flex items-center">
                <i class="fas fa-plus mr-2"></i> Add New User
            </a>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 p-3 bg-green-100 text-green-700 rounded-lg">
                User deleted successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['role_changed'])): ?>
            <div class="mb-6 p-3 bg-green-100 text-green-700 rounded-lg">
                User role updated successfully!
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member Since</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
    <div class="flex items-center">
        <div class="flex-shrink-0 h-10 w-10">
            <img class="h-10 w-10 rounded-full object-cover"
                 src="<?= htmlspecialchars(
                     file_exists('../uploads/' . ($user['profile_picture'] ?? 'default.png'))
                         ? '../uploads/' . $user['profile_picture']
                         : '../uploads/default.png'
                 ) ?>"
                 alt="">
        </div>
        <div class="ml-4">
            <div class="text-sm font-medium text-gray-900">
                <?= htmlspecialchars($user['name']) ?>
            </div>
        </div>
    </div>
</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $user['role'] === 'admin' ? 'bg-highlight text-white' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <a href="users.php?toggle_role=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <?php echo $user['role'] === 'admin' ? 'Demote' : 'Promote'; ?>
                                </a>
                                <a href="users.php?delete=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this user?')">
                                    Delete
                                </a>
                                <?php else: ?>
                                <span class="text-gray-400">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="bg-primary text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="text-center text-gray-400 text-sm">
                <p>© 2026 Warzone Gym CRM. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <!-- <?php include 'modal_logout.php'; ?> -->
</body>
</html>