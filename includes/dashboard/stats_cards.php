<?php
// includes/dashboard/stats_cards.php
?>
<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Attendance Card -->
    <div class="dashboard-card bg-white p-6 rounded-xl shadow">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-gray-500 text-sm">Attendance Rate</p>
                <p class="text-2xl font-bold"><?= $attendance_rate ?>%</p>
                <p class="text-xs text-success">↑ 12% from last month</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-calendar-check text-blue-500 text-xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Workouts Card -->
    <div class="dashboard-card bg-white p-6 rounded-xl shadow">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-gray-500 text-sm">Workouts This Week</p>
                <p class="text-2xl font-bold"><?= count(array_filter($workouts, fn($w) => strtotime($w['date']) >= strtotime('monday this week'))) ?></p>
                <p class="text-xs text-green-500">↑ <?= count($workouts) ?> total</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="fas fa-dumbbell text-purple-500 text-xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Goal Progress Card -->
    <?php if (!empty($user_data['fitness_goal'])): ?>
        <div id="goalProgressCard" class="dashboard-card bg-white p-6 rounded-xl shadow cursor-pointer hover:ring-2 hover:ring-highlight">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Goal Progress</p>
                    <p class="text-2xl font-bold"><?= $goal_progress ?>%</p>
                    <p class="text-xs text-highlight">"<?= htmlspecialchars(substr($user_data['fitness_goal'], 0, 20)) . (strlen($user_data['fitness_goal']) > 20 ? '…' : '') ?>"</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-chart-line text-green-500 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Consistency Score Card -->
        <div class="dashboard-card bg-white p-6 rounded-xl shadow">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Consistency Score</p>
                    <p class="text-2xl font-bold"><?= $ai_confidence ?>%</p>
                    <p class="text-xs <?= $ai_confidence > 85 ? 'text-success' : 'text-yellow-500' ?>">
                        <?= $ai_confidence > 85 ? '✅ On track' : '⚠️ Needs attention' ?>
                    </p>
                </div>
                <div class="bg-highlight p-3 rounded-full">
                    <i class="fas fa-robot text-white text-xl"></i>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Get Started CTA -->
        <div class="dashboard-card bg-white p-6 rounded-xl shadow md:col-span-2 gradient-border">
            <div class="flex justify-between items-center h-full">
                <div>
                    <p class="text-gray-500 text-sm">Set Your Fitness Goal</p>
                    <p class="text-2xl font-bold">Get Started!</p>
                    <p class="text-xs text-highlight mt-2">✨ 5-minute setup: goal, metrics, coach style</p>
                </div>
                <a href="profile.php#goal" class="btn-primary px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-bullseye mr-2"></i> Set Goal
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
