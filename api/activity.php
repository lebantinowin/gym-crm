<?php
// api/activity.php - Recent User Activity Feed
require_once __DIR__ . '/bootstrap.php';

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $limit = (int)($_GET['limit'] ?? 10);
        
        $stmt = $pdo->prepare("
            SELECT activity_type as type, details, created_at 
            FROM user_activity 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        $activities = $stmt->fetchAll();

        send_response(['activities' => $activities]);
    } catch (PDOException $e) {
        send_response(null, 500, 'Failed to fetch activity: ' . $e->getMessage());
    }
}

send_response(null, 405, 'Method not allowed');
