<?php
// admin/index.php - Enhanced Admin Dashboard with Activity Tracking, Reports & Messaging
require_once '../auth.php';

require_admin();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$total_users = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM workouts");
$stmt->execute();
$total_workouts = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(attended) * 100 as avg_attendance FROM attendance");
$stmt->execute();
$avg_attendance = number_format($stmt->fetchColumn(), 1);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt->execute();
$admin_count = $stmt->fetchColumn();

// Get recent activity
$stmt = $pdo->prepare("
    SELECT ua.*, u.name, u.email 
    FROM user_activity ua
    JOIN users u ON ua.user_id = u.id
    ORDER BY ua.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending feedback
$stmt = $pdo->prepare("
    SELECT f.*, u.name as user_name 
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    WHERE f.status = 'pending'
    ORDER BY f.created_at DESC
    LIMIT 5
");
$stmt->execute();
$pending_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread messages
$stmt = $pdo->prepare("
    SELECT m.*, u.name as sender_name, u.profile_picture
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ? AND m.is_read = 0
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$unread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle feedback status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_feedback_status'])) {
    $feedback_id = (int)$_POST['feedback_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE feedback SET status = ?, admin_id = ? WHERE id = ?");
    $stmt->execute([$status, $_SESSION['user_id'], $feedback_id]);
    
    $_SESSION['admin_success'] = "Feedback status updated successfully!";
    header('Location: index.php');
    exit();
}

// Handle message reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
        
        $_SESSION['admin_success'] = "Message sent successfully!";
        header('Location: index.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Admin Dashboard</title>
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
        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        .message-card {
            border-left: 4px solid #e94560;
            transition: all 0.3s ease;
        }
        .message-card:hover {
            transform: translateX(5px);
            border-left-color: #0f3460;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 md:ml-64 w-full flex flex-col">

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Admin Dashboard</h2>
            <p class="text-gray-600">Manage your gym community</p>
        </div>

        <?php if (isset($_SESSION['admin_success'])): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                <?= $_SESSION['admin_success'] ?>
            </div>
            <?php unset($_SESSION['admin_success']); ?>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="dashboard-card bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Total Members</p>
                        <p class="text-2xl font-bold"><?= $total_users ?></p>
                        <p class="text-xs text-green-500">↑ 12% from last month</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Total Workouts</p>
                        <p class="text-2xl font-bold"><?= $total_workouts ?></p>
                        <p class="text-xs text-green-500">↑ 150 this week</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-dumbbell text-purple-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Avg. Attendance</p>
                        <p class="text-2xl font-bold"><?= $avg_attendance ?>%</p>
                        <p class="text-xs text-green-500">↑ 5% from last month</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-calendar-check text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Admin Users</p>
                        <p class="text-2xl font-bold"><?= $admin_count ?></p>
                        <p class="text-xs text-gray-500">Manage permissions</p>
                    </div>
                    <div class="bg-highlight p-3 rounded-full">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Activity -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Recent Activity</h3>
                            <p class="text-gray-600">Latest member actions</p>
                        </div>
                        <a href="activity.php" class="text-highlight hover:underline text-sm">
                            View All
                        </a>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php if (empty($recent_activity)): ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-history text-4xl mb-4 text-gray-300"></i>
                                    <p>No recent activity</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_activity as $activity): ?>
                                <div class="flex items-start p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="activity-icon bg-blue-100 text-blue-500">
                                        <?php 
                                        $icon = 'fas fa-question';
                                        switch($activity['activity_type']) {
                                            case 'login': $icon = 'fas fa-sign-in-alt'; break;
                                            case 'workout': $icon = 'fas fa-dumbbell'; break;
                                            case 'attendance': $icon = 'fas fa-calendar-check'; break;
                                            case 'profile_update': $icon = 'fas fa-user-edit'; break;
                                            case 'feedback': $icon = 'fas fa-comment'; break;
                                        }
                                        ?>
                                        <i class="<?= $icon ?>"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-bold"><?= htmlspecialchars($activity['name']) ?></h4>
                                                <p class="text-gray-600 text-sm"><?= htmlspecialchars($activity['description']) ?></p>
                                            </div>
                                            <span class="text-gray-500 text-xs">
                                                <?= date('M j, g:i A', strtotime($activity['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Reports & Feedback -->
                <div class="bg-white rounded-xl shadow overflow-hidden mt-8">
                    <div class="p-6 border-b flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Pending Feedback</h3>
                            <p class="text-gray-600">User reports and suggestions</p>
                        </div>
                        <a href="reports.php" class="text-highlight hover:underline text-sm">
                            View All
                        </a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($pending_feedback)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                <p class="font-medium">No pending feedback</p>
                                <p class="text-sm mt-1">All user feedback has been addressed</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($pending_feedback as $feedback): ?>
                                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center">
                                                <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded mr-2">
                                                    #<?= $feedback['id'] ?>
                                                </span>
                                                <h4 class="font-bold"><?= htmlspecialchars($feedback['title']) ?></h4>
                                            </div>
                                            <p class="text-gray-600 mt-1 text-sm"><?= htmlspecialchars(substr($feedback['message'], 0, 100)) ?>...</p>
                                            <div class="mt-2 flex items-center text-xs text-gray-500">
                                                <i class="fas fa-user mr-1"></i>
                                                <span><?= htmlspecialchars($feedback['user_name']) ?></span>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-clock mr-1"></i>
                                                <span><?= date('M j', strtotime($feedback['created_at'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span class="status-badge bg-yellow-100 text-yellow-800 mb-2">
                                                Pending
                                            </span>
                                            <button onclick="openFeedbackModal(<?= $feedback['id'] ?>)" 
                                                    class="text-highlight hover:underline text-sm">
                                                Take Action
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Messages & Quick Actions -->
            <div class="space-y-8">
                <!-- Messages -->
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-800">Messages</h3>
                        <a href="messages.php" class="text-highlight hover:underline text-sm">
                            View All
                        </a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($unread_messages)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-comment-dots text-4xl mb-4 text-gray-300"></i>
                                <p class="font-medium">No new messages</p>
                                <p class="text-sm mt-1">Check back later for updates</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($unread_messages as $message): ?>
                                <div class="message-card bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <img src="<?= htmlspecialchars(file_exists('../uploads/' . $message['profile_picture']) 
                                                    ? '../uploads/' . $message['profile_picture'] 
                                                    : '../uploads/default.png') ?>" 
                                             alt="User" class="w-10 h-10 rounded-full mr-3">
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-bold"><?= htmlspecialchars($message['sender_name']) ?></h4>
                                                    <p class="text-gray-600 text-sm"><?= htmlspecialchars(substr($message['message'], 0, 50)) ?>...</p>
                                                </div>
                                                <span class="text-xs text-gray-500">
                                                    <?= date('g:i A', strtotime($message['created_at'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button onclick="document.getElementById('sendMessageModal').classList.remove('hidden')"
                                class="w-full bg-highlight text-white py-3 rounded-lg font-medium flex items-center justify-center hover:bg-opacity-90 transition">
                            <i class="fas fa-paper-plane mr-2"></i> Send Message
                        </button>
                        <a href="users.php" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium flex items-center justify-center hover:bg-blue-600 transition">
                            <i class="fas fa-users mr-2"></i> Manage Users
                        </a>
                        <a href="reports.php" class="w-full bg-green-500 text-white py-3 rounded-lg font-medium flex items-center justify-center hover:bg-green-600 transition">
                            <i class="fas fa-file-alt mr-2"></i> View Reports
                        </a>
                        <div class="border-t pt-4 mt-4">
                            <p class="text-center text-gray-600 text-sm">
                                Last login: <?= isset($_SESSION['last_login']) ? date('M j, g:i A', strtotime($_SESSION['last_login'])) : 'First login' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Feedback Action Modal -->
    <div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Update Feedback Status</h3>
                <button id="closeFeedbackModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="feedbackForm" method="POST">
                <input type="hidden" name="feedback_id" id="feedbackId">
                <input type="hidden" name="update_feedback_status" value="1">
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" required>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Admin Note (Optional)</label>
                    <textarea name="admin_note" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" placeholder="Add a note for the user..."></textarea>
                </div>
                
                <button type="submit" class="w-full bg-highlight text-white py-3 rounded-lg font-medium hover:bg-opacity-90 transition">
                    Update Status
                </button>
            </form>
        </div>
    </div>

    <!-- Send Message Modal -->
    <div id="sendMessageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Send Message</h3>
                <button id="closeMessageModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="send_message" value="1">
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Recipient</label>
                    <select name="receiver_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" required>
                        <option value="">Select a user</option>
                        <?php
                        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role != 'admin'");
                        $stmt->execute();
                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Message</label>
                    <textarea name="message" rows="4" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" placeholder="Type your message..." required></textarea>
                </div>
                
                <button type="submit" class="w-full bg-highlight text-white py-3 rounded-lg font-medium hover:bg-opacity-90 transition">
                    <i class="fas fa-paper-plane mr-2"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <!-- Notification Dropdown -->
    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50 border">
        <div class="p-4 border-b">
            <h3 class="font-bold text-gray-800 flex items-center">
                <i class="fas fa-bell mr-2 text-highlight"></i>
                Notifications
            </h3>
        </div>
        <div class="max-h-96 overflow-y-auto">
            <?php if (empty($unread_messages)): ?>
                <div class="p-4 text-center text-gray-500">
                    No new notifications
                </div>
            <?php else: ?>
                <?php foreach ($unread_messages as $message): ?>
                <a href="messages.php#message-<?= $message['id'] ?>" class="block p-4 hover:bg-gray-50 border-b">
                    <div class="flex items-start">
                        <img src="<?= htmlspecialchars(file_exists('../uploads/' . $message['profile_picture']) 
                                    ? '../uploads/' . $message['profile_picture'] 
                                    : '../uploads/default.png') ?>" 
                             alt="User" class="w-10 h-10 rounded-full mr-3">
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <h4 class="font-bold"><?= htmlspecialchars($message['sender_name']) ?></h4>
                                <span class="text-xs text-gray-500"><?= date('g:i A', strtotime($message['created_at'])) ?></span>
                            </div>
                            <p class="text-gray-600 text-sm mt-1"><?= htmlspecialchars(substr($message['message'], 0, 50)) ?>...</p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="p-3 border-t">
            <a href="messages.php" class="block text-center text-highlight hover:underline font-medium">
                View All Messages
            </a>
        </div>
    </div>

    <script>
        // Modal controls
        function openFeedbackModal(feedbackId) {
            document.getElementById('feedbackId').value = feedbackId;
            document.getElementById('feedbackModal').classList.remove('hidden');
        }
        
        document.getElementById('closeFeedbackModal').addEventListener('click', function() {
            document.getElementById('feedbackModal').classList.add('hidden');
        });
        
        document.getElementById('closeMessageModal').addEventListener('click', function() {
            document.getElementById('sendMessageModal').classList.add('hidden');
        });
        
        document.getElementById('notificationBtn').addEventListener('click', function() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('hidden');
        });
        
        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const feedbackModal = document.getElementById('feedbackModal');
            const messageModal = document.getElementById('sendMessageModal');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationBtn = document.getElementById('notificationBtn');
            
            if (feedbackModal && !feedbackModal.contains(event.target) && feedbackModal.classList.contains('hidden') === false) {
                feedbackModal.classList.add('hidden');
            }
            
            if (messageModal && !messageModal.contains(event.target) && messageModal.classList.contains('hidden') === false) {
                messageModal.classList.add('hidden');
            }
            
            if (notificationDropdown && !notificationDropdown.contains(event.target) && !notificationBtn.contains(event.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });
    </script>
    </div>
</body>
</html>