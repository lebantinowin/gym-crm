<?php
// api/workouts.php - Workout Operations
require_once __DIR__ . '/bootstrap.php';

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET - Fetch Workouts (Paginated)
if ($method === 'GET') {
    try {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        // Total count for pagination metadata
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total = (int)$stmt->fetchColumn();

        // Data
        $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT ? OFFSET ?");
        $stmt->execute([$user_id, $limit, $offset]);
        $workouts = $stmt->fetchAll();

        send_response([
            'workouts' => $workouts,
            'pagination' => [
                'current_page' => $page,
                'limit' => $limit,
                'total_items' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    } catch (PDOException $e) {
        send_response(null, 500, 'Failed to fetch workouts: ' . $e->getMessage());
    }
}

// 2. POST - Log or Delete Workout
if ($method === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        send_response(null, 403, 'Invalid CSRF token');
    }

    $action = $_POST['action'] ?? 'log';

    if ($action === 'log') {
        try {
            $type = htmlspecialchars(trim($_POST['workout_type'] ?? 'Other'));
            $duration = (int)($_POST['duration'] ?? 0);
            $calories = (int)($_POST['calories'] ?? 0);
            $notes = htmlspecialchars(trim($_POST['workout_notes'] ?? ''));
            $mood = $_POST['workout_mood'] ?? '😊';

            // Validate mood
            $allowed_moods = ['😠', '😐', '😊', '😁'];
            if (!in_array($mood, $allowed_moods)) $mood = '😊';

            // Insert workout
            $stmt = $pdo->prepare("INSERT INTO workouts (user_id, date, type, duration, calories_burned, notes, mood) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $duration, $calories, $notes, $mood]);
            
            // Log activity
            log_activity($user_id, 'Workout Logged', "Logged a $type session ($duration mins)");
            
            // Mark attendance
            $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, attended) VALUES (?, CURDATE(), 1) ON DUPLICATE KEY UPDATE attended = 1");
            $stmt->execute([$user_id]);

            send_response(['workout_id' => $pdo->lastInsertId()], 200, 'Workout logged successfully!');
        } catch (PDOException $e) {
            send_response(null, 500, 'Failed to log workout: ' . $e->getMessage());
        }
    }

    if ($action === 'delete') {
        try {
            $workout_id = (int)($_POST['workout_id'] ?? 0);
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM workouts WHERE id = ? AND user_id = ?");
            $stmt->execute([$workout_id, $user_id]);
            if (!$stmt->fetch()) {
                send_response(null, 404, 'Workout not found or unauthorized');
            }

            $stmt = $pdo->prepare("DELETE FROM workouts WHERE id = ?");
            $stmt->execute([$workout_id]);
            
            log_activity($user_id, 'Workout Deleted', "Deleted workout ID: $workout_id");
            send_response(null, 200, 'Workout deleted successfully!');
        } catch (PDOException $e) {
            send_response(null, 500, 'Failed to delete workout: ' . $e->getMessage());
        }
    }
}

send_response(null, 405, 'Method not allowed');
