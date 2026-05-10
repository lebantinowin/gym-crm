<?php
// api/bootstrap.php - Core API Handler
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Standardized JSON response helper
function send_response($data = null, $status = 200, $message = '') {
    http_response_code($status);
    echo json_encode([
        'status' => $status === 200 ? 'success' : 'error',
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit();
}

// Authentication Check Middleware
if (!is_logged_in()) {
    send_response(null, 401, 'Unauthorized access. Please log in.');
}

// Admin Only Check
function api_require_admin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        send_response(null, 403, 'Forbidden: Admin privileges required.');
    }
}
