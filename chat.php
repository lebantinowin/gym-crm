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

// Get available personas
$stmt = $pdo->query("SELECT name FROM ai_personas WHERE status = 'active'");
$available_personas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get & set coach style
$stmt = $pdo->prepare("SELECT coach_style FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$style = $stmt->fetchColumn() ?: ($available_personas[0] ?? 'Balanced');
$_SESSION['user_coach_style'] = $style;

// Update coach style
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_coach_style'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Invalid token");
    }
    $new_style = $_POST['coach_style'];
    if (in_array($new_style, $available_personas)) {
        $stmt = $pdo->prepare("UPDATE users SET coach_style = ? WHERE id = ?");
        $stmt->execute([$new_style, $user_id]);
        $_SESSION['user_coach_style'] = $new_style;
        header("Location: chat.php");
        exit();
    }
}

// Process new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Invalid token");
    }

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

        // 🛡️ PRUNING: Keep only the latest 100 messages to prevent database bloat
        $stmt = $pdo->prepare("
            DELETE FROM chats 
            WHERE user_id = ? 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM chats 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 100
                ) x
            )
        ");
        $stmt->execute([$user_id, $user_id]);
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
    <!-- Navigation Bar -->
    <?php include 'includes/user_nav.php'; ?>

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
        <?= csrf_field() ?>
        <input type="hidden" name="set_coach_style" value="1">
        <?php foreach ($available_personas as $p_name): ?>
            <button type="submit" name="coach_style" value="<?= $p_name ?>"
                    class="px-4 py-2 text-sm rounded-xl font-bold transition-all
                           <?= $_SESSION['user_coach_style'] === $p_name
                               ? 'bg-highlight text-white shadow-lg shadow-highlight/20 scale-105'
                               : 'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">
                <?= htmlspecialchars($p_name) ?>
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
    <form id="chatForm" class="flex items-center">
        <input type="text" id="messageInput" name="message" placeholder="Ask Warzone anything..."
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
        const chatContainer = document.getElementById('chatContainer');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const csrfToken = '<?= $_SESSION['csrf_token'] ?>';
        let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;

        function scrollToBottom() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function appendMessage(msg) {
            const wrapper = document.createElement('div');
            wrapper.className = `message-wrapper ${msg.is_ai ? 'ai-wrapper' : ''}`;
            
            const bubble = document.createElement('div');
            bubble.className = `${msg.is_ai ? 'ai-bubble' : 'user-bubble'} chat-bubble`;
            bubble.innerHTML = `<p>${escapeHtml(msg.message)}</p>`;
            
            const time = document.createElement('div');
            time.className = 'timestamp';
            time.textContent = msg.time;
            
            wrapper.appendChild(bubble);
            wrapper.appendChild(time);
            chatContainer.appendChild(wrapper);
            scrollToBottom();
            
            if (msg.id > lastMessageId) lastMessageId = msg.id;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            messageInput.value = '';
            messageInput.disabled = true;

            try {
                const response = await fetch('chat_api.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message, csrf_token: csrfToken })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    appendMessage(data.user_message);
                    setTimeout(() => appendMessage(data.ai_message), 500);
                }
            } catch (err) {
                console.error('Failed to send message:', err);
            } finally {
                messageInput.disabled = false;
                messageInput.focus();
            }
        });

        // SSE for real-time updates
        let eventSource;
        function initSSE() {
            if (eventSource) eventSource.close();
            eventSource = new EventSource(`chat_sse.php?last_id=${lastMessageId}`);
            
            eventSource.onmessage = (event) => {
                const msg = JSON.parse(event.data);
                if (msg.id > lastMessageId) {
                    appendMessage(msg);
                }
            };

            eventSource.onerror = () => {
                console.warn('SSE connection lost. Reconnecting in 5s...');
                eventSource.close();
                setTimeout(initSSE, 5000);
            };
        }

        initSSE();
        document.addEventListener('DOMContentLoaded', scrollToBottom);
    </script>
    <?php include 'modal_logout.php'; ?>
</body>
</html>