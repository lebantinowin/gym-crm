<?php
// api/dashboard_stats.php - Unified Dashboard Data
require_once __DIR__ . '/bootstrap.php';

$user_id = $_SESSION['user_id'];

try {
    // 1. Total Workouts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_workouts = $stmt->fetchColumn();

    // 2. Weekly Attendance
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND date >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND attended = 1");
    $stmt->execute([$user_id]);
    $weekly_attendance = $stmt->fetchColumn();

    // 3. Current Weight (Most Recent)
    $stmt = $pdo->prepare("SELECT weight FROM journal WHERE user_id = ? AND weight IS NOT NULL ORDER BY date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $current_weight = $stmt->fetchColumn() ?: 'N/A';

    // 4. Streak (Days in a row) - Simplified logic
    $stmt = $pdo->prepare("SELECT date FROM attendance WHERE user_id = ? AND attended = 1 ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $streak = 0;
    if (!empty($dates)) {
        $today = new DateTime();
        $checkDate = new DateTime($dates[0]);
        $diff = $today->diff($checkDate)->days;
        
        if ($diff <= 1) { // Current or yesterday
            $streak = 1;
            for ($i = 0; $i < count($dates) - 1; $i++) {
                $d1 = new DateTime($dates[$i]);
                $d2 = new DateTime($dates[$i+1]);
                if ($d1->diff($d2)->days === 1) {
                    $streak++;
                } else {
                    break;
                }
            }
        }
    }

    send_response([
        'total_workouts' => (int)$total_workouts,
        'weekly_attendance' => (int)$weekly_attendance,
        'current_weight' => $current_weight,
        'streak' => $streak
    ]);

} catch (PDOException $e) {
    send_response(null, 500, 'Database error: ' . $e->getMessage());
}
