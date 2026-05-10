<?php
// forgot_password.php - Initiate Password Reset
require_once 'auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_request'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session.";
    } else {
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires]);
            
            // In a real app, send email here.
            // For this demo, we'll show the link.
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            $message = "If an account exists for $email, a reset link has been sent. <br><br><strong>[DEMO MODE]</strong> Reset Link: <a href='$reset_link' class='text-highlight underline'>$reset_link</a>";
        } else {
            // Don't reveal if email exists
            $message = "If an account exists for $email, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warzone Gym - Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', highlight: '#e94560' } } } }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-xl p-8 border-t-4 border-highlight">
        <div class="text-center mb-8">
            <i class="fas fa-key text-highlight text-4xl mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">Reset Password</h1>
            <p class="text-gray-600">Enter your email to get a reset link</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="mb-4 p-4 bg-blue-50 text-blue-800 rounded-lg text-sm leading-relaxed">
                <?= $message ?>
            </div>
            <div class="text-center">
                <a href="login.php" class="text-highlight font-bold hover:underline">Back to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-6">
                <?= csrf_field() ?>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="email" name="email" required placeholder="Email Address"
                           class="w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-highlight outline-none">
                </div>
                <button type="submit" name="reset_request" class="w-full bg-highlight text-white py-3 rounded-lg font-bold hover:opacity-90 transition shadow-lg">
                    Send Reset Link
                </button>
            </form>
            <div class="mt-6 text-center">
                <a href="login.php" class="text-gray-500 hover:text-highlight transition text-sm">Cancel and go back</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
