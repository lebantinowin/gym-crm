<?php
// api/admin/ai.php - AI Persona & Response Management
require_once __DIR__ . '/../bootstrap.php';

if ($_SESSION['role'] !== 'admin') {
    send_response(null, 403, 'Unauthorized');
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Fetch Personas
        $stmt = $pdo->query("SELECT * FROM ai_personas ORDER BY status ASC, name ASC");
        $personas = $stmt->fetchAll();

        // Fetch Responses (Categories)
        $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM ai_responses GROUP BY category");
        $stats = $stmt->fetchAll();

        send_response([
            'personas' => $personas,
            'stats' => $stats
        ]);
    } catch (PDOException $e) {
        send_response(null, 500, 'Fetch failed: ' . $e->getMessage());
    }
}

if ($method === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        send_response(null, 403, 'Invalid CSRF token');
    }

    $action = $_POST['action'] ?? 'update_persona';

    if ($action === 'toggle_persona') {
        try {
            $id = (int)$_POST['id'];
            $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
            
            // Deactivate others if this one is being activated (optional rule)
            if ($status === 'active') {
                $pdo->exec("UPDATE ai_personas SET status = 'inactive'");
            }
            
            $stmt = $pdo->prepare("UPDATE ai_personas SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            send_response(null, 200, "Persona $status");
        } catch (PDOException $e) {
            send_response(null, 500, 'Toggle failed');
        }
    }

    if ($action === 'update_persona') {
        try {
            $id = (int)$_POST['id'];
            $instructions = trim($_POST['instructions'] ?? '');
            
            $stmt = $pdo->prepare("UPDATE ai_personas SET instructions = ? WHERE id = ?");
            $stmt->execute([$instructions, $id]);
            
            send_response(null, 200, 'Instructions updated');
        } catch (PDOException $e) {
            send_response(null, 500, 'Update failed');
        }
    }
}
