<?php
// includes/dashboard/modals.php
?>

<!-- Log Workout Modal -->
<div id="workoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-dumbbell mr-3 text-highlight"></i> Log Workout
            </h3>
            <button id="closeWorkoutModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="workoutForm" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="log_workout" value="1">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Workout Type</label>
                    <select name="type" required class="w-full border rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-highlight outline-none bg-gray-50">
                        <option value="Strength">Strength</option>
                        <option value="Cardio">Cardio</option>
                        <option value="Yoga">Yoga</option>
                        <option value="HIIT">HIIT</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Duration (min)</label>
                    <input type="number" name="duration" required min="1" placeholder="30" 
                           class="w-full border rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-highlight outline-none bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Calories Burned (est.)</label>
                <input type="number" name="calories" placeholder="300" 
                       class="w-full border rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-highlight outline-none bg-gray-50">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">How do you feel?</label>
                <div class="flex justify-between gap-2">
                    <?php 
                    $moods = ['😠', '😐', '😊', '😁'];
                    $labels = ['Tired', 'Okay', 'Good', 'Awesome'];
                    foreach ($moods as $index => $mood): ?>
                        <div class="flex flex-col items-center">
                            <input type="radio" name="mood" value="<?= $mood ?>" id="mood_<?= $index ?>" 
                                   class="peer hidden" <?= $mood === '😊' ? 'checked' : '' ?>>
                            <label for="mood_<?= $index ?>" 
                                   class="mood-btn w-12 h-12 rounded-full flex items-center justify-center bg-gray-100 
                                          peer-checked:bg-highlight peer-checked:text-white 
                                          peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-highlight
                                          cursor-pointer transition-all duration-200">
                                <span class="text-xl"><?= $mood ?></span>
                            </label>
                            <span class="mt-1 text-[10px] text-gray-500 peer-checked:text-highlight peer-checked:font-bold"><?= $labels[$index] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="w-full btn-primary py-3 rounded-lg font-bold shadow-lg shadow-highlight/20 mt-2">
                <i class="fas fa-save mr-2"></i> Log Session
            </button>
        </form>
    </div>
</div>

<!-- Quick Journal Modal -->
<div id="journalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-book-open mr-3 text-highlight"></i> Journal Entry
            </h3>
            <button id="closeJournalModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="journal_action" id="journalModalAction" value="add">
            <input type="hidden" name="journal_id" id="journalModalId" value="">
            <input type="hidden" name="journal_date" id="journalModalDate" value="<?= date('Y-m-d') ?>">
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-3">Overall Mood</label>
                <div class="grid grid-cols-4 gap-3">
                    <?php foreach (['😠', '😐', '😊', '😁'] as $idx => $mood): ?>
                        <label class="flex flex-col items-center cursor-pointer">
                            <input type="radio" name="journal_mood" value="<?= $mood ?>" id="j_mood_<?= $idx ?>"
                                   class="peer hidden" <?= $mood === '😊' ? 'checked' : '' ?>>
                            <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center border-2 border-transparent peer-checked:border-highlight peer-checked:bg-white transition-all">
                                <span class="text-2xl"><?= $mood ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Thoughts & Progress</label>
                <textarea name="journal_notes" id="journalModalNotes" rows="4" 
                          class="w-full border rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-highlight outline-none bg-gray-50"
                          placeholder="What's on your mind?"></textarea>
            </div>
            
            <button type="submit" class="w-full btn-primary py-3 rounded-lg font-bold shadow-lg shadow-highlight/20">
                <i class="fas fa-save mr-2"></i> Save Entry
            </button>
        </form>
    </div>
</div>

<!-- Goal Progress Breakdown Modal -->
<div id="goalProgressModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Progress Breakdown</h3>
            <button id="closeGoalModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Base Points</span>
                <span class="font-bold text-gray-800">50%</span>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                    <span class="text-gray-600 block">Attendance</span>
                    <span class="text-[10px] text-gray-400"><?= $attendance_rate ?>% rate</span>
                </div>
                <span class="font-bold text-green-500">+<?= round($attendance_rate * 0.3) ?>%</span>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                    <span class="text-gray-600 block">Workouts Logged</span>
                    <span class="text-[10px] text-gray-400"><?= count($workouts) ?> sessions</span>
                </div>
                <span class="font-bold text-green-500">+<?= count($workouts) * 5 ?>%</span>
            </div>

            <div class="pt-4 border-t flex justify-between items-center">
                <span class="text-lg font-bold text-gray-800">Total Progress</span>
                <span class="text-2xl font-black text-highlight"><?= $goal_progress ?>%</span>
            </div>
        </div>

        <p class="text-[10px] text-gray-400 mt-6 text-center italic">
            "Consistency is the key to unlocking your true potential."
        </p>
    </div>
