<?php
// admin/payments.php - Payment Management
require_once '../auth.php';
require_admin();

// Handle new payment entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Invalid token");
    }
    
    $user_id = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $method = htmlspecialchars($_POST['method']);
    
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, method) VALUES (?, ?, ?)");
    if ($stmt->execute([$user_id, $amount, $method])) {
        log_activity($_SESSION['user_id'], 'Payment Added', "Added payment of $amount for user ID: $user_id");
        header("Location: payments.php?success=1");
        exit();
    }
}

// Get all users for the dropdown
$stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
$all_users = $stmt->fetchAll();

// Get recent payments
$stmt = $pdo->query("
    SELECT p.*, u.name as user_name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.payment_date DESC 
    LIMIT 50
");
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warzone Admin - Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', highlight: '#e94560' } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 md:ml-64 w-full flex flex-col">
        <main class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Membership Payments</h1>
                <button onclick="document.getElementById('addPaymentModal').classList.remove('hidden')" 
                        class="bg-highlight text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:opacity-90 transition">
                    <i class="fas fa-plus mr-2"></i> Record Payment
                </button>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-xl font-bold">
                    Payment recorded successfully!
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-600">Member</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-600">Amount</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-600">Method</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-600">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($payments as $p): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($p['user_name']) ?></td>
                                <td class="px-6 py-4 text-green-600 font-bold">$<?= number_format($p['amount'], 2) ?></td>
                                <td class="px-6 py-4 text-gray-500 uppercase text-xs font-bold"><?= htmlspecialchars($p['method']) ?></td>
                                <td class="px-6 py-4 text-gray-400 text-sm"><?= date('M j, Y g:i A', strtotime($p['payment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Payment Modal -->
    <div id="addPaymentModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold mb-6">Record New Payment</h2>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Select Member</label>
                        <select name="user_id" required class="w-full border rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-highlight">
                            <?php foreach ($all_users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Amount ($)</label>
                        <input type="number" step="0.01" name="amount" required class="w-full border rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-highlight">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Payment Method</label>
                        <select name="method" class="w-full border rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-highlight">
                            <option value="credit_card">Credit Card</option>
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="document.getElementById('addPaymentModal').classList.add('hidden')" 
                            class="flex-1 py-3 font-bold text-gray-500 hover:bg-gray-100 rounded-xl transition">Cancel</button>
                    <button type="submit" name="add_payment" 
                            class="flex-1 bg-highlight text-white py-3 rounded-xl font-bold shadow-lg hover:opacity-90 transition">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
