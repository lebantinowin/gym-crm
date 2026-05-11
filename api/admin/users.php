<?php
// api/admin/users.php - Admin User Management
require_once __DIR__ . '/../bootstrap.php';

// Ensure user is admin
if ($_SESSION['role'] !== 'admin') {
    send_response(null, 403, 'Unauthorized');
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, name, email, role, created_at, last_login, profile_picture FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        send_response(['users' => $users]);
    } catch (PDOException $e) {
        send_response(null, 500, 'Fetch failed: ' . $e->getMessage());
    }
}

if ($method === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        send_response(null, 403, 'Invalid CSRF token');
    }

    $action = $_POST['action'] ?? 'update';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($action === 'delete') {
        try {
            // Prevent self-deletion
            if ($user_id === (int)$_SESSION['user_id']) {
                send_response(null, 400, 'Cannot delete yourself');
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            send_response(null, 200, 'User deleted');
        } catch (PDOException $e) {
            send_response(null, 500, 'Delete failed');
        }
    }

    if ($action === 'update_role') {
        try {
            $new_role = $_POST['role'] === 'admin' ? 'admin' : 'user';
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            send_response(null, 200, 'Role updated');
        } catch (PDOException $e) {
            send_response(null, 500, 'Update failed');
        }
    }
}