</div>

<!-- Workout Response Modal (AJAX Feedback) -->
<div id="workoutResponseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-xs p-6 text-center shadow-2xl">
        <div id="workoutModalIconContainer" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-gray-50">
            <i id="workoutModalIcon" class="fas fa-check text-2xl"></i>
        </div>
        <h3 id="workoutModalTitle" class="text-lg font-bold text-gray-800 mb-1">Success!</h3>
        <p id="workoutModalMessage" class="text-sm text-gray-500 mb-6">Workout logged successfully!</p>
        <button id="workoutModalClose" class="w-full py-2.5 bg-primary text-white rounded-lg font-bold hover:bg-opacity-90">
            OK
        </button>
    </div>
</div>

<style>
    .mood-btn { transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .mood-btn:hover { transform: scale(1.15); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Helpers
    const toggleModal = (id, show) => {
        const modal = document.getElementById(id);
        if (modal) modal.classList.toggle('hidden', !show);
    };

    // Workout Modal
    document.getElementById('logWorkoutBtn')?.addEventListener('click', () => toggleModal('workoutModal', true));
    document.getElementById('closeWorkoutModal')?.addEventListener('click', () => toggleModal('workoutModal', false));
    document.getElementById('workoutModal')?.addEventListener('click', (e) => {
        if (e.target.id === 'workoutModal') toggleModal('workoutModal', false);
    });

    // Journal Modal
    document.getElementById('quickJournalBtn')?.addEventListener('click', () => {
        document.getElementById('journalModalAction').value = 'add';
        document.getElementById('journalModalId').value = '';
        document.getElementById('journalModalNotes').value = '';
        toggleModal('journalModal', true);
    });
    document.getElementById('closeJournalModal')?.addEventListener('click', () => toggleModal('journalModal', false));
    document.getElementById('journalModal')?.addEventListener('click', (e) => {
        if (e.target.id === 'journalModal') toggleModal('journalModal', false);
    });

    // Goal Modal
    document.getElementById('goalProgressCard')?.addEventListener('click', () => toggleModal('goalProgressModal', true));
    document.getElementById('closeGoalModal')?.addEventListener('click', () => toggleModal('goalProgressModal', false));
    document.getElementById('goalProgressModal')?.addEventListener('click', (e) => {
        if (e.target.id === 'goalProgressModal') toggleModal('goalProgressModal', false);
    });

    // AJAX Workout Submission
    document.getElementById('workoutForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('dashboard.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            const icon = document.getElementById('workoutModalIcon');
            const iconContainer = document.getElementById('workoutModalIconContainer');
            const title = document.getElementById('workoutModalTitle');
            const message = document.getElementById('workoutModalMessage');
            
            if (result.success) {
                icon.className = 'fas fa-check text-green-500';
                iconContainer.className = 'w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-green-50';
                title.textContent = 'Awesome!';
            } else {
                icon.className = 'fas fa-exclamation-triangle text-red-500';
                iconContainer.className = 'w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-red-50';
                title.textContent = 'Oops!';
            }
            
            message.textContent = result.message;
            toggleModal('workoutModal', false);
            toggleModal('workoutResponseModal', true);
            
            document.getElementById('workoutModalClose').onclick = () => {
                if (result.success) location.reload();
                else toggleModal('workoutResponseModal', false);
            };
        } catch (err) {
            alert('Connection error. Please try again.');
        }
    });

    // Helper for editing mood from dashboard
    window.openEditMood = function(entry) {
        document.getElementById('journalModalAction').value = 'edit';
        document.getElementById('journalModalId').value = entry.id;
        document.getElementById('journalModalNotes').value = entry.notes;
        document.getElementById('journalModalDate').value = entry.date;
        
        // Select mood radio
        const moodRadio = document.querySelector(`input[name="journal_mood"][value="${entry.mood}"]`);
        if (moodRadio) moodRadio.checked = true;
        
        toggleModal('journalModal', true);
    };

    window.confirmAction = function(type, id) {
        if (confirm(`Are you sure you want to ${type} this entry?`)) {
            const form = document.getElementById('journalActionForm');
            document.getElementById('journalActionId').value = id;
            document.getElementById('journalActionType').value = type;
            form.submit();
        }
    };
});
</script>
