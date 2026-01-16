<?php
// profile.php - Enhanced User Profile with Messaging and Feedback (FIXED)
require_once 'auth.php';
require_once 'config.php';

require_login();

$user_id = $_SESSION['user_id'];
$user_data = get_user_data($user_id);

// ✅ Get a valid admin ID dynamically (first admin found)
$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_id = $admin['id'] ?? 1; // Fallback to 1 if no admin found

// Get user's feedback history
$stmt = $pdo->prepare("
    SELECT * FROM feedback 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$user_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's messages
$stmt = $pdo->prepare("
    SELECT m.*, u.name as sender_name, u.profile_picture
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ? OR m.sender_id = ?
    ORDER BY m.created_at ASC
    LIMIT 10
");
// We'll reverse in PHP to get latest 10, then sort ASC
$stmt->execute([$user_id, $user_id]);
$user_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $goal = !empty($_POST['goal']) ? filter_var($_POST['goal'], FILTER_SANITIZE_STRING) : null;
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
        
        // Handle profile picture upload
        $profile_picture = $user_data['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $new_picture = upload_profile_picture($_FILES['profile_picture']);
            if ($new_picture !== 'default.png') {
                $profile_picture = $new_picture;
                // Update session
                $_SESSION['user_profile_picture'] = $profile_picture;
            }
        }
        
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = "Email is already in use by another account";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, fitness_goal = ?, profile_picture = ?, weight = ?, height = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $goal, $profile_picture, $weight, $height, $user_id])) {
                $success = "Profile updated successfully!";
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $user_data['name'] = $name;
                $user_data['email'] = $email;
                $user_data['fitness_goal'] = $goal;
                $user_data['profile_picture'] = $profile_picture;
                $user_data['weight'] = $weight;
                $user_data['height'] = $height;
                
                // Log activity
                log_user_activity($user_id, 'profile_update', 'Updated profile information');
            } else {
                $error = "Failed to update profile";
            }
        }
    }
    
    // Handle password change
    /* ------------------------------------------------------------------
   1.  PHP – replace the whole “Handle password change” section
   ------------------------------------------------------------------ */
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /* basic front-end guard */
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $password_error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $password_error = 'New password must be at least 6 characters.';
    } else {
        /* verify the old hash */
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current_password, $hash)) {
            $password_error = 'Current password is incorrect.';
        } else {
            /* update to new hash */
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);

            $password_success = 'Password changed successfully!';
            log_user_activity($user_id, 'password_change', 'Changed account password');
        }
    }
}
    
    // Handle feedback submission
    if (isset($_POST['submit_feedback'])) {
        $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
        $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
        
        if (!empty($title) && !empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO feedback (user_id, title, message) VALUES (?, ?, ?)");
            if ($stmt->execute([$user_id, $title, $message])) {
                $feedback_success = "Feedback submitted successfully! We'll get back to you soon.";
                
                // Log activity
                log_user_activity($user_id, 'feedback', 'Submitted feedback: ' . $title);
            } else {
                $feedback_error = "Failed to submit feedback. Please try again.";
            }
        } else {
            $feedback_error = "Both title and message are required.";
        }
    }
    
    // Handle message sending (FIXED)
    if (isset($_POST['send_message'])) {
        $message = trim($_POST['message']);
        $receiver_id = (int)$_POST['admin_id'];
        
        // ✅ Validate admin exists before sending
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$receiver_id]);
        $admin_exists = $stmt->fetch();
        
        if (!$admin_exists) {
            $message_error = "Admin account not found. Please contact support.";
        } elseif (empty($message)) {
            $message_error = "Message cannot be empty.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            if ($stmt->execute([$user_id, $receiver_id, $message])) {
                $message_success = "Message sent successfully!";
                
                // Log activity
                log_user_activity($user_id, 'message', 'Sent message to admin');
            } else {
                $message_error = "Failed to send message. Please try again.";
            }
        }
    }
}

// Calculate BMI
$bmi = null;
if ($user_data['weight'] && $user_data['height']) {
    $height_m = $user_data['height'] / 100;
    $bmi = round($user_data['weight'] / ($height_m * $height_m), 1);
}

// Function to log user activity
function log_user_activity($user_id, $activity_type, $description) {
    global $pdo;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, activity_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $activity_type, $description, $ip_address, $user_agent]);
}

// Mark messages as read
if (!empty($user_messages)) {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Profile</title>
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
        .profile-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .bmi-indicator {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .profile-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e94560;
            margin: 0 auto;
            display: block;
        }
        .file-upload {
            position: relative;
            display: inline-block;
            margin-top: 2px;
        }
        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-upload-label {
            display: block;
            padding: 0.5rem 1rem;
            background: #e94560;
            color: white;
            text-align: center;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .file-upload-label:hover {
            background: #d03a55;
        }
        .message-card {
            border-left: 3px solid #e94560;
            transition: all 0.3s ease;
        }
        .message-card:hover {
            transform: translateX(5px);
        }
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
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
                <a href="dashboard.php" class="text-xl font-bold text-white hover:text-highlight transition">
                    Warzone Gym CRM
                </a>
            </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="dashboard.php" class="hover:text-highlight transition font-semibold">Dashboard</a>
                    <a href="workouts.php" class="hover:text-highlight transition">Workouts</a>
                    <a href="attendance.php" class="hover:text-highlight transition">Attendance</a>
                    <a href="journal.php" class="hover:text-highlight transition">Journal</a>
                    <a href="chat.php" class="hover:text-highlight transition">Chat</a>
                    <a href="profile.php" class="hover:text-highlight transition">Profile</a>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- ✅ Profile Picture (CLICKABLE → profile.php) -->
                    <a href="profile.php" class="flex items-center space-x-2 group" title="View Profile">
                        <img src="<?= htmlspecialchars(file_exists('uploads/' . ($_SESSION['user_profile_picture'] ?? 'default.png')) 
                                    ? 'uploads/' . $_SESSION['user_profile_picture'] 
                                    : 'uploads/default.png') ?>" 
                            alt="Profile" 
                            class="rounded-full w-10 h-10 transition-transform duration-200 group-hover:scale-105 group-hover:ring-2 group-hover:ring-highlight">
                        <a href="logout.php" 
                        class="text-gray-400 hover:text-highlight transition ml-1 opacity-75 group-hover:opacity-100" 
                        title="Logout">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </a>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Your Profile</h2>
                <button onclick="document.getElementById('editProfile').classList.remove('hidden')" class="btn-primary px-4 py-2 rounded-lg text-sm flex items-center">
                    <i class="fas fa-edit mr-2"></i> Edit Profile
                </button>
            </div>
            
            <!-- Profile Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-1">
                    <div class="profile-card bg-white p-6 rounded-xl shadow text-center">
                        <div class="mb-4">
                            <img src="<?php echo file_exists('uploads/' . $user_data['profile_picture']) ? 'uploads/' . $user_data['profile_picture'] : 'uploads/default.png'; ?>" alt="Profile" class="profile-preview">
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($user_data['email']); ?></p>
                        <div class="mt-4">
                            <span class="inline-block bg-highlight text-white text-xs px-3 py-1 rounded-full">
                                <?php echo ucfirst($user_data['role']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($user_data['weight'] && $user_data['height']): ?>
                    <div class="profile-card bg-white p-6 rounded-xl shadow mt-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Body Metrics</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-600">Weight:</span>
                                <span class="font-semibold"><?php echo $user_data['weight']; ?> kg</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Height:</span>
                                <span class="font-semibold"><?php echo $user_data['height']; ?> cm</span>
                            </div>
                            <div>
                                <span class="text-gray-600">BMI:</span>
                                <span class="font-semibold"><?php echo $bmi; ?></span>
                                <span class="bmi-indicator 
                                    <?php 
                                    if ($bmi < 18.5) echo 'bg-blue-100 text-blue-800';
                                    elseif ($bmi < 25) echo 'bg-green-100 text-green-800';
                                    elseif ($bmi < 30) echo 'bg-yellow-100 text-yellow-800';
                                    else echo 'bg-red-100 text-red-800';
                                    ?>">
                                    <?php 
                                    if ($bmi < 18.5) echo 'Underweight';
                                    elseif ($bmi < 25) echo 'Normal';
                                    elseif ($bmi < 30) echo 'Overweight';
                                    else echo 'Obese';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Support Section (FIXED) -->
                    <div class="profile-card bg-white p-6 rounded-xl shadow mt-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Need Help?</h3>
                        <p class="text-gray-600 mb-4">Contact our support team for any questions or issues.</p>
                        <?php if ($admin_id): ?>
                            <button onclick="document.getElementById('feedbackModal').classList.remove('hidden')" 
                                    class="w-full bg-highlight text-white py-2 rounded-lg font-medium hover:bg-opacity-90 transition">
                                <i class="fas fa-comment-alt mr-2"></i> Contact Support
                            </button>
                        <?php else: ?>
                            <div class="bg-yellow-50 text-yellow-800 p-3 rounded-lg text-sm">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Admin contact not available. Please try again later.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <!-- Messages Section -->
                    <div class="profile-card bg-white p-6 rounded-xl shadow">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-gray-800">Messages</h3>
                            <?php if ($admin_id): ?>
                                <button onclick="document.getElementById('messageModal').classList.remove('hidden')" 
                                        class="text-highlight hover:underline text-sm">
                                    <i class="fas fa-paper-plane mr-1"></i> New Message
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($user_messages)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                <p class="font-medium">No messages yet</p>
                                <?php if ($admin_id): ?>
                                    <p class="text-sm mt-1">Start a conversation with our support team</p>
                                <?php else: ?>
                                    <p class="text-sm mt-1">Admin contact is currently unavailable</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                <?php foreach ($user_messages as $message): ?>
                                <div class="message-card bg-gray-50 p-4 rounded-lg <?php echo $message['sender_id'] == $user_id ? 'ml-8' : 'mr-8'; ?>">
                                    <div class="flex items-start">
                                        <img src="<?= htmlspecialchars(file_exists('uploads/' . $message['profile_picture']) 
                                                    ? 'uploads/' . $message['profile_picture'] 
                                                    : 'uploads/default.png') ?>" 
                                             alt="User" class="w-10 h-10 rounded-full mr-3">
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start mb-1">
                                                <span class="font-bold"><?= htmlspecialchars($message['sender_name']) ?></span>
                                                <span class="text-xs text-gray-500"><?= date('g:i A', strtotime($message['created_at'])) ?></span>
                                            </div>
                                            <p class="bg-white p-3 rounded-lg shadow text-gray-700"><?= htmlspecialchars($message['message']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Feedback History -->
                    <div class="profile-card bg-white p-6 rounded-xl shadow mt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-gray-800">Feedback History</h3>
                            <button onclick="document.getElementById('feedbackModal').classList.remove('hidden')" 
                                    class="text-highlight hover:underline text-sm">
                                <i class="fas fa-plus mr-1"></i> New Feedback
                            </button>
                        </div>
                        
                        <?php if (empty($user_feedback)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-comment-dots text-4xl mb-4 text-gray-300"></i>
                                <p class="font-medium">No feedback history</p>
                                <p class="text-sm mt-1">Submit your first feedback to help us improve</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($user_feedback as $feedback): ?>
                                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center">
                                                <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded mr-2">
                                                    #<?= $feedback['id'] ?>
                                                </span>
                                                <h4 class="font-bold"><?= htmlspecialchars($feedback['title']) ?></h4>
                                            </div>
                                            <p class="text-gray-600 mt-1"><?= htmlspecialchars(substr($feedback['message'], 0, 100)) ?>...</p>
                                            <div class="mt-2 flex items-center text-xs text-gray-500">
                                                <i class="fas fa-clock mr-1"></i>
                                                <span><?= date('M j, Y', strtotime($feedback['created_at'])) ?></span>
                                                <span class="mx-2">•</span>
                                                <span class="status-badge 
                                                    <?php 
                                                    switch($feedback['status']) {
                                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'in_progress': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'resolved': echo 'bg-green-100 text-green-800'; break;
                                                        case 'closed': echo 'bg-gray-100 text-gray-800'; break;
                                                    }
                                                    ?>">
                                                    <?= ucfirst($feedback['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if ($feedback['admin_id']): ?>
                                            <div class="text-right">
                                                <i class="fas fa-check-circle text-green-500"></i>
                                                <span class="text-xs block text-gray-500 mt-1">Replied</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Account Information -->
                    <div class="profile-card bg-white p-6 rounded-xl shadow mt-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Account Information</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Member Since</span>
                                <span><?= date('M Y', strtotime($user_data['created_at'])) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Last Login</span>
                                <span>
                                    <?= $user_data['last_login'] 
                                        ? date('M j, Y g:i A', strtotime($user_data['last_login'])) 
                                        : 'Never' ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Workouts Logged</span>
                                <span>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ?");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetchColumn();
                                    ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Attendance Rate</span>
                                <span><?= calculate_attendance_rate($user_id) ?>%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <!-- 2.  HTML – replace the “Change Password” card only -->
<div class="profile-card bg-white p-6 rounded-xl shadow mt-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Change Password</h3>

    <?php if (!empty($password_success)): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg"><?= htmlspecialchars($password_success) ?></div>
    <?php endif; ?>
    <?php if (!empty($password_error)): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg"><?= htmlspecialchars($password_error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-gray-700 mb-2">Current Password</label>
            <input type="password" name="current_password" required
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight">
        </div>
        <div>
            <label class="block text-gray-700 mb-2">New Password</label>
            <input type="password" name="new_password" required minlength="6"
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight">
        </div>
        <div>
            <label class="block text-gray-700 mb-2">Confirm New Password</label>
            <input type="password" name="confirm_password" required minlength="6"
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight">
        </div>
        <button type="submit" name="change_password" class="btn-primary py-2 px-4 rounded-lg">
            Update Password
        </button>
    </form>
</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Profile Modal -->
    <div id="editProfile" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Edit Profile</h3>
                <button onclick="document.getElementById('editProfile').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="text-center">
                    <img id="editProfilePreview" src="<?php echo file_exists('uploads/' . $user_data['profile_picture']) ? 'uploads/' . $user_data['profile_picture'] : 'uploads/default.png'; ?>" alt="Profile Preview" class="profile-preview mb-2">
                    <div class="file-upload">
                        <input type="file" name="profile_picture" id="editProfilePicture" accept="image/*">
                        <label for="editProfilePicture" class="file-upload-label">Change Profile Picture</label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Fitness Goal (Optional)</label>
                    <textarea name="goal" class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight"><?php echo htmlspecialchars($user_data['fitness_goal'] ?? ''); ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Weight (kg) - Optional</label>
                        <input type="number" step="0.1" name="weight" value="<?php echo $user_data['weight'] ? $user_data['weight'] : ''; ?>" class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Height (cm) - Optional</label>
                        <input type="number" step="0.1" name="height" value="<?php echo $user_data['height'] ? $user_data['height'] : ''; ?>" class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight">
                    </div>
                </div>
                <button type="submit" name="update_profile" class="w-full btn-primary py-2 rounded-lg">
                    Update Profile
                </button>
            </form>
            <?php if (isset($success)): ?>
                <div class="mt-4 p-3 bg-green-100 text-green-700 rounded-lg text-center">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="mt-4 p-3 bg-red-100 text-red-700 rounded-lg text-center">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Feedback Modal -->
    <div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Contact Support</h3>
                <button onclick="document.getElementById('feedbackModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <?php if (isset($feedback_success)): ?>
                    <div class="p-3 bg-green-100 text-green-700 rounded-lg">
                        <?= $feedback_success ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($feedback_error)): ?>
                    <div class="p-3 bg-red-100 text-red-700 rounded-lg">
                        <?= $feedback_error ?>
                    </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-gray-700 mb-2">Subject</label>
                    <input type="text" name="title" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight" placeholder="What's your feedback about?">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Message</label>
                    <textarea name="message" rows="4" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight" placeholder="Please provide details about your feedback..."></textarea>
                </div>
                <button type="submit" name="submit_feedback" class="w-full bg-highlight text-white py-3 rounded-lg font-medium hover:bg-opacity-90 transition">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Feedback
                </button>
                <p class="text-center text-gray-500 text-sm mt-2">
                    Our support team will respond within 24 hours
                </p>
            </form>
        </div>
    </div>
    
    <!-- Message Modal (FIXED) -->
    <div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Send Message</h3>
                <button onclick="document.getElementById('messageModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <?php if (isset($message_success)): ?>
                    <div class="p-3 bg-green-100 text-green-700 rounded-lg">
                        <?= $message_success ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($message_error)): ?>
                    <div class="p-3 bg-red-100 text-red-700 rounded-lg">
                        <?= $message_error ?>
                    </div>
                <?php endif; ?>
                
                <input type="hidden" name="admin_id" value="<?= $admin_id ?>">
                
                <div>
                    <label class="block text-gray-700 mb-2">Your Message</label>
                    <textarea name="message" rows="4" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-highlight" placeholder="Type your message here..."></textarea>
                </div>
                <button type="submit" name="send_message" class="w-full bg-highlight text-white py-3 rounded-lg font-medium hover:bg-opacity-90 transition">
                    <i class="fas fa-paper-plane mr-2"></i> Send Message
                </button>
                <p class="text-center text-gray-500 text-sm mt-2">
                    Admin will respond to your message soon
                </p>
            </form>
        </div>
    </div>

    <script>
        // Close modal when clicking outside
        document.getElementById('editProfile').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
        
        document.getElementById('feedbackModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
        
        document.getElementById('messageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
        
        // Preview profile picture in edit modal
        document.getElementById('editProfilePicture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editProfilePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
    <?php include 'modal_logout.php'; ?>
</body>
</html>