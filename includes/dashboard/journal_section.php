<?php
// includes/dashboard/journal_section.php
?>
<div class="bg-white rounded-xl shadow overflow-hidden mt-8">
    <div class="p-6 border-b flex justify-between items-center">
        <div>
            <h3 class="text-xl font-bold text-gray-800">Journey Journal</h3>
            <p class="text-gray-600">Reflect on your progress and mood</p>
        </div>
        <a href="journal.php" class="text-highlight hover:underline font-medium text-sm">
            Full Journal
        </a>
    </div>
    <div class="p-6">
        <?php if (empty($mood_history)): ?>
            <div class="text-center py-12 text-gray-500">
                <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-book text-2xl text-gray-400"></i>
                </div>
                <p class="font-medium">Your journal is empty</p>
                <button onclick="document.getElementById('moodModal').classList.remove('hidden')" 
                        class="text-highlight text-sm hover:underline mt-2">
                    Write your first entry →
                </button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                // Only show latest 3 entries
                $latest_moods = array_slice($mood_history, 0, 3);
                foreach ($latest_moods as $entry): 
                ?>
                <div class="border rounded-xl p-4 hover:shadow-md transition-shadow relative group">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs text-gray-400"><?= date('M j, Y', strtotime($entry['date'])) ?></span>
                        <span class="text-2xl"><?= $entry['mood'] ?></span>
                    </div>
                    <p class="text-gray-600 text-sm line-clamp-3 mb-4 italic">
                        "<?= htmlspecialchars($entry['notes']) ?>"
                    </p>
                    <div class="flex justify-between items-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="openEditMood(<?= htmlspecialchars(json_encode($entry)) ?>)" 
                                class="text-gray-400 hover:text-highlight text-xs">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                        <button onclick="confirmAction('delete', <?= $entry['id'] ?>)" 
                                class="text-gray-300 hover:text-red-500 text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
