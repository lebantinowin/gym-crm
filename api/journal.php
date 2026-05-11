<?php
// api/journal.php - Enhanced Journal & Metrics
require_once __DIR__ . '/bootstrap.php';

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET - Fetch Entries
if ($method === 'GET') {
    try {
        $filter = $_GET['filter'] ?? 'active';
        $limit = (int)($_GET['limit'] ?? 30);
        
        $query = "SELECT * FROM journal WHERE user_id = ?";
        if ($filter === 'starred') $query .= " AND starred = 1 AND archived = 0";
        elseif ($filter === 'archived') $query .= " AND archived = 1";
        else $query .= " AND archived = 0";
        
        $query .= " ORDER BY date DESC LIMIT ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $limit]);
        $history = $stmt->fetchAll();

        send_response(['history' => $history]);
    } catch (PDOException $e) {
        send_response(null, 500, 'Database error: ' . $e->getMessage());
    }
}

// 2. POST - Save/Update Entry
if ($method === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        send_response(null, 403, 'Invalid CSRF token');
    }

    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        try {
            $date = $_POST['date'] ?? date('Y-m-d');
            $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
            $chest = !empty($_POST['chest']) ? (float)$_POST['chest'] : null;
            $waist = !empty($_POST['waist']) ? (float)$_POST['waist'] : null;
            $hips = !empty($_POST['hips']) ? (float)$_POST['hips'] : null;
            $mood = $_POST['mood'] ?? '😊';
            $notes = htmlspecialchars(trim($_POST['notes'] ?? ''));

            $stmt = $pdo->prepare("
                INSERT INTO journal (user_id, date, weight, chest, waist, hips, mood, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    weight = VALUES(weight), 
                    chest = VALUES(chest), 
                    waist = VALUES(waist), 
                    hips = VALUES(hips), 
                    mood = VALUES(mood),
                    notes = VALUES(notes)
            ");
            $stmt->execute([$user_id, $date, $weight, $chest, $waist, $hips, $mood, $notes]);

            send_response(null, 200, 'Journal entry saved!');
        } catch (PDOException $e) {
            send_response(null, 500, 'Save failed: ' . $e->getMessage());
        }
    }

    if (in_array($action, ['star', 'unstar', 'archive', 'unarchive', 'delete'])) {
        try {
            $id = (int)$_POST['id'];
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM journal WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
            } else {
                $field = in_array($action, ['star', 'unstar']) ? 'starred' : 'archived';
                $value = in_array($action, ['star', 'archive']) ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE journal SET $field = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$value, $id, $user_id]);
            }
            send_response(null, 200, 'Action completed');
        } catch (PDOException $e) {
            send_response(null, 500, 'Action failed: ' . $e->getMessage());
        }
    }
}
