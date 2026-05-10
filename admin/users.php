<?php
// admin/users.php - User Management
require_once '../auth.php';

require_admin();

// 🔍 Handle user actions (POST with CSRF)
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
            log_activity($_SESSION['user_id'], 'User Deleted', "Deleted user ID: $user_id");
            header('Location: users.php?deleted=1');
            exit();
        }
    }

    // Handle role change
    if (isset($_POST['toggle_role'])) {
        $user_id = intval($_POST['user_id']);
        if ($user_id !== $_SESSION['user_id']) {
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_role = $stmt->fetchColumn();
            $new_role = ($current_role === 'admin') ? 'user' : 'admin';
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            log_activity($_SESSION['user_id'], 'Role Changed', "Changed user ID: $user_id to $new_role");
            header('Location: users.php?role_changed=1');
            exit();
        }
    }
}

// 🔍 Search & Filter Setup
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where_clauses = [];
$params = [];

if ($search) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM users $where_sql");
$stmt_count->execute($params);
$total_users = $stmt_count->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Get paginated users
$stmt = $pdo->prepare("SELECT * FROM users $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
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
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 md:ml-64 w-full flex flex-col">

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">User Management</h2>
                <p class="text-gray-500">Manage gym members and administrators</p>
            </div>
            <a href="../register.php" class="bg-highlight text-white px-6 py-3 rounded-xl font-bold flex items-center hover:opacity-90 transition shadow-lg">
                <i class="fas fa-plus mr-2"></i> Add New User
            </a>
        </div>

        <!-- 🔍 Search & Filter Bar -->
        <div class="bg-white p-4 rounded-xl shadow mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search by name or email..."
                           class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-highlight outline-none">
                </div>
                <div class="w-full md:w-48">
                    <select name="role" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-highlight outline-none" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
                        <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Members</option>
                    </select>
                </div>
                <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-900 transition">
                    Filter
                </button>
                <?php if ($search || $role_filter): ?>
                    <a href="users.php" class="text-gray-500 hover:text-highlight flex items-center justify-center">Clear</a>
                <?php endif; ?>
            </form>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): 
                            // Check if active (payment in last 30 days)
                            $stmt_pay = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ? AND payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                            $stmt_pay->execute([$user['id']]);
                            $is_active = $stmt_pay->fetchColumn() > 0;
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <img class="h-10 w-10 rounded-full object-cover"
                                             src="<?= htmlspecialchars(file_exists('../uploads/' . ($user['profile_picture'] ?? 'default.png')) ? '../uploads/' . $user['profile_picture'] : '../uploads/default.png') ?>" alt="">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-bold rounded-full uppercase tracking-widest <?= $user['role'] === 'admin' ? 'bg-highlight text-white' : 'bg-gray-100 text-gray-600' ?>">
                                    <?= $user['role'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($is_active): ?>
                                    <span class="px-2 py-1 text-[10px] font-bold text-green-600 bg-green-50 rounded-lg uppercase tracking-wider">Active</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-[10px] font-bold text-red-600 bg-red-50 rounded-lg uppercase tracking-wider">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                <?= date('M j, Y', strtotime($user['created_at'])) ?>
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