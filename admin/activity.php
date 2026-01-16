<?php
// admin/activity.php
require_once '../auth.php';
require_once '../config.php';

require_admin();

/* ---------- how many rows to show (default 5) ---------- */
$limit = isset($_GET['show']) && (int)$_GET['show'] > 0
         ? (int)$_GET['show']
         : 5;

/* ---------- pull the rows ---------- */
$stmt = $pdo->prepare(
    "SELECT ua.*, u.name, u.email
       FROM user_activity ua
       JOIN users u ON u.id = ua.user_id
      ORDER BY ua.created_at DESC
      LIMIT :lim"
);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$activity = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM – Activity Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme:{
                extend:{
                    colors:{
                        primary:'#1a1a2e',
                        secondary:'#16213e',
                        accent:'#0f3460',
                        highlight:'#e94560'
                    }
                }
            }
        };
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body{ font-family:'Poppins',sans-serif; background:#f8f9fa; }
        /* thin scrollbar */
        .scroll-thin::-webkit-scrollbar{width:6px}
        .scroll-thin::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}
    </style>
</head>
<body class="bg-gray-50">
<!-- ---------- nav ---------- -->
<nav class="bg-primary text-white shadow-lg">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-dumbbell text-highlight text-2xl"></i>
                <h1 class="text-xl font-bold">Warzone Admin</h1>
            </div>
            <div class="hidden md:flex items-center space-x-6">
                <a href="index.php" class="hover:text-highlight transition">Dashboard</a>
                <a href="users.php" class="hover:text-highlight transition">Users</a>
                <a href="reports.php" class="hover:text-highlight transition">Reports</a>
                <a href="messages.php" class="hover:text-highlight transition">Messages</a>
                <a href="activity.php" class="hover:text-highlight transition font-semibold">Activity</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../logout.php" class="text-gray-400 hover:text-highlight transition" title="Logout">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ---------- content ---------- -->
<main class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Activity Log</h2>
            <p class="text-gray-600">Recent member actions</p>
        </div>
        <div class="flex items-center space-x-2">
            <label class="text-sm text-gray-600">Show</label>
            <select id="limitSel" class="border rounded px-2 py-1 text-sm">
                <option value="5"  <?= $limit === 5  ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
            </select>
        </div>
    </div>

    <!-- ---------- card ---------- -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Last <?= $limit ?> actions</h3>
            <a href="index.php" class="text-highlight hover:underline text-sm">← Back to dashboard</a>
        </div>

        <div class="p-4 scroll-thin overflow-y-auto max-h-[65vh]">
            <?php if (!$activity): ?>
                <div class="text-center py-10 text-gray-500">
                    <i class="fas fa-history text-4xl mb-3 text-gray-300"></i>
                    <p>No activity recorded yet.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                <?php foreach ($activity as $row):
                    $icon = match($row['activity_type']){
                        'login'          => 'fa-sign-in-alt',
                        'logout'         => 'fa-sign-out-alt',
                        'workout'        => 'fa-dumbbell',
                        'attendance'     => 'fa-calendar-check',
                        'profile_update' => 'fa-user-edit',
                        'password_change'=> 'fa-key',
                        default          => 'fa-question-circle'
                    };
                ?>
                    <div class="flex items-start p-3 rounded-lg border hover:bg-gray-50 transition">
                        <div class="w-10 h-10 bg-primary/10 text-primary rounded-full flex items-center justify-center mr-3">
                            <i class="fas <?= $icon ?>"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($row['name']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($row['description']) ?></p>
                                </div>
                                <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                                    <?= date('M j, g:i A', strtotime($row['created_at'])) ?>
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-gray-400">
                                IP: <?= htmlspecialchars($row['ip_address']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
/* quick limit switcher */
document.getElementById('limitSel').addEventListener('change', e => {
    window.location = '?show=' + e.target.value;
});
</script>
</body>
</html>