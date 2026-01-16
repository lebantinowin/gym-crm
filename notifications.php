<?php
// notifications.php — Notification API
require_once 'auth.php';
require_once 'config.php';

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'mark_read':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id && mark_notification_read($user_id, $id)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid notification']);
            }
            break;
            
        case 'mark_all_read':
            if (mark_all_notifications_read($user_id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
            break;
            
        case 'get_count':
            echo json_encode([
                'count' => get_unread_notifications_count($user_id)
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>