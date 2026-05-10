<?php
// reset_password.php - Complete Password Reset
require_once 'auth.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    die("Invalid token request.");
}

// Check token validity
$stmt = $pdo->prepare("
    SELECT pr.*, u.email 
    FROM password_resets pr
    JOIN users u ON pr.user_id = u.id
    WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
");
$stmt->execute([$token]);
$reset_request = $stmt->fetch();

if (!$reset_request) {
    $error = "This reset link is invalid or has expired.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset_request) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session.";
    } else {
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $val = validate_password($password);
            if ($val !== true) {
                $error = $val;
            } else {
                // Update password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $reset_request['user_id']]);
                
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $stmt->execute([$reset_request['id']]);
                
                log_activity($reset_request['user_id'], 'password_reset', "Password reset successfully via token.");
                $success = "Your password has been reset! You can now login.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warzone Gym - New Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', highlight: '#e94560' } } } }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-xl p-8 border-t-4 border-highlight">
        <div class="text-center mb-8">
            <i class="fas fa-shield-alt text-highlight text-4xl mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">New Password</h1>
            <p class="text-gray-600">Enter your new strong password</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm"><?= $error ?></div>
            <?php if (!$reset_request): ?>
                <div class="text-center mt-4">
                    <a href="forgot_password.php" class="text-highlight font-bold hover:underline">Request new link</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg text-sm text-center font-bold">
                <?= $success ?>
            </div>
            <div class="text-center">
                <a href="login.php" class="bg-highlight text-white px-8 py-3 rounded-lg font-bold shadow-lg hover:opacity-90 transition block">Login Now</a>
            </div>
        <?php elseif ($reset_request): ?>
            <form method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="password" name="password" id="passwordInput" required placeholder="New Password"
                           class="w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-highlight outline-none">
                </div>
                <!-- Strength Meter -->
                <div class="mt-1 space-y-1">
                    <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div id="strengthBar" class="h-full w-0 bg-red-500 transition-all duration-300"></div>
                    </div>
                    <p id="strengthText" class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">Strength: None</p>
                </div>

                <div class="relative">
                    <i class="fas fa-check-circle absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="password" name="confirm_password" required placeholder="Confirm New Password"
                           class="w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-highlight outline-none">
                </div>
                <button type="submit" class="w-full bg-highlight text-white py-3 rounded-lg font-bold hover:opacity-90 transition shadow-lg">
                    Reset Password
                </button>
            </form>
            <script>
                const passInput = document.getElementById('passwordInput');
                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');

                passInput.addEventListener('input', () => {
                    const val = passInput.value;
                    let score = 0;
                    if (!val) score = 0;
                    else {
                        if (val.length >= 8) score += 20;
                        if (/[A-Z]/.test(val)) score += 20;
                        if (/[a-z]/.test(val)) score += 20;
                        if (/[0-9]/.test(val)) score += 20;
                        if (/[^A-Za-z0-9]/.test(val)) score += 20;
                    }
                    strengthBar.style.width = score + '%';
                    if (score <= 20) { strengthBar.className = 'h-full bg-red-500'; strengthText.textContent = 'Weak'; }
                    else if (score <= 60) { strengthBar.className = 'h-full bg-yellow-500'; strengthText.textContent = 'Moderate'; }
                    else if (score < 100) { strengthBar.className = 'h-full bg-blue-500'; strengthText.textContent = 'Good'; }
                    else { strengthBar.className = 'h-full bg-green-500'; strengthText.textContent = 'Warzone Ready'; }
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
