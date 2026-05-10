<?php
// dashboard.php - Modularized Dashboard
require_once 'auth.php';

require_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_data = get_user_data($user_id);
$attendance_rate = calculate_attendance_rate($user_id);
$workouts = get_recent_workouts($user_id, 30); // Get more for analytics

// Get mood data
$stmt = $pdo->prepare("SELECT * FROM mood_checkins WHERE user_id = ? ORDER BY date DESC LIMIT 10");
$stmt->execute([$user_id]);
$mood_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Logic for goal progress
$goal_progress = 0;
$ai_confidence = 0;
if (!empty($user_data['fitness_goal'])) {
    $goal_progress = min(100, 50 + ($attendance_rate * 0.3) + (count($workouts) * 5));
    $ai_confidence = min(100, 70 + ($attendance_rate * 0.2) + (count($workouts) * 3));
}

// Notifications logic
$notifications = [];
if (empty($workouts) || strtotime($workouts[0]['date']) < strtotime('-2 days')) {
    $notifications[] = ['title' => 'AI Reminder', 'message' => 'Missing you at the gym!', 'icon' => 'fas fa-robot', 'color' => 'highlight', 'time' => 'Now'];
}

// Handle POST actions (Journal & Workout)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die(json_encode(['success' => false, 'message' => 'Invalid token']));
    }

    // 1. Handle Workouts (AJAX)
    if (isset($_POST['log_workout'])) {
        header('Content-Type: application/json');
        $type = htmlspecialchars($_POST['type'] ?? 'Other');
        $duration = intval($_POST['duration'] ?? 30);
        $calories = intval($_POST['calories'] ?? 0);
        $mood = $_POST['mood'] ?? '😊';
        
        $stmt = $pdo->prepare("INSERT INTO workouts (user_id, date, type, duration, calories_burned, mood) VALUES (?, CURDATE(), ?, ?, ?, ?)");
        $success = $stmt->execute([$user_id, $type, $duration, $calories, $mood]);
        
        // Mark attendance
        $pdo->prepare("INSERT IGNORE INTO attendance (user_id, date, attended) VALUES (?, CURDATE(), 1)")->execute([$user_id]);
        
        log_activity($user_id, 'workout', "Logged $type workout ($duration mins)");
        echo json_encode(['success' => $success, 'message' => $success ? 'Workout logged!' : 'Failed to log workout.']);
        exit();
    }

    // 2. Handle Journal
    if (isset($_POST['journal_action'])) {
        $action = $_POST['journal_action'];
        $id = $_POST['journal_id'] ?? null;
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO mood_checkins (user_id, date, mood, notes) VALUES (?, CURDATE(), ?, ?)");
            $stmt->execute([$user_id, $_POST['journal_mood'], $_POST['journal_notes']]);
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE mood_checkins SET mood = ?, notes = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['journal_mood'], $_POST['journal_notes'], $id, $user_id]);
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM mood_checkins WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
        }
        header("Location: dashboard.php");
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#1a1a2e', secondary: '#16213e', accent: '#0f3460', highlight: '#e94560', success: '#06d6a0' } } }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; }
        .gradient-border { position: relative; border-radius: 0.75rem; }
        .gradient-border::after { content: ''; position: absolute; inset: -2px; background: linear-gradient(45deg, #e94560, #0f3460); z-index: -1; border-radius: 0.85rem; }
        .btn-primary { background: linear-gradient(135deg, #e94560 0%, #0f3460 100%); transition: all 0.3s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(233, 69, 96, 0.4); }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

    <?php include 'includes/user_nav.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Welcome, <?= htmlspecialchars($user_name) ?>!</h1>
                <p class="text-gray-500 italic">"The only bad workout is the one that didn't happen."</p>
            </div>
            <button id="logWorkoutBtn" class="btn-primary text-white px-8 py-3.5 rounded-xl font-bold flex items-center shadow-lg shadow-highlight/20">
                <i class="fas fa-plus-circle mr-2 text-xl"></i> Log Session
            </button>
        </div>

        <?php include 'includes/dashboard/stats_cards.php'; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left & Middle Column -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Performance Chart -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Training Analytics</h3>
                            <p class="text-xs text-gray-500">Your energy output over the last 7 sessions</p>
                        </div>
                        <div class="bg-gray-50 px-3 py-1 rounded-full text-[10px] font-bold text-gray-400 uppercase tracking-widest">Live Insight</div>
                    </div>
                    <div class="h-[300px]">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php include 'includes/dashboard/attendance_calendar.php'; ?>
                    <?php include 'includes/dashboard/recent_workouts.php'; ?>
                </div>

                <?php include 'includes/dashboard/journal_section.php'; ?>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                <?php include 'includes/dashboard/ai_coach_card.php'; ?>
                <?php include 'includes/dashboard/activity_sidebar.php'; ?>
                
                <button id="quickJournalBtn" class="w-full bg-white border-2 border-dashed border-gray-200 p-6 rounded-2xl text-gray-400 hover:border-highlight hover:text-highlight hover:bg-highlight/5 transition-all flex flex-col items-center justify-center group">
                    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-2 group-hover:bg-highlight/10">
                        <i class="fas fa-pen-fancy"></i>
                    </div>
                    <span class="font-bold text-sm">Quick Reflection</span>
                    <span class="text-[10px] mt-1">Log your mood in 10 seconds</span>
                </button>
            </div>
        </div>
    </main>

    <footer class="mt-auto py-10 bg-white border-t text-center">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-center space-x-2 mb-4">
                <i class="fas fa-dumbbell text-highlight"></i>
                <span class="font-bold text-gray-800 tracking-tighter text-lg">WARZONE <span class="font-light text-gray-400">CRM</span></span>
            </div>
            <p class="text-gray-400 text-xs tracking-widest uppercase mb-2">Designed for the 1%</p>
            <p class="text-gray-300 text-[10px]">&copy; 2026 Warzone Gym Ecosystem. Built by BayaniH4ck.</p>
        </div>
    </footer>

    <?php include 'includes/dashboard/modals.php'; ?>

    <!-- Persistent Action Form for Journal -->
    <form id="journalActionForm" method="POST" style="display:none;">
        <?= csrf_field() ?>
        <input type="hidden" name="journal_id" id="journalActionId">
        <input type="hidden" name="journal_action" id="journalActionType">
        <input type="hidden" name="journal_mood" value="😐"> <!-- Default to allow POST -->
    </form>

    <script>
        // Performance Chart Initialization
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            const workoutData = <?= json_encode(array_reverse(array_slice($workouts, 0, 7))) ?>;
            
            if (workoutData.length === 0) {
                ctx.font = "14px Outfit";
                ctx.fillStyle = "#9ca3af";
                ctx.textAlign = "center";
                ctx.fillText("Log a workout to see analytics", ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }

            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(233, 69, 96, 0.2)');
            gradient.addColorStop(1, 'rgba(233, 69, 96, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: workoutData.map(w => new Date(w.date).toLocaleDateString('en-US', {weekday: 'short'})),
                    datasets: [{
                        label: 'Calories',
                        data: workoutData.map(w => w.calories_burned),
                        borderColor: '#e94560',
                        borderWidth: 4,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.5,
                        pointRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#e94560',
                        pointBorderWidth: 3,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: '#e94560',
                        pointHoverBorderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1a1a2e',
                            titleFont: { family: 'Outfit', size: 12 },
                            bodyFont: { family: 'Outfit', size: 14 },
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: (ctx) => `${ctx.raw} kcal burned`
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f3f4f6', borderDash: [5, 5] },
                            ticks: { font: { family: 'Outfit', size: 10 }, color: '#9ca3af' }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { family: 'Outfit', size: 10 }, color: '#9ca3af' }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>