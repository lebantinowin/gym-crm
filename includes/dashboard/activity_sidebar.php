<?php
// includes/dashboard/activity_sidebar.php
?>
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-6 border-b">
        <h3 class="text-xl font-bold text-gray-800">Alerts & Insights</h3>
    </div>
    <div class="p-6">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-6 text-gray-400">
                <i class="fas fa-check-circle text-2xl mb-2 text-green-100"></i>
                <p class="text-sm">You're all caught up!</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($notifications as $notif): ?>
                    <div class="flex items-start p-3 bg-gray-50 rounded-lg border-l-4 border-<?= $notif['color'] ?>">
                        <div class="mr-3 mt-1">
                            <i class="<?= $notif['icon'] ?> text-<?= $notif['color'] ?>"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <h4 class="font-bold text-sm"><?= $notif['title'] ?></h4>
                                <span class="text-[10px] text-gray-400"><?= $notif['time'] ?></span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1"><?= $notif['message'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="bg-primary text-white p-6 rounded-xl shadow overflow-hidden relative group">
    <div class="absolute -right-4 -bottom-4 opacity-10 transform group-hover:scale-110 transition-transform duration-500">
        <i class="fas fa-bolt text-9xl"></i>
    </div>
    <h4 class="text-lg font-bold mb-2">Upgrade to Pro</h4>
    <p class="text-xs text-gray-300 mb-4">Unlock advanced AI analysis, custom meal plans, and video form correction.</p>
    <button class="w-full bg-highlight py-2 rounded-lg text-sm font-bold hover:bg-opacity-90 transition">
        View Pricing
    </button>
</div>
