<?php
// api/admin/reports.php - Business Intelligence API
require_once __DIR__ . '/../bootstrap.php';

if ($_SESSION['role'] !== 'admin') {
    send_response(null, 403, 'Unauthorized');
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // 1. Revenue Metrics
        $stmt = $pdo->query("SELECT SUM(amount) as total, COUNT(*) as count FROM payments");
        $revenue = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT SUM(amount) as monthly FROM payments WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $monthly_revenue = $stmt->fetchColumn() ?? 0;

        // 2. Member Growth
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
        $total_members = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $new_members = $stmt->fetchColumn();

        // 3. Activity Trends (Last 7 Days)
        $stmt = $pdo->query("
            SELECT date, COUNT(*) as count 
            FROM workouts 
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
            GROUP BY date 
            ORDER BY date ASC
        ");
        $workout_trends = $stmt->fetchAll();

        // 4. Popular Workout Types
        $stmt = $pdo->query("
            SELECT type, COUNT(*) as count 
            FROM workouts 
            GROUP BY type 
            ORDER BY count DESC 
            LIMIT 5
        ");
        $top_workouts = $stmt->fetchAll();

        send_response([
            'revenue' => [
                'total' => (float)$revenue['total'],
                'monthly' => (float)$monthly_revenue,
                'transaction_count' => (int)$revenue['count']
            ],
            'members' => [
                'total' => (int)$total_members,
                'new_30d' => (int)$new_members,
                'active_rate' => $total_members > 0 ? round(($new_members / $total_members) * 100, 1) : 0
            ],
            'trends' => [
                'workouts' => $workout_trends,
                'top_types' => $top_workouts
            ]
        ]);
    } catch (PDOException $e) {
        send_response(null, 500, 'Report generation failed: ' . $e->getMessage());
    }
}
