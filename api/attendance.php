<?php
// api/attendance.php - Attendance Operations
require_once __DIR__ . '/bootstrap.php';

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET - Fetch Attendance (for a specific month/year)
if ($method === 'GET') {
    try {
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        // Fetch all attendance for the given month
        $stmt = $pdo->prepare("
            SELECT date, attended 
            FROM attendance 
            WHERE user_id = ? 
            AND MONTH(date) = ? 
            AND YEAR(date) = ?
        ");
        $stmt->execute([$user_id, $month, $year]);
        $history = $stmt->fetchAll();

        send_response([
            'month' => $month,
            'year' => $year,
            'history' => $history
        ]);
    } catch (PDOException $e) {
        send_response(null, 500, 'Failed to fetch attendance: ' . $e->getMessage());
    }
}

// 2. POST - Mark Attendance
if ($method === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        send_response(null, 403, 'Invalid CSRF token');
    }

    try {
        $date = $_POST['date'] ?? date('Y-m-d');
        $attended = (int)($_POST['attended'] ?? 1);

        $stmt = $pdo->prepare("
            INSERT INTO attendance (user_id, date, attended) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE attended = VALUES(attended)
        ");
        $stmt->execute([$user_id, $date, $attended]);

        $action_text = $attended ? 'Check-in' : 'Check-out';
        log_activity($user_id, "Attendance Updated", "$action_text for date: $date");

        send_response(null, 200, 'Attendance updated successfully!');
    } catch (PDOException $e) {
        send_response(null, 500, 'Failed to update attendance: ' . $e->getMessage());
    }
}

send_response(null, 405, 'Method not allowed');
