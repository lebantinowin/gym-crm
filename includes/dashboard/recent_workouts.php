<?php
// includes/dashboard/recent_workouts.php
?>
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center">
        <div>
            <h3 class="text-xl font-bold text-gray-800">Workout History</h3>
            <p class="text-gray-600">Your recent training sessions</p>
        </div>
        <a href="workouts.php" class="text-highlight hover:underline font-medium text-sm">
            View All 
        </a>
    </div>
    <div class="p-6">
        <?php if (empty($workouts)): ?>
            <div class="text-center py-12 text-gray-500">
                <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-dumbbell text-2xl text-gray-400"></i>
                </div>
                <p class="font-medium">No workouts logged yet</p>
                <button onclick="document.getElementById('workoutModal').classList.remove('hidden')" 
                        class="text-highlight text-sm hover:underline mt-2">
                    Start your first session →
                </button>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php 
                // Only show 3 most recent workouts
                $recent_workouts = array_slice($workouts, 0, 3);
                foreach ($recent_workouts as $workout): 
                ?>
                <div class="flex items-center justify-between p-4 border rounded-xl hover:bg-gray-50 transition-colors">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-running text-green-500"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800"><?= htmlspecialchars($workout['type']) ?></h4>
                            <p class="text-gray-500 text-xs">
                                <?= date('M j', strtotime($workout['date'])) ?> • <?= $workout['duration'] ?> mins
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800"><?= $workout['calories_burned'] ?> <span class="text-xs font-normal text-gray-500">kcal</span></p>
                        <span class="text-lg"><?= $workout['mood'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
