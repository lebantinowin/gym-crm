<?php
// api/nutrition.php - Nutrition & Meal Logs
require_once __DIR__ . '/bootstrap.php';

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT * FROM nutrition_logs 
            WHERE user_id = ? AND date = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$user_id, $date]);
        $meals = $stmt->fetchAll();

        // Calculate totals
        $totals = [
            'calories' => 0,
            'protein' => 0,
            'carbs' => 0,
            'fat' => 0
        ];
        foreach ($meals as $m) {
            $totals['calories'] += $m['calories'];
            $totals['protein'] += $m['protein'];
            $totals['carbs'] += $m['carbs'];
            $totals['fat'] += $m['fat'];
        }

        send_response([
            'date' => $date,
            'meals' => $meals,
            'totals' => $totals
        ]);
    } catch (PDOException $e) {
        send_response(null, 500, 'Fetch failed: ' . $e->getMessage());
    }
}

if ($method === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        send_response(null, 403, 'Invalid CSRF token');
    }

    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        try {
            $date = $_POST['date'] ?? date('Y-m-d');
            $meal_type = $_POST['meal_type'] ?? 'snack';
            $food_item = htmlspecialchars($_POST['food_item'] ?? '');
            $calories = (int)($_POST['calories'] ?? 0);
            $protein = (float)($_POST['protein'] ?? 0);
            $carbs = (float)($_POST['carbs'] ?? 0);
            $fat = (float)($_POST['fat'] ?? 0);

            $stmt = $pdo->prepare("
                INSERT INTO nutrition_logs (user_id, date, meal_type, food_item, calories, protein, carbs, fat) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $date, $meal_type, $food_item, $calories, $protein, $carbs, $fat]);

            log_activity($user_id, "Nutrition Logged", "Logged $food_item ($calories kcal)");

            send_response(null, 200, 'Meal logged successfully!');
        } catch (PDOException $e) {
            send_response(null, 500, 'Save failed: ' . $e->getMessage());
        }
    }

    if ($action === 'delete') {
        try {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM nutrition_logs WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            send_response(null, 200, 'Meal removed');
        } catch (PDOException $e) {
            send_response(null, 500, 'Delete failed: ' . $e->getMessage());
        }
    }
}
