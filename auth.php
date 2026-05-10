<?php
// auth.php — Enhanced & Fixed (all helpers defined early)
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/upload_helper.php';

define('SESSION_TIMEOUT', 1800); // 30 minutes idle timeout

// ── Session timeout check ──────────────────────────────────────────────────
if (isset($_SESSION['user_id'])) {
    $last_active = $_SESSION['last_active'] ?? time();
    if ((time() - $last_active) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
    $_SESSION['last_active'] = time();
}

// ── CSRF Helpers ───────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Create upload dir & default.png
if (!file_exists(UPLOAD_DIR_PATH)) {
    mkdir(UPLOAD_DIR_PATH, 0777, true);
}
if (!file_exists(UPLOAD_DIR_PATH . 'default.png')) {
    // 1x1 transparent PNG (base64)
    file_put_contents(UPLOAD_DIR_PATH . 'default.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
}
// ✅ Password Complexity Check
function validate_password($password) {
    if (strlen($password) < 8) return "Password must be at least 8 characters long.";
    if (!preg_match('/[A-Z]/', $password)) return "Password must contain at least one uppercase letter.";
    if (!preg_match('/[a-z]/', $password)) return "Password must contain at least one lowercase letter.";
    if (!preg_match('/[0-9]/', $password)) return "Password must contain at least one number.";
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return "Password must contain at least one special character.";
    return true;
}

// ✅ 1. ALL HELPER FUNCTIONS — defined FIRST
function login($email, $password) {
    global $pdo;

    // Brute force protection
    $now = time();
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $last_attempt = $_SESSION['login_last_attempt'] ?? 0;

    if ($attempts >= 5 && ($now - $last_attempt) < 30) {
        $remaining = 30 - ($now - $last_attempt);
        $_SESSION['error'] = "Too many failed attempts. Please wait $remaining seconds.";
        return false;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_profile_picture'] = $user['profile_picture'] ?? 'default.png';
        $_SESSION['user_coach_style'] = $user['coach_style'] ?? 'balanced';
        $_SESSION['last_active'] = time();
        
        // Clear login attempts on success
        unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
        return true;
    }

    // Failure: Increment attempts
    $_SESSION['login_attempts'] = $attempts + 1;
    $_SESSION['login_last_attempt'] = $now;
    
    // Artificial delay
    sleep(1);
    return false;
}

function register($name, $email, $password, $goal, $profile_picture = null, $weight = null, $height = null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) return "Email already registered.";
    
    $val = validate_password($password);
    if ($val !== true) return $val;
    
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, fitness_goal, profile_picture, weight, height, coach_style) VALUES (?, ?, ?, ?, ?, ?, ?, 'balanced')");
    return $stmt->execute([$name, $email, $hashed, $goal, $profile_picture, $weight, $height]);
}

// ✅ REQUIRED BY dashboard.php — defined early!
function get_user_data($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function calculate_attendance_rate($user_id) {
    global $pdo;
    $user_id = (int) $user_id;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_days = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND attended = 1");
    $stmt->execute([$user_id]);
    $attended_days = (int) $stmt->fetchColumn();

    return $total_days > 0 ? round(($attended_days / $total_days) * 100, 1) : 0;
}

function get_recent_workouts($user_id, $limit = 5) {
    global $pdo;
    $limit = (int) $limit;
    $sql = "SELECT * FROM workouts WHERE user_id = ? ORDER BY date DESC LIMIT ?";
    $stmt = $pdo->prepare($sql);
    // ✅ Use PDO::PARAM_INT
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_mood_data($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT mood, COUNT(*) as count FROM mood_checkins WHERE user_id = ? GROUP BY mood");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $mood_order = ['😠', '😐', '😊', '😁'];
    $ordered_results = [];
    $mood_counts = [];
    foreach ($results as $row) {
        $mood_counts[$row['mood']] = $row['count'];
    }
    
    foreach ($mood_order as $mood) {
        $ordered_results[] = [
            'mood' => $mood,
            'count' => $mood_counts[$mood] ?? 0
        ];
    }
    
    return $ordered_results;
}

// Session/Flow helpers
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

function require_admin() {
    if (!is_logged_in() || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}


// ✅ Get notifications (paginated)
// ✅ Get unread notifications count
function get_unread_notifications_count($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

// ✅ Get notifications (paginated)
function get_notifications($user_id, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT id, type, title, message, icon, is_read, created_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ Mark notification as read
function mark_notification_read($user_id, $notification_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    return $stmt->execute([$notification_id, $user_id]);
}

// ✅ Mark all as read
function mark_all_notifications_read($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    return $stmt->execute([$user_id]);
}

// ✅ Add notification (call this from dashboard/attendance/etc.)
function add_notification($user_id, $type, $title, $message, $icon = 'fas fa-bell') {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, icon) 
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $type, $title, $message, $icon]);
}



// ✅ Log system activity (Audit Log)
function log_activity($user_id, $type, $details = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, activity_type, details) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $type, $details]);
}

// ✅ 2. POST HANDLERS — go LAST (after all functions defined)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🛡️ Global CSRF Check
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid session token. Please try again.";
        return;
    }

    // Login
    if (isset($_POST['login'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        if (login($email, $password)) {
            $redirect = $_SESSION['user_role'] === 'admin' ? 'admin/index.php' : 'dashboard.php';
            header('Location: ' . $redirect);
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password";
        }
    }
    // Register
    elseif (isset($_POST['register'])) {
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $goal = !empty($_POST['goal']) ? htmlspecialchars(trim($_POST['goal'])) : null;
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
        
        $profile_picture = 'default.png';
        if (!empty($_FILES['profile_picture']['name'])) {
            $profile_picture = upload_profile_picture($_FILES['profile_picture']);
        }
        
        if (strlen($password) < 6) {
            $_SESSION['error'] = "Password must be at least 6 characters";
        } elseif (register($name, $email, $password, $goal, $profile_picture, $weight, $height)) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['error'] = "Email already in use.";
        }
    }
}
?>