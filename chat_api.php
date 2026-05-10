<?php
// chat_api.php - AJAX Backend for Chat
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_coach.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['error' => 'Empty message']);
        exit();
    }

    // Save user message
    $stmt = $pdo->prepare("INSERT INTO chats (user_id, message, is_ai) VALUES (?, ?, 0)");
    $stmt->execute([$user_id, $message]);
    $user_msg_id = $pdo->lastInsertId();

    // Get context + AI response
    $context = getUserContext($pdo, $user_id);
    $ai_response = get_ai_response($message, $context);

    // Save AI reply
    $stmt = $pdo->prepare("INSERT INTO chats (user_id, message, is_ai) VALUES (?, ?, 1)");
    $stmt->execute([$user_id, $ai_response]);
    $ai_msg_id = $pdo->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'user_message' => [
            'id' => $user_msg_id,
            'message' => $message,
            'is_ai' => 0,
            'time' => date('g:i A')
        ],
        'ai_message' => [
            'id' => $ai_msg_id,
            'message' => $ai_response,
            'is_ai' => 1,
            'time' => date('g:i A')
        ]
    ]);
    exit();
}

if ($action === 'poll') {
    $last_id = (int)($_GET['last_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, message, is_ai, created_at FROM chats WHERE user_id = ? AND id > ? ORDER BY created_at ASC");
    $stmt->execute([$user_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted = array_map(function($m) {
        return [
            'id' => $m['id'],
            'message' => $m['message'],
            'is_ai' => (int)$m['is_ai'],
            'time' => date('g:i A', strtotime($m['created_at']))
        ];
    }, $messages);

    echo json_encode(['messages' => $formatted]);
    exit();
}

echo json_encode(['error' => 'Invalid action']);
