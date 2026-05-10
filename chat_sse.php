<?php
// chat_sse.php - Server-Sent Events for Chat
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

if (!is_logged_in()) {
    echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
    exit();
}

$user_id = $_SESSION['user_id'];
$last_id = (int)($_GET['last_id'] ?? 0);

// Set execution time limit to 0 to keep the script running
set_time_limit(0);

while (true) {
    if (connection_aborted()) break;

    $stmt = $pdo->prepare("SELECT id, message, is_ai, created_at FROM chats WHERE user_id = ? AND id > ? ORDER BY id ASC");
    $stmt->execute([$user_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($messages)) {
        foreach ($messages as $m) {
            $data = [
                'id' => $m['id'],
                'message' => $m['message'],
                'is_ai' => (int)$m['is_ai'],
                'time' => date('g:i A', strtotime($m['created_at']))
            ];
            echo "data: " . json_encode($data) . "\n\n";
            $last_id = $m['id'];
        }
        ob_flush();
        flush();
    }

    sleep(1); // Check every second
}
