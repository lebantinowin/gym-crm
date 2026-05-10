<?php
// admin/messages.php - Messaging System
require_once '../auth.php';

require_admin();

$user_id = $_SESSION['user_id'];

// Pagination setup for conversations
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt_count = $pdo->prepare("
    SELECT COUNT(DISTINCT u.id)
    FROM messages m
    JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
");
$stmt_count->execute([$user_id, $user_id, $user_id]);
$total_conversations = $stmt_count->fetchColumn();
$total_pages = ceil($total_conversations / $limit);

// Get conversations
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.name, u.profile_picture,
        MAX(m.created_at) as last_message_date,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
    FROM messages m
    JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
    GROUP BY u.id, u.name, u.profile_picture
    ORDER BY last_message_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $user_id, PDO::PARAM_INT);
$stmt->bindValue(3, $user_id, PDO::PARAM_INT);
$stmt->bindValue(4, $user_id, PDO::PARAM_INT);
$stmt->bindValue(5, $limit, PDO::PARAM_INT);
$stmt->bindValue(6, $offset, PDO::PARAM_INT);
$stmt->execute();
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get messages for a specific conversation
$conversation_id = $_GET['conversation'] ?? null;
$messages = [];

if ($conversation_id) {
    // Mark messages as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$conversation_id, $user_id]);
    
    // Get messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name, u.profile_picture
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id, $user_id, $user_id, $conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);
        
        header('Location: messages.php?conversation=' . $receiver_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Messages</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* ---------- core palette from chat.php ---------- */
    :root{
        --primary:#1a1a2e;
        --secondary:#16213e;
        --accent:#0f3460;
        --highlight:#e94560;
    }

    /* ---------- global ---------- */
    body{ background:#f8f9fa; font-family:'Poppins',sans-serif; }

    /* ---------- layout helpers ---------- */
    .h-screen-content{ height:calc(100vh - 120px); }   /* nav + footer offset */
    .bubble{
        max-width:65%;
        word-wrap:break-word;
        padding:.8rem 1.1rem;
        line-height:1.45;
        border-radius:1.1rem;
        box-shadow:0 2px 4px rgba(0,0,0,.06);
    }
    .bubble-sent{
        background:linear-gradient(45deg,var(--highlight),var(--accent));
        color:#fff;
        border-bottom-right-radius:.4rem;
    }
    .bubble-received{
        background:#f1f5f9;
        color:#1f2937;
        border-bottom-left-radius:.4rem;
        border:1px solid #e2e8f0;
    }
    .timestamp{ font-size:.7rem; color:#94a3b8; margin-top:.25rem; }

    /* ---------- conversation list ---------- */
    .conv-item{ transition:background .2s; }
    .conv-item:hover{ background:#f8fafc; }
    .conv-active{ background:#eff6ff!important; border-right:3px solid var(--highlight); }

    /* ---------- message thread ---------- */
    #messageContainer{ scroll-behavior:smooth; }
    /* thin scrollbar */
    #messageContainer::-webkit-scrollbar{ width:6px; }
    #messageContainer::-webkit-scrollbar-thumb{ background:#cbd5e1; border-radius:3px; }

    /* ---------- input area ---------- */
    textarea:focus{ outline:none; box-shadow:0 0 0 2px var(--highlight); }
    nav{
        background:var(--primary,#1a1a2e);
        box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06);
    }
    nav a{
        color:#fff;
        transition:color .2s;
        font-family:'Poppins',sans-serif;
    }
    nav a:hover{
        color:var(--highlight,#e94560);
    }
    /* make the brand text behave like the chat.php link */
    nav .brand-link{
        font-size:1.25rem;
        font-weight:700;
    }
    /* logout icon same muted colour + hover as chat.php */
    nav .logout-icon{
        color:#9ca3af;
        transition:color .2s;
    }
    nav .logout-icon:hover{
        color:var(--highlight,#e94560);
    }
    /* thin red ring on profile pic hover (same as chat.php) */
    nav .profile-group:hover img{
        transform:scale(1.05);
        ring:2px solid var(--highlight,#e94560);
    }
</style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 md:ml-64 w-full flex flex-col">

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Messages</h1>
                <p class="text-gray-600">Communicate with gym members</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-[600px]">
            <!-- Conversations List -->
            <div class="lg:col-span-1 bg-white rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b">
                    <h3 class="font-bold text-gray-800">Conversations</h3>
                </div>
                <div class="overflow-y-auto max-h-[550px]">
                    <?php foreach ($conversations as $conversation): ?>
                    <a href="messages.php?conversation=<?= $conversation['id'] ?>" 
                       class="flex items-center p-4 hover:bg-gray-50 transition-colors border-b <?= $conversation['id'] == $conversation_id ? 'bg-blue-50' : '' ?>">
                        <img src="<?= htmlspecialchars(file_exists('../uploads/' . $conversation['profile_picture']) 
                                    ? '../uploads/' . $conversation['profile_picture'] 
                                    : '../uploads/default.png') ?>" 
                             alt="User" class="w-12 h-12 rounded-full mr-3">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-800 truncate"><?= htmlspecialchars($conversation['name']) ?></h4>
                            <p class="text-sm text-gray-500 truncate">
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <span class="text-highlight font-medium"><?= $conversation['unread_count'] ?> unread</span>
                                <?php else: ?>
                                    No unread messages
                                <?php endif; ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="p-3 border-t flex justify-center space-x-2 text-sm">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-2 py-1 border rounded hover:bg-gray-50">Prev</a>
                    <?php endif; ?>
                    <span class="px-2 py-1"><?= $page ?> / <?= $total_pages ?></span>
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-2 py-1 border rounded hover:bg-gray-50">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Message Thread -->
            <div class="lg:col-span-3 bg-white rounded-xl shadow flex flex-col">
                <?php if ($conversation_id): ?>
                    <?php 
                    // Get conversation partner info
                    $stmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
                    $stmt->execute([$conversation_id]);
                    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    
                    <!-- Chat Header -->
                    <div class="p-4 border-b flex items-center">
                        <img src="<?= htmlspecialchars(file_exists('../uploads/' . $partner['profile_picture']) 
                                    ? '../uploads/' . $partner['profile_picture'] 
                                    : '../uploads/default.png') ?>" 
                             alt="User" class="w-10 h-10 rounded-full mr-3">
                        <h3 class="font-bold text-gray-800"><?= htmlspecialchars($partner['name']) ?></h3>
                    </div>

                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messageContainer">
                        <?php foreach ($messages as $message): ?>
                        <div class="flex <?= $message['sender_id'] == $user_id ? 'justify-end' : 'justify-start' ?>">
                            <div class="message-bubble px-4 py-2 rounded-lg 
                                        <?= $message['sender_id'] == $user_id ? 'message-sent' : 'message-received' ?>">
                                <p><?= htmlspecialchars($message['message']) ?></p>
                                <p class="text-xs mt-1 opacity-75"><?= date('g:i A', strtotime($message['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Message Input -->
                    <div class="p-4 border-t">
                        <form method="POST" class="flex space-x-2">
                            <input type="hidden" name="receiver_id" value="<?= $conversation_id ?>">
                            <textarea name="message" rows="2" class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight resize-none" 
                                      placeholder="Type your message..." required></textarea>
                            <button type="submit" name="send_message" class="bg-highlight text-white px-4 py-2 rounded-lg hover:bg-opacity-90 transition">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="flex-1 flex items-center justify-center text-gray-500">
                        <div class="text-center">
                            <i class="fas fa-comments text-5xl mb-4 text-gray-300"></i>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Select a conversation</h3>
                            <p>Choose a conversation from the list to start messaging</p>
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
</body>
</html>