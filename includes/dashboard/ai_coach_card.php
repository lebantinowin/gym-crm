<?php
// includes/dashboard/ai_coach_card.php
?>
<div class="gradient-border">
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b bg-primary flex justify-between items-center">
            <div class="flex items-center">
                <div class="bg-highlight p-2 rounded-full mr-3">
                    <i class="fas fa-robot text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Warzone AI Coach</h3>
            </div>
            <a href="chat.php" class="text-gray-400 hover:text-white text-sm">
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
        <div class="p-6">
            <div class="bg-gray-50 rounded-lg p-4 mb-4 italic text-gray-700 relative">
                <i class="fas fa-quote-left absolute -top-2 -left-2 text-gray-200 text-2xl"></i>
                "You've been consistent this week! But I noticed you skipped leg day. Don't make me come find you..."
            </div>
            
            <?php if (!empty($user_data['fitness_goal'])): ?>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Coach Confidence</span>
                        <span class="font-bold text-highlight"><?= $ai_confidence ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-highlight h-2 rounded-full" style="width: <?= $ai_confidence ?>%"></div>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-500 mb-4">Set a fitness goal to unlock personalized coaching insights.</p>
            <?php endif; ?>
            
            <a href="chat.php" class="block w-full text-center bg-primary text-white py-3 rounded-lg mt-6 font-bold hover:bg-opacity-90 transition">
                Talk to Coach
            </a>
        </div>
    </div>
</div>
