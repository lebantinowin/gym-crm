<?php
// api/messages.php - Unified Messaging Engine
require_once __DIR__ . '/bootstrap.php';

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET - Fetch Conversations or Specific Thread
if ($method === 'GET') {
    try {
        $contact_id = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : null;

        if ($contact_id) {
            // Fetch thread between current user and contact
            $stmt = $pdo->prepare("
                SELECT * FROM messages 
                WHERE (sender_id = ? AND receiver_id = ?) 
                OR (sender_id = ? AND receiver_id = ?) 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$user_id, $contact_id, $contact_id, $user_id]);
            $messages = $stmt->fetchAll();
            
            // Mark as read
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
            $stmt->execute([$user_id, $contact_id]);

            send_response(['messages' => $messages]);
        } else {
            // Fetch list of conversations
            $stmt = $pdo->prepare("
                SELECT 
                    u.id as contact_id, 
                    u.name as contact_name, 
                    u.profile_picture,
                    m.message as last_message, 
                    m.created_at as last_date,
                    (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND is_read = 0) as unread_count
                FROM users u
                JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
                WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
                AND m.id = (
                    SELECT id FROM messages 
                    WHERE (sender_id = ? AND receiver_id = u.id) 
                    OR (sender_id = u.id AND receiver_id = ?) 
                    ORDER BY created_at DESC LIMIT 1
                )
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
            $conversations = $stmt->fetchAll();
            
            send_response(['conversations' => $conversations]);
        }
    } catch (PDOException $e) {
        send_response(null, 500, 'Fetch failed: ' . $e->getMessage());
    }
}

// 2. POST - Send Message
if ($method === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        send_response(null, 403, 'Invalid CSRF token');
    }

    try {
        $receiver_id = (int)$_POST['receiver_id'];
        $message = trim($_POST['message'] ?? '');

        if (empty($message)) send_response(null, 400, 'Message cannot be empty');

        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);

        send_response(null, 200, 'Message sent');
    } catch (PDOException $e) {
        send_response(null, 500, 'Send failed');
    }
}
