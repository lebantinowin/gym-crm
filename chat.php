<?php
// chat.php — Persistent AI Coach Chat (v4.0)

// // DEBUG: Check if API key is available
// if (!defined('QWEN_API_KEY') || QWEN_API_KEY === '') {
//     die("<pre style='color:red'>❌ FATAL: QWEN_API_KEY is missing or empty.<br>Check config.php and .env</pre>");
// }
// echo "<pre style='color:green'>✅ QWEN_API_KEY loaded (first 5 chars: " . substr(QWEN_API_KEY, 0, 5) . "****)</pre>";
// // Remove this block once fixed

require_once __DIR__ . '/config.php';      // 1. DB + $pdo
require_once __DIR__ . '/ai_coach.php';    // 2. Smart AI functions
require_once __DIR__ . '/auth.php';        // 3. Auth (now safe)
require_login();

$user_id = $_SESSION['user_id'];

// Get & set coach style
$stmt = $pdo->prepare("SELECT coach_style FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$style = $stmt->fetchColumn() ?: 'balanced';
$_SESSION['user_coach_style'] = $style;

// Update coach style
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_coach_style'])) {
    $new_style = $_POST['coach_style'];
    if (in_array($new_style, ['gentle', 'balanced', 'hardcore'])) {
        $stmt = $pdo->prepare("UPDATE users SET coach_style = ? WHERE id = ?");
        $stmt->execute([$new_style, $user_id]);
        $_SESSION['user_coach_style'] = $new_style;
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Process new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        // Save user message
        $stmt = $pdo->prepare("INSERT INTO chats (user_id, message, is_ai) VALUES (?, ?, 0)");
        $stmt->execute([$user_id, $message]);

        // Get context + AI response
        $context = getUserContext($pdo, $user_id);
        $ai_response = get_ai_response($message, $context);

        // Save AI reply
        $stmt = $pdo->prepare("INSERT INTO chats (user_id, message, is_ai) VALUES (?, ?, 1)");
        $stmt->execute([$user_id, $ai_response]);
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Load chat history
$stmt = $pdo->prepare("SELECT message, is_ai, created_at FROM chats WHERE user_id = ? ORDER BY created_at ASC");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - AI Coach</title>
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
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .chat-container {
            height: 40vh; overflow-y: auto; padding: 1.5rem;
            background-color: #f1f5f9; border-radius: 1rem;
            display: flex; flex-direction: column;
        }
        .user-bubble { background-color: #0f3460; color: white; border-bottom-right-radius: 0.5rem; align-self: flex-end; }
        .ai-bubble { background-color: #f1f5f9; color: #1e293b; border-bottom-left-radius: 0.5rem; border: 1px solid #e2e8f0; align-self: flex-start; }
        .chat-bubble { max-width: 75%; padding: 0.8rem 1rem; margin: 0.5rem 0; border-radius: 1rem; line-height: 1.5; }
        .message-wrapper { display: flex; flex-direction: column; align-items: flex-end; }
        .ai-wrapper { align-items: flex-start; }
        .timestamp { font-size: 0.7rem; color: #94a3b8; margin-top: 0.25rem; opacity: 0; transition: opacity 0.2s; }
        .message-wrapper:hover .timestamp { opacity: 1; }
    </style>
</head>
<body class="bg-gray-50">
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
                <div class="flex items-center space-x-3">
                    <!-- ✅ Profile Picture (desktop only) -->
                    <a href="profile.php" class="hidden md:flex items-center space-x-2 group" title="View Profile">
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
                    <!-- Mobile: hamburger -->
                    <button id="userNavToggle" class="md:hidden text-white focus:outline-none p-1" aria-label="Open menu">
                        <i class="fas fa-bars text-xl" id="userNavIcon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <!-- Mobile nav drawer -->
    <div id="userNavDrawer" class="md:hidden hidden bg-primary text-white border-t border-gray-800 shadow-lg sticky top-0 z-40">
        <div class="px-4 py-3 space-y-1">
            <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-home w-6"></i> Dashboard
            </a>
            <a href="workouts.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-dumbbell w-6"></i> Workouts
            </a>
            <a href="attendance.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-calendar-check w-6"></i> Attendance
            </a>
            <a href="journal.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-book w-6"></i> Journal
            </a>
            <a href="chat.php" class="flex items-center px-4 py-3 rounded-lg bg-highlight text-white">
                <i class="fas fa-robot w-6"></i> AI Coach
            </a>
            <a href="profile.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-user w-6"></i> Profile
            </a>
            <a href="logout.php" class="flex items-center px-4 py-3 rounded-lg text-gray-400 hover:text-highlight hover:bg-secondary">
                <i class="fas fa-sign-out-alt w-6"></i> Logout
            </a>
        </div>
    </div>
    <script>
    (function() {
        const toggle = document.getElementById('userNavToggle');
        const drawer = document.getElementById('userNavDrawer');
        const icon   = document.getElementById('userNavIcon');
        if (toggle && drawer) {
            toggle.addEventListener('click', function() {
                const isOpen = !drawer.classList.contains('hidden');
                drawer.classList.toggle('hidden', isOpen);
                icon.className = isOpen ? 'fas fa-bars text-xl' : 'fas fa-times text-xl';
            });
        }
    })();
    </script>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center mb-6">
                <div class="bg-highlight p-3 rounded-full mr-4">
                    <i class="fas fa-robot text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Warzone AI Coach</h2>
                    <p class="text-gray-600">Your adaptive, science-backed, Filipino-rooted fitness partner</p>
                </div>
            </div>

            <!-- Coach Style -->
<!-- 1.  Coach-style picker – becomes a vertical stack on mobile -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between
            bg-white p-4 rounded-lg border shadow-sm gap-3">
    <div>
        <h3 class="font-bold text-gray-800">Coaching Style</h3>
        <p class="text-sm text-gray-600">How should Warzone talk to you?</p>
    </div>
    <form method="POST" class="flex flex-wrap gap-2">
        <input type="hidden" name="set_coach_style" value="1">
        <?php foreach (['gentle'   => '🌿 Gentle',
                        'balanced' => '⚖️ Balanced',
                        'hardcore' => '💀 Hardcore'] as $val => $label): ?>
            <button type="submit" name="coach_style" value="<?= $val ?>"
                    class="px-3 py-1 text-sm rounded-full whitespace-nowrap
                           <?= $_SESSION['user_coach_style'] === $val
                               ? 'bg-highlight text-white'
                               : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                <?= htmlspecialchars($label) ?>
            </button>
        <?php endforeach; ?>
    </form>
</div>

            <!-- Chat -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="chat-container" id="chatContainer">
                    <?php if (empty($messages)): ?>
                        <div class="message-wrapper ai-wrapper">
                            <div class="ai-bubble chat-bubble">
                                <p>✨ <strong>Welcome, ka-warzone.</strong></p>
                                <p class="mt-2">I’m not a bot—I’m your coach: part scientist, part lolo, part drill sergeant.</p>
                                <p class="mt-1">Set your style above, then ask me anything:</p>
                                <ul class="list-disc list-inside text-sm mt-1">
                                    <li>“How do I lose fat without starving?”</li>
                                    <li>“Give me a home workout with no equipment”</li>
                                    <li>“Is adobo healthy?”</li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-wrapper <?= $msg['is_ai'] ? 'ai-wrapper' : '' ?>">
                                <div class="<?= $msg['is_ai'] ? 'ai-bubble' : 'user-bubble' ?> chat-bubble">
                                    <p><?= htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="timestamp"><?= date('g:i A', strtotime($msg['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- 2.  Message bar – keeps everything on one line -->
<div class="p-4 border-t bg-white">
    <form method="POST" class="flex items-center">
        <input type="text" name="message" placeholder="Ask Warzone anything..."
               class="flex-1 min-w-0 border rounded-l-lg px-4 py-2
                      focus:outline-none focus:ring-2 focus:ring-highlight"
               required>
        <button type="submit"
                class="bg-highlight text-white px-4 py-2 rounded-r-lg
                       flex-shrink-0">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>

    <!-- suggested chips – still wrap if needed -->
    <div class="suggested-messages mt-2 flex flex-wrap gap-2">
        <?php
        $suggestions = [
            'Hi'                    => 'Hi',
            'Give me a home workout'=> 'Home workout',
            'How do I lose fat?'    => 'Fat loss',
            'Is adobo healthy?'     => 'Adobo',
            'I’m exhausted'         => 'Exhausted',
            'Sugod!'                => 'Sugod!'
        ];
        foreach ($suggestions as $msg => $label): ?>
            <button type="button"
                    class="suggested-message bg-gray-200 hover:bg-gray-300
                           px-3 py-1 rounded-full text-sm"
                    onclick="document.querySelector('input[name=message]').value='<?= htmlspecialchars($msg) ?>'; this.blur();">
                <?= htmlspecialchars($label) ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>
            </div>
        </div>
    </main>

    <script>
        function scrollToBottom() {
            const c = document.getElementById('chatContainer');
            if (c) c.scrollTop = c.scrollHeight;
        }
        document.addEventListener('DOMContentLoaded', scrollToBottom);
        window.addEventListener('load', scrollToBottom);
    </script>
    <?php include 'modal_logout.php'; ?>
</body>
</html>