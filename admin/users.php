<?php
// admin/users.php - User Management
require_once '../auth.php';

require_admin();

// 🛡️ Handle user actions (POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }

    // Handle user deletion
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        if ($user_id !== $_SESSION['user_id']) { // Prevent self-deletion
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // 📝 Audit Log
            log_activity($_SESSION['user_id'], 'User Deleted', "Deleted user ID: $user_id");
            
            header('Location: users.php?deleted=1');
            exit();
        }
    }

    // Handle role change
    if (isset($_POST['toggle_role'])) {
        $user_id = intval($_POST['user_id']);
        if ($user_id !== $_SESSION['user_id']) { // Prevent self-role change
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_role = $stmt->fetchColumn();
            
            $new_role = ($current_role === 'admin') ? 'user' : 'admin';
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            
            // 📝 Audit Log
            log_activity($_SESSION['user_id'], 'Role Changed', "Changed user ID: $user_id role to: $new_role");
            
            header('Location: users.php?role_changed=1');
            exit();
        }
    }
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt_count = $pdo->query("SELECT COUNT(*) FROM users");
$total_users = $stmt_count->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Get paginated users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 md:ml-64 w-full flex flex-col">

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <h2 class="text-3xl font-bold text-gray-800">User Management</h2>
            <a href="../register.php" class="btn-primary px-4 py-2 rounded-lg text-sm flex items-center whitespace-nowrap">
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
                                <form method="POST" class="inline-block">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="toggle_role" class="text-blue-600 hover:text-blue-900 mr-3 focus:outline-none">
                                        <?= $user['role'] === 'admin' ? 'Demote' : 'Promote' ?>
                                    </button>
                                </form>
                                <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user" class="text-red-600 hover:text-red-900 focus:outline-none">
                                        Delete
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-gray-400">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination controls -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between flex-wrap gap-4">
                    <span class="text-sm text-gray-700">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_users) ?> of <?= $total_users ?> users
                    </span>
                    <div class="flex items-center space-x-1">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 border rounded hover:bg-gray-50 text-sm">Prev</a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?= $i ?>" class="px-3 py-1 border rounded text-sm <?= $i === $page ? 'bg-highlight text-white border-highlight' : 'hover:bg-gray-50' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 border rounded hover:bg-gray-50 text-sm">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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
    </div>
    <!-- <?php include 'modal_logout.php'; ?> -->
</body>
</html>