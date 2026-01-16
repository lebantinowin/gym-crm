<?php
// dashboard.php - Enhanced Main Dashboard with Notifications, AI Popups & Journal
require_once 'auth.php';
require_once 'config.php';

require_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_email = $_SESSION['user_email'];

// Get user data
$user_data = get_user_data($user_id);

// Calculate attendance rate
$attendance_rate = calculate_attendance_rate($user_id);

// Get recent workouts
$workouts = get_recent_workouts($user_id, 5); // increase for journal

// Get mood data (last 30 days for journal)
$stmt = $pdo->prepare("
    SELECT * FROM mood_checkins 
    WHERE user_id = ? 
    ORDER BY date DESC 
    LIMIT 30
");
$stmt->execute([$user_id]);
$mood_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate goal progress
$goal_progress = 0;
$ai_confidence = 0;

if (!empty($user_data['fitness_goal'])) {
    $goal_progress = min(100, 50 + ($attendance_rate * 0.3) + (count($workouts) * 5));
    $ai_confidence = min(100, 70 + ($attendance_rate * 0.2) + (count($workouts) * 3));
}

// ===== NOTIFICATIONS =====
$notifications = [];

// AI Coach reminders
if (empty($workouts) || strtotime($workouts[0]['date']) < strtotime('-2 days')) {
    $notifications[] = [
        'id' => 'ai_reminder',
        'type' => 'ai',
        'title' => 'Warzone AI Coach',
        'message' => 'It’s been 2+ days since your last workout. Ready to get back on track?',
        'time' => date('H:i'),
        'icon' => 'fas fa-robot',
        'color' => 'highlight'
    ];
}

// Streak bonus
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM attendance 
    WHERE user_id = ? AND attended = 1 AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->execute([$user_id]);
$week_streak = (int)$stmt->fetchColumn();
if ($week_streak >= 5) {
    $notifications[] = [
        'id' => 'streak',
        'type' => 'success',
        'title' => '🔥 5-Day Streak!',
        'message' => 'You’ve trained 5+ days this week. Keep the fire burning!',
        'time' => date('H:i'),
        'icon' => 'fas fa-fire',
        'color' => 'success'
    ];
}

// Mood insight
if (!empty($mood_history)) {
    $happy_days = array_filter($mood_history, fn($m) => $m['mood'] === '😊' || $m['mood'] === '😁');
    if (count($happy_days) / count($mood_history) < 0.3) {
        $notifications[] = [
            'id' => 'mood_insight',
            'type' => 'warning',
            'title' => 'Mood Check',
            'message' => 'Your mood’s been low lately. Want to log how you’re feeling?',
            'time' => date('H:i'),
            'icon' => 'fas fa-heart',
            'color' => 'yellow-500'
        ];
    }
}

// Handle journal CRUD (add/edit/delete/archive)
$journal_action = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mood check-in (for journal)
    if (isset($_POST['journal_mood'])) {
        $mood = $_POST['journal_mood'];
        $notes = trim($_POST['journal_notes'] ?? '');
        $date = $_POST['journal_date'] ?? date('Y-m-d');
        $id = $_POST['journal_id'] ?? null;
        $action = $_POST['journal_action'] ?? 'add';

        if ($action === 'add' || $action === 'edit') {
            $stmt = $pdo->prepare($action === 'add' 
                ? "INSERT INTO mood_checkins (user_id, date, mood, notes) VALUES (?, ?, ?, ?)"
                : "UPDATE mood_checkins SET mood = ?, notes = ? WHERE id = ? AND user_id = ?"
            );
            $params = $action === 'add' 
                ? [$user_id, $date, $mood, $notes]
                : [$mood, $notes, $id, $user_id];
            $stmt->execute($params);
            $journal_action = 'saved';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM mood_checkins WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $journal_action = 'deleted';
        } elseif ($action === 'archive') {
            $stmt = $pdo->prepare("UPDATE mood_checkins SET archived = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $journal_action = 'archived';
        } elseif ($action === 'unarchive') {
            $stmt = $pdo->prepare("UPDATE mood_checkins SET archived = 0 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $journal_action = 'unarchived';
        } elseif ($action === 'star') {
            $stmt = $pdo->prepare("UPDATE mood_checkins SET starred = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $journal_action = 'starred';
        } elseif ($action === 'unstar') {
            $stmt = $pdo->prepare("UPDATE mood_checkins SET starred = 0 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $journal_action = 'unstarred';
        }

        // Refresh
        $stmt = $pdo->prepare("SELECT * FROM mood_checkins WHERE user_id = ? ORDER BY date DESC LIMIT 30");
        $stmt->execute([$user_id]);
        $mood_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Workout log (existing)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && isset($_POST['log_workout'])) {
        header('Content-Type: application/json');
        
        try {
            $type = filter_var($_POST['workout_type'], FILTER_SANITIZE_STRING);
            $duration = intval($_POST['duration']);
            $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : 0;
            $notes = !empty($_POST['workout_notes']) ? filter_var($_POST['workout_notes'], FILTER_SANITIZE_STRING) : '';
            $mood = $_POST['workout_mood'] ?? '😊';
            
            // Create exercises array
            $exercises = [];
            if ($type === 'Upper Body') {
                $exercises = ['Bench Press', 'Pull-ups', 'Shoulder Press', 'Bicep Curls'];
            } elseif ($type === 'Lower Body') {
                $exercises = ['Squats', 'Deadlifts', 'Lunges', 'Calf Raises'];
            } elseif ($type === 'Cardio') {
                $exercises = ['Running', 'Cycling', 'Jump Rope', 'Burpees'];
            } else {
                $exercises = ['Push-ups', 'Sit-ups', 'Planks', 'Burpees'];
            }
            
            global $pdo;
            $user_id = $_SESSION['user_id'];
            
            $stmt = $pdo->prepare("INSERT INTO workouts (user_id, date, type, duration, calories_burned, notes, mood, exercises) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $duration, $calories, $notes, $mood, json_encode($exercises)]);
            
            // Also log attendance
            $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, attended) VALUES (?, CURDATE(), 1) ON DUPLICATE KEY UPDATE attended = 1");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true, 'message' => '✅ Workout logged successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '⚠️ ' . $e->getMessage()]);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Dashboard</title>
    <!-- ✅ FIXED CDN URLs (no trailing spaces) -->
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
                        highlight: '#e94560',
                        success: '#06d6a0'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .gradient-border { position: relative; border-radius: 0.5rem; }
        .gradient-border::before {
            content: ''; position: absolute; top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, #e94560, #0f3460, #16213e); border-radius: 0.6rem; z-index: -1;
        }
        .btn-primary {
            background: linear-gradient(45deg, #e94560, #0f3460); color: white;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .dashboard-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        /* Notifications */
        .notification-badge { position: absolute; top: -4px; right: -4px; background: #e94560; color: white; font-size: 0.7rem; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .notification-dropdown { position: absolute; top: 100%; right: 0; margin-top: 8px; width: 320px; background: white; border-radius: 0.75rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 50; display: none; }
        .notification-item { padding: 1rem; border-bottom: 1px solid #f1f5f9; }
        .notification-item:last-child { border-bottom: none; }
        
        /* AI Popup */
        .ai-popup {
            position: fixed; top: 20px; right: 20px; max-width: 320px;
            background: white; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 1.25rem; z-index: 100;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .ai-popup .close { position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1rem; cursor: pointer; }
        
        /* Floating AI Button */
        .ai-float-btn {
            position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px;
            background: linear-gradient(45deg, #e94560, #0f3460); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(233, 69, 96, 0.4);
            z-index: 90; cursor: pointer; transition: all 0.3s;
        }
        .ai-float-btn:hover { transform: scale(1.1) rotate(5deg); }
        .ai-float-btn.pulse { animation: pulse 2s infinite; }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(233, 69, 96, 0.7); }
            70% { box-shadow: 0 0 0 12px rgba(233, 69, 96, 0); }
            100% { box-shadow: 0 0 0 0 rgba(233, 69, 96, 0); }
        }
        
        /* Journal */
        .journal-card { position: relative; }
        .journal-actions { display: none; position: absolute; top: 1rem; right: 1rem; }
        .journal-card:hover .journal-actions { display: flex; gap: 0.5rem; }
        .journal-star { color: #fbbf24; }
        .journal-archived { opacity: 0.6; }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">


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
                    <!-- Notifications -->
                    <!-- <div class="relative">
                        <button id="notificationBtn" class="text-xl relative">
                            <i class="fas fa-bell"></i>
                            <?php if (!empty($notifications)): ?>
                                <span class="notification-badge"><?= count($notifications) ?></span>
                            <?php endif; ?>
                        </button>
                    </div> -->

                    <!-- ✅ Profile Picture (CLICKABLE → profile.php) -->
                    <a href="profile.php" class="flex items-center space-x-2 group" title="View Profile">
                        <img src="<?= htmlspecialchars(file_exists('uploads/' . ($_SESSION['user_profile_picture'] ?? 'default.png')) 
                                    ? 'uploads/' . $_SESSION['user_profile_picture'] 
                                    : 'uploads/default.png') ?>" 
                            alt="Profile" 
                            class="rounded-full w-10 h-10 transition-transform duration-200 group-hover:scale-105 group-hover:ring-2 group-hover:ring-highlight">
                        <!-- <span class="hidden md:inline font-medium group-hover:text-highlight"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span> -->
                        <!-- Logout icon stays next to name -->
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

    <!-- ✅ AI COACH POPUPS (contextual) -->
    <?php if (empty($workouts)): ?>
        <div class="ai-popup bg-highlight text-white">
            <span class="close" onclick="this.parentElement.style.display='none'">&times;</span>
            <div class="flex items-start">
                <i class="fas fa-robot text-2xl mr-3 mt-1"></i>
                <div>
                    <p class="font-bold">First time?</p>
                    <p class="mt-1">Let’s get you started! Click <strong>“Get Started”</strong> below to set your fitness goal.</p>
                </div>
            </div>
        </div>
    <?php elseif (strtotime($workouts[0]['date']) < strtotime('-1 day')): ?>
        <div class="ai-popup bg-blue-500 text-white">
            <span class="close" onclick="this.parentElement.style.display='none'">&times;</span>
            <div class="flex items-start">
                <i class="fas fa-robot text-2xl mr-3 mt-1"></i>
                <div>
                    <p class="font-bold">Welcome back, <?= htmlspecialchars($user_name) ?>!</p>
                    <p class="mt-1">What’s the plan today? Upper body? Legs? Or just 10 mins of movement?</p>
                </div>
            </div>
        </div>
    <?php elseif ($attendance_rate > 85 && !empty($mood_history) && $mood_history[0]['mood'] === '😁'): ?>
        <div class="ai-popup bg-green-500 text-white">
            <span class="close" onclick="this.parentElement.style.display='none'">&times;</span>
            <div class="flex items-start">
                <i class="fas fa-robot text-2xl mr-3 mt-1"></i>
                <div>
                    <p class="font-bold">🔥 Hot streak!</p>
                    <p class="mt-1">Ever heard? Muscle protein synthesis peaks 24–48h post-workout — your gains are compounding!</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Dashboard Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Welcome back, <?= htmlspecialchars($user_name) ?>!</h2>
            <p class="text-gray-600">Your personalized fitness journey with Warzone AI Coach</p>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Attendance -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow">

                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Attendance Rate</p>
                        <p class="text-2xl font-bold"><?= $attendance_rate ?>%</p>
                        <p class="text-xs <?= $attendance_rate > 80 ? 'text-green-500' : 'text-highlight' ?>">
                            <?= $attendance_rate > 80 ? '🔥 Above target' : '🎯 Target: 85%+' ?>
                        </p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-calendar-check text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Workouts -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Workouts This Week</p>
                        <p class="text-2xl font-bold"><?= count(array_filter($workouts, fn($w) => strtotime($w['date']) >= strtotime('monday this week'))) ?></p>
                        <p class="text-xs text-green-500">↑ <?= count($workouts) ?> total</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-dumbbell text-purple-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Goal Progress -->
            <?php if (!empty($user_data['fitness_goal'])): ?>
                <div id="goalProgressCard"
     class="dashboard-card bg-white p-6 rounded-xl shadow cursor-pointer hover:ring-2 hover:ring-highlight">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Goal Progress</p>
                        <p class="text-2xl font-bold"><?= $goal_progress ?>%</p>
                        <p class="text-xs text-highlight">"<?= htmlspecialchars(substr($user_data['fitness_goal'], 0, 20)) . (strlen($user_data['fitness_goal']) > 20 ? '…' : '') ?>"</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-chart-line text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center">
                    <div>
                    <p class="text-gray-500 text-sm">Consistency Score</p>
                    <p class="text-2xl font-bold"><?= $ai_confidence ?>%</p>
                    <p class="text-xs <?= $ai_confidence > 85 ? 'text-success' : 'text-yellow-500' ?>">
                        <?= $ai_confidence > 85 ? '✅ On track' : '⚠️ Needs attention' ?>
                    </p>

                    </div>
                    <div class="bg-highlight p-3 rounded-full">
                        <i class="fas fa-robot text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- ✅ GET STARTED CTA -->
            <div class="dashboard-card bg-white p-6 rounded-xl shadow md:col-span-2 gradient-border">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 text-sm">Set Your Fitness Goal</p>
                        <p class="text-2xl font-bold">Get Started!</p>
                        <p class="text-xs text-highlight mt-2">✨ 5-minute setup: goal, metrics, coach style</p>
                    </div>
                    <a href="profile.php#goal" class="btn-primary px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-bullseye mr-2"></i> Set Goal
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Attendance Calendar -->
                <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="p-6 border-b flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Attendance Calendar</h3>
                    <p class="text-gray-600">Track your gym visits</p>
                </div>
                <a href="attendance.php"
                class="text-highlight hover:underline font-medium text-sm">
                    View all
                </a>
            </div>

                    <div class="p-6">
                        <!-- Calendar Header -->
                        <div class="grid grid-cols-7 gap-1 mb-4">
                            <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                                <div class="text-center font-medium text-gray-500"><?= $d ?></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-7 gap-1">
                            <?php 
                            $current_month = date('m');
                            $current_year = date('Y');
                            $first_day = date('w', strtotime("$current_year-$current_month-01"));
                            $days_in_month = date('t', strtotime("$current_year-$current_month-01"));
                            
                            // Get attendance for current month
                            $stmt = $pdo->prepare("SELECT date, attended FROM attendance WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
                            $stmt->execute([$user_id, $current_year, $current_month]);
                            $attendance_map = [];
                            while ($row = $stmt->fetch()) {
                                $attendance_map[date('j', strtotime($row['date']))] = $row['attended'];
                            }
                            
                            // Empty cells before month
                            for ($i = 0; $i < $first_day; $i++) echo '<div class="h-12"></div>';
                            
                            // Days
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $status = $attendance_map[$day] ?? null;
                                $classes = "h-12 flex flex-col items-center justify-center relative";
                                $dot_class = "";
                                
                                if ($status !== null) {
                                    $classes .= $status ? " bg-green-100" : " bg-red-100";
                                    $dot_class = $status ? "bg-success" : "bg-red-500";
                                }
                                
                                echo "<div class=\"$classes\">";
                                echo "<span class=\"text-gray-700\">$day</span>";
                                if ($dot_class) echo "<span class=\"attendance-dot $dot_class mt-1\"></span>";
                                echo "</div>";
                            }
                            
                            // Fill grid
                            $total = 42;
                            $filled = $first_day + $days_in_month;
                            for ($i = 0; $i < ($total - $filled); $i++) echo '<div class="h-12"></div>';
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Workout Log -->
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Workout History</h3>
                            <p class="text-gray-600">Your recent training sessions</p>
                        </div>
                        <a href="workouts.php" class="text-highlight hover:underline font-medium text-sm flex items-center">
                            View All 
                        </a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($workouts)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-dumbbell text-4xl mb-4 text-gray-300"></i>
                                <p>No workouts logged yet. Get moving!</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php 
                                // Only show 3 most recent workouts
                                $recent_workouts = array_slice($workouts, 0, 3);
                                foreach ($recent_workouts as $workout): 
                                ?>
                                <div class="flex items-center justify-between p-4 border rounded-lg">
                                    <div class="flex items-center">
                                        <div class="bg-green-100 p-3 rounded-full mr-4">
                                            <i class="fas fa-running text-green-500"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold"><?= htmlspecialchars($workout['type']) ?></h4>
                                            <p class="text-gray-600 text-sm"><?= date('M j', strtotime($workout['date'])) ?> • <?= $workout['duration'] ?> mins</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold"><?= $workout['calories_burned'] ?> kcal</p>
                                        <p class="text-gray-600 text-sm"><?= $workout['mood'] ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="space-y-8">
                <!-- ✅ AI COACH CHAT PREVIEW -->
                <div class="gradient-border">
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="p-6 border-b bg-primary flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="bg-highlight p-2 rounded-full mr-3">
                                    <i class="fas fa-robot text-white"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white">Warzone AI Coach</h3>
                            </div>
                            <a href="chat.php" class="text-white hover:text-highlight text-sm">
                                <i class="fas fa-arrow-right mr-1"></i> Full Chat
                            </a>
                        </div>
                        <div class="p-4 h-64 overflow-y-auto bg-gray-50">
                            <?php 
                            $ai_messages = [
                                "How’s your day going, ka-warzone? Ready to move?",
                                "Pro tip: Drink 500ml water 30 mins before training — boosts performance by 12%.",
                                "Your last squat: 60kg. Ready for 62.5kg today? 💪",
                                "Ever heard? Rest days > extra reps. Recovery builds muscle.",
                                "Mood check: 😊 or 😁 today? Log it in your Journal!"
                            ];
                            shuffle($ai_messages);
                            ?>
                            <div class="ai-bubble bg-gray-200 text-gray-800 p-3 rounded-2xl">
                                <p><?= $ai_messages[0] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ✅ JOURNEY JOURNAL PREVIEW -->
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Journey Journal</h3>
                            <p class="text-gray-600">Your mood & progress tracker</p>
                        </div>
                        <a href="journal.php" class="text-highlight hover:underline text-sm">View All</a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($mood_history)): ?>
                            <div class="text-center py-6">
                                <i class="fas fa-book-open text-3xl text-gray-300"></i>
                                <p class="mt-2">No entries yet. Start your journey!</p>
                                <button id="quickJournalBtn" class="mt-3 btn-primary px-3 py-1 text-sm">
                                    <i class="fas fa-plus mr-1"></i> Quick Log
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($mood_history, 0, 3) as $entry): ?>
                            <div class="journal-card <?= $entry['archived'] ? 'journal-archived' : '' ?> p-3 rounded-lg mb-3 bg-gray-50 border">
                                <div class="journal-actions">
                                    <?php if (!$entry['archived']): ?>
                                        <!-- <button class="text-gray-500 hover:text-red-500" onclick="archiveJournal(<?= $entry['id'] ?>)">
                                            <i class="fas fa-archive"></i>
                                        </button> -->
                                    <?php endif; ?>
                                    <button class="text-gray-500 hover:text-yellow-500" onclick="toggleStar(<?= $entry['id'] ?>, <?= $entry['starred'] ? '1' : '0' ?>)">
                                        <i class="fas fa-star<?= $entry['starred'] ? '' : '-outline' ?> journal-star"></i>
                                    </button>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl mr-2"><?= $entry['mood'] ?></span>
                                    <span class="font-medium"><?= date('M j', strtotime($entry['date'])) ?></span>
                                    <?php if ($entry['starred']): ?>
                                        <i class="fas fa-star text-yellow-500 ml-2"></i>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($entry['notes'])): ?>
                                    <p class="text-gray-700 mt-1 text-sm"><?= htmlspecialchars($entry['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ✅ FLOATING AI BUTTON -->
    <a href="chat.php" class="ai-float-btn pulse" title="Chat with Warzone AI">
        <i class="fas fa-robot text-xl"></i>
    </a>

<!-- ✅ Workout Log Modal -->
<!-- ✅ Workout Log Modal -->
<div id="workoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Log Your Workout</h3>
            <button id="closeWorkoutModal" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="workoutForm" class="space-y-4">
            <input type="hidden" name="log_workout" value="1">
            <input type="hidden" name="ajax" value="1">

            <div>
                <label class="block text-gray-700 mb-2">Workout Type</label>
                <select name="workout_type" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" required>
                    <option value="">Select type</option>
                    <option value="Cardio">Cardio</option>
                    <option value="Upper Body">Upper Body</option>
                    <option value="Lower Body">Lower Body</option>
                    <option value="Full Body">Full Body</option>
                    <option value="HIIT">HIIT</option>
                    <option value="Yoga">Yoga</option>
                    <option value="CrossFit">CrossFit</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 mb-2">Duration (mins)</label>
                    <input type="number" name="duration" min="1" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" required>
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Calories Burned</label>
                    <input type="number" name="calories" min="0" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight">
                </div>
            </div>

            <div>
                <label class="block text-gray-700 mb-2">Workout Notes</label>
                <textarea name="workout_notes" placeholder="Describe your workout..." class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" rows="3"></textarea>
            </div>

            <!-- ✅ FIXED MOOD SELECTION -->
            <div>
                <label class="block text-gray-700 mb-2">How are you feeling?</label>
                <div class="flex justify-around">
                    <?php $moods = ['😠','😐','😊','😁']; 
                    $labels = ['Frustrated', 'Neutral', 'Happy', 'Energized'];
                    foreach ($moods as $index => $mood): ?>
                        <div class="flex flex-col items-center">
                            <input type="radio" 
                                   id="mood_<?= $index ?>" 
                                   name="workout_mood" 
                                   value="<?= $mood ?>" 
                                   class="peer hidden" 
                                   <?= $mood === '😊' ? 'checked' : '' ?>>
                            <label for="mood_<?= $index ?>" 
                                   class="mood-btn w-12 h-12 rounded-full flex items-center justify-center bg-gray-100 
                                          peer-checked:bg-highlight peer-checked:text-white 
                                          peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-highlight
                                          cursor-pointer transition-all duration-200">
                                <span class="text-xl"><?= $mood ?></span>
                            </label>
                            <span class="mt-1 text-xs text-gray-600 peer-checked:text-highlight"><?= $labels[$index] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="w-full btn-primary py-3 rounded-lg">
                <i class="fas fa-dumbbell mr-2"></i> Log Workout
            </button>
        </form>
    </div>
</div>

<!-- ✅ Workout Response Modal (unchanged) -->
<div id="workoutResponseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl w-full max-w-md p-6 text-center">
        <i id="workoutModalIcon" class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
        <h3 id="workoutModalTitle" class="text-xl font-bold text-gray-800 mb-2">Success!</h3>
        <p id="workoutModalMessage" class="text-gray-600 mb-4">Workout logged successfully!</p>
        <button id="workoutModalClose" class="px-4 py-2 bg-highlight text-white rounded-lg">OK</button>
    </div>
</div>

<style>
    .mood-btn {
        transition: all 0.2s ease;
    }
    .mood-btn:hover {
        transform: scale(1.1);
    }
</style>

<script>
// ✅ Workout Modal Control
document.addEventListener('DOMContentLoaded', function() {
    // Open workout modal
    document.getElementById('logWorkoutBtn')?.addEventListener('click', () => {
        document.getElementById('workoutModal').classList.remove('hidden');
    });
    
    // Close workout modal
    document.getElementById('closeWorkoutModal')?.addEventListener('click', () => {
        document.getElementById('workoutModal').classList.add('hidden');
    });
    
    // Close on outside click
    document.getElementById('workoutModal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) {
            e.currentTarget.classList.add('hidden');
        }
    });

    // ✅ Workout form submission
    document.getElementById('workoutForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('dashboard.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            // Show response
            const modal = document.getElementById('workoutResponseModal');
            const icon = document.getElementById('workoutModalIcon');
            const title = document.getElementById('workoutModalTitle');
            const message = document.getElementById('workoutModalMessage');
            
            if (result.success) {
                icon.className = 'fas fa-check-circle text-green-500 text-4xl mb-4';
                title.textContent = 'Success!';
                message.textContent = result.message || 'Workout logged successfully!';
            } else {
                icon.className = 'fas fa-exclamation-triangle text-red-500 text-4xl mb-4';
                title.textContent = 'Error';
                message.textContent = result.message || 'Failed to log workout.';
            }
            
            modal.classList.remove('hidden');
            
            // Close and reload on success
            document.getElementById('workoutModalClose').onclick = () => {
                modal.classList.add('hidden');
                if (result.success) {
                    location.reload();
                }
            };
        } catch (err) {
            // Show error
            document.getElementById('workoutModalIcon').className = 'fas fa-exclamation-triangle text-red-500 text-4xl mb-4';
            document.getElementById('workoutModalTitle').textContent = 'Error';
            document.getElementById('workoutModalMessage').textContent = 'Network error. Please try again.';
            document.getElementById('workoutResponseModal').classList.remove('hidden');
        }
    });

    // Close workout response modal
    document.getElementById('workoutModalClose')?.addEventListener('click', () => {
        document.getElementById('workoutResponseModal').classList.add('hidden');
    });
    
    document.getElementById('workoutResponseModal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) {
            e.currentTarget.classList.add('hidden');
        }
    });
});
</script>

    <!-- ✅ QUICK JOURNAL MODAL -->
    <div id="journalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Journey Journal</h3>
                <button id="closeJournalModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="journal_action" value="add">
                <input type="hidden" name="journal_date" value="<?= date('Y-m-d') ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">How are you feeling?</label>
                    <div class="grid grid-cols-4 gap-2">
                        <?php foreach (['😠', '😐', '😊', '😁'] as $mood): ?>
                            <label class="flex flex-col items-center cursor-pointer">
                                <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                    <input type="radio" name="journal_mood" value="<?= $mood ?>" 
                                           class="hidden" <?= $mood === '😊' ? 'checked' : '' ?>>
                                    <span class="text-2xl"><?= $mood ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Journal Entry</label>
                    <textarea name="journal_notes" rows="3" 
                              class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight"
                              placeholder="What's on your mind? Progress, struggles, wins?"></textarea>
                </div>
                
                <button type="submit" class="w-full btn-primary py-3 rounded-lg">
                    <i class="fas fa-save mr-2"></i> Save Entry
                </button>
            </form>
        </div>
    </div>

    <footer class="bg-primary text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-dumbbell text-highlight text-2xl"></i>
                        <h2 class="text-xl font-bold">Warzone Gym CRM</h2>
                    </div>
                    <p class="text-gray-300">AI-powered fitness coaching for serious athletes</p>
                </div>
                <div>
                    <p class="text-gray-300 text-sm">Developed by: BayaniH4ck</p>
                    <p class="text-gray-400 text-xs mt-1">
                        • Lebantino, Aldwin C.<br>
                        • Cortado, Crisdhan Harben D.<br>
                        • Gagarin, Vincent Yuri P.<br>
                        • Tejada, John Lloyd R.
                    </p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400 text-sm">
                <p>© 2026 Warzone Gym CRM. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // ✅ Notifications dropdown
        document.getElementById('notificationBtn').addEventListener('click', function() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            const btn = document.getElementById('notificationBtn');
            const dropdown = document.getElementById('notificationDropdown');
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // ✅ Workout modal
        document.getElementById('logWorkoutBtn')?.addEventListener('click', () => {
            document.getElementById('workoutModal').classList.remove('hidden');
        });

        // Close workout modal
        document.getElementById('closeWorkoutModal')?.addEventListener('click', () => {
            document.getElementById('workoutModal').classList.add('hidden');
        });

        // ✅ Journal quick log
        document.getElementById('quickJournalBtn')?.addEventListener('click', () => {
            document.getElementById('journalModal').classList.remove('hidden');
        });
        document.getElementById('closeJournalModal')?.addEventListener('click', () => {
            document.getElementById('journalModal').classList.add('hidden');
        });

        // ✅ Journal actions (archive/star)
        function archiveJournal(id) {
            if (confirm('Archive this entry?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="journal_id" value="${id}">
                    <input type="hidden" name="journal_action" value="archive">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        function toggleStar(id, isStarred) {
            const action = isStarred ? 'unstar' : 'star';
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="journal_id" value="${id}">
                <input type="hidden" name="journal_action" value="${action}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // ✅ Close AI popups
        document.querySelectorAll('.ai-popup .close').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.parentElement.style.display = 'none';
            });
        });
        // Auto-check for new notifications every 30s
        setInterval(async () => {
            try {
                const res = await fetch('notifications.php?action=get_count');
                const data = await res.json();
                
                const badge = document.querySelector('.notification-badge');
                if (data.count > 0) {
                    if (!badge) {
                        const btn = document.getElementById('notificationBtn');
                        btn.innerHTML += `<span class="notification-badge">${data.count > 9 ? '9+' : data.count}</span>`;
                    } else {
                        badge.textContent = data.count > 9 ? '9+' : data.count;
                    }
                } else if (badge) {
                    badge.remove();
                }
            } catch (err) {
                console.warn('Failed to refresh notifications', err);
            }
        }, 30000);
    </script>
    <?php include 'modal_logout.php'; ?>
    <script>
// ✅ Notification System
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('notificationBtn');
            const dropdown = document.getElementById('notificationDropdown');
            const markAllBtn = document.getElementById('markAllRead');
            
            if (!btn || !dropdown) return;

            // Toggle dropdown
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });

            // Mark all as read
            markAllBtn?.addEventListener('click', async () => {
                try {
                    const res = await fetch('notifications.php?action=mark_all_read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    if (res.ok) {
                        // Update UI
                        document.querySelectorAll('.notification-item').forEach(el => {
                            el.classList.remove('bg-blue-50');
                            el.classList.add('text-gray-500');
                        });
                        document.querySelector('.notification-badge')?.remove();
                        markAllBtn.style.display = 'none';
                    }
                } catch (err) {
                    console.error('Failed to mark as read', err);
                }
            });

            // Mark single as read on click
            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', async () => {
                    const id = item.dataset.id;
                    try {
                        const res = await fetch('notifications.php?action=mark_read', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        if (res.ok) {
                            // Update UI
                            item.classList.remove('bg-blue-50');
                            item.classList.add('text-gray-500');
                            item.querySelector('.bg-highlight')?.parentElement?.remove(); // remove dot
                            
                            // Update badge count
                            const badge = document.querySelector('.notification-badge');
                            if (badge) {
                                let count = parseInt(badge.textContent);
                                count--;
                                if (count <= 0) {
                                    badge.remove();
                                } else {
                                    badge.textContent = count > 9 ? '9+' : count;
                                }
                            }
                        }
                    } catch (err) {
                        console.error('Failed to mark notification', err);
                    }
                });
            });
        });

        // ✅ Goal Progress Modal
        document.getElementById('goalProgressCard')?.addEventListener('click', () => {
            document.getElementById('goalProgressModal').classList.remove('hidden');
        });

        document.getElementById('closeGoalModal')?.addEventListener('click', () => {
            document.getElementById('goalProgressModal').classList.add('hidden');
        });

        // Close on backdrop click
        document.getElementById('goalProgressModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'goalProgressModal') {
                e.target.classList.add('hidden');
            }
        });

        </script>

        <style>
        /* Enhanced notification styles */
        .notification-badge {
            position: absolute; top: -4px; right: -4px; 
            background: #e94560; color: white; font-size: 0.7rem; 
            width: 18px; height: 18px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            font-weight: bold;
        }
        .notification-dropdown {
            position: absolute; top: 100%; right: 0; margin-top: 8px; 
            width: 360px; background: white; border-radius: 0.75rem; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 50; 
            display: none; overflow: hidden;
        }
        .notification-item { border-bottom: 1px solid #f1f5f9; }
        .notification-item:last-child { border-bottom: none; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        </style>

        <div id="goalProgressModal"
            class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Goal Progress Breakdown</h3>
                    <button id="closeGoalModal" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>

                </div>

                <div class="space-y-4 text-sm text-gray-700">
                    <div class="flex justify-between">
                        <span>Base Progress</span>
                        <span class="font-semibold">50%</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Attendance (<?= $attendance_rate ?>%)</span>
                        <span class="font-semibold">
                            +<?= round($attendance_rate * 0.3) ?>%
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span>Workouts Logged</span>
                        <span class="font-semibold">
                            +<?= count($workouts) * 5 ?>%
                        </span>
                    </div>

                    <hr>

                    <div class="flex justify-between text-lg font-bold">
                        <span>Total Progress</span>
                        <span class="text-highlight"><?= $goal_progress ?>%</span>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mt-4">
                    This score reflects your consistency based on attendance and workout activity.
                </p>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {

                const goalCard = document.getElementById('goalProgressCard');
                const goalModal = document.getElementById('goalProgressModal');
                const closeBtn = document.getElementById('closeGoalModal');

                if (!goalModal) return;

                // Open modal
                goalCard?.addEventListener('click', () => {
                    goalModal.classList.remove('hidden');
                });

                // Close via X button
                closeBtn?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    goalModal.classList.add('hidden');
                });

                // Close when clicking outside modal
                goalModal.addEventListener('click', (e) => {
                    if (e.target === goalModal) {
                        goalModal.classList.add('hidden');
                    }
                });

                // ESC key close (bonus UX)
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !goalModal.classList.contains('hidden')) {
                        goalModal.classList.add('hidden');
                    }
                });

            });
            document.getElementById('workoutModal').addEventListener('click', (e) => {
                if (e.target.id === 'workoutModal') {
                    e.target.classList.add('hidden');
                }
            });

        </script>

        <form id="journalActionForm" method="POST" style="display:none;">
            <input type="hidden" name="journal_id" id="journalActionId">
            <input type="hidden" name="journal_action" id="journalActionType">
        </form>

</body>
</html>