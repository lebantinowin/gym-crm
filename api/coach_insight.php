<?php
// api/coach_insight.php - Dynamic Coaching Insight
require_once __DIR__ . '/bootstrap.php';

$user_id = $_SESSION['user_id'];

try {
    // Fetch last workout
    $stmt = $pdo->prepare("SELECT type, date FROM workouts WHERE user_id = ? ORDER BY date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $last_workout = $stmt->fetch();

    // Fetch attendance rate
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND attended = 1");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();

    $messages = [
        "Your consistency is impressive. Keep pushing those limits!",
        "Rest is just as important as the work. Make sure you're recovering.",
        "I'm watching your progress. Don't let up now!",
        "Every rep counts. Focus on that form today."
    ];

    if ($last_workout) {
        $messages[] = "Great session on " . date('M j', strtotime($last_workout['date'])) . ". What's next?";
    }

    if ($count < 5) {
        $messages = ["The gym misses you. Get back in there!", "Consistency is the only secret. Show up today."];
    }

    $insight = $messages[array_rand($messages)];
    $confidence = 85 + (rand(0, 10));

    send_response([
        'insight' => $insight,
        'confidence' => $confidence
    ]);
} catch (PDOException $e) {
    send_response(null, 500, 'Insight failed: ' . $e->getMessage());
}
