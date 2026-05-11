<?php
// workouts.php - API-First Version
require_once 'auth.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Workout History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', secondary: '#16213e', accent: '#0f3460', highlight: '#e94560', success: '#06d6a0' } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fa; }
        .btn-primary { background: linear-gradient(45deg, #e94560, #0f3460); color: white; transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; border-radius: 1.5rem; width: 100%; max-width: 550px; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .mood-btn { width: 55px; height: 55px; font-size: 1.8rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; background: #f8fafc; cursor: pointer; transition: all 0.2s; border: 2px solid transparent; }
        .mood-btn:hover { transform: scale(1.05); background: #f1f5f9; }
        .mood-btn.active { background: #fee2e2; border-color: #e94560; transform: scale(1.1); }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">
    <?php include 'includes/user_nav.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Workout History</h1>
                <p class="text-gray-500">Track every session of your transformation</p>
            </div>
            <button onclick="openWorkoutModal()" class="btn-primary px-6 py-3 rounded-xl flex items-center font-bold shadow-lg">
                <i class="fas fa-plus mr-2"></i> Log Workout
            </button>
        </div>

        <div id="workoutsTableContainer" class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Duration</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Calories</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Mood</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="workoutsTableBody" class="divide-y divide-gray-100">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>
            
            <div id="pagination" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                <!-- Pagination Metadata & Buttons -->
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="workoutModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Log Workout</h3>
                <button onclick="closeWorkoutModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            <form id="workoutForm" class="space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Workout Type</label>
                    <select name="workout_type" required class="w-full border-gray-200 border rounded-xl px-4 py-3 focus:ring-2 focus:ring-highlight outline-none">
                        <option value="Upper Body">Upper Body</option>
                        <option value="Lower Body">Lower Body</option>
                        <option value="Cardio">Cardio</option>
                        <option value="Full Body">Full Body</option>
                        <option value="HIIT">HIIT</option>
                        <option value="Yoga">Yoga</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Duration (mins)</label>
                        <input type="number" name="duration" required min="1" class="w-full border-gray-200 border rounded-xl px-4 py-3 focus:ring-2 focus:ring-highlight outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Calories</label>
                        <input type="number" name="calories" min="0" class="w-full border-gray-200 border rounded-xl px-4 py-3 focus:ring-2 focus:ring-highlight outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Notes</label>
                    <textarea name="workout_notes" class="w-full border-gray-200 border rounded-xl px-4 py-3 focus:ring-2 focus:ring-highlight outline-none" rows="3"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 text-center">How are you feeling?</label>
                    <div class="flex justify-between">
                        <div class="mood-btn active" data-mood="😊">😊</div>
                        <div class="mood-btn" data-mood="😁">😁</div>
                        <div class="mood-btn" data-mood="😐">😐</div>
                        <div class="mood-btn" data-mood="😠">😠</div>
                    </div>
                    <input type="hidden" name="workout_mood" id="selectedMood" value="😊">
                </div>
                <button type="submit" class="w-full btn-primary py-4 rounded-xl font-bold text-lg shadow-xl">Save Workout</button>
            </form>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let selectedMood = '😊';

        // Initialize Mood Buttons
        document.querySelectorAll('.mood-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                selectedMood = this.dataset.mood;
                document.getElementById('selectedMood').value = selectedMood;
            });
        });

        async function fetchWorkouts(page = 1) {
            currentPage = page;
            const container = document.getElementById('workoutsTableBody');
            container.innerHTML = `<tr><td colspan="6" class="py-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-highlight"></i></td></tr>`;
            
            try {
                const response = await fetch(`api/workouts.php?page=${page}`);
                const res = await response.json();
                
                if (res.status === 'success') {
                    renderTable(res.data.workouts);
                    renderPagination(res.data.pagination);
                }
            } catch (err) {
                console.error(err);
            }
        }

        function renderTable(workouts) {
            const body = document.getElementById('workoutsTableBody');
            if (workouts.length === 0) {
                body.innerHTML = `<tr><td colspan="6" class="py-20 text-center"><p class="text-gray-400">No sessions logged yet.</p></td></tr>`;
                return;
            }
            
            body.innerHTML = workouts.map(w => `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${new Date(w.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700">${w.type}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${w.duration} mins</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${w.calories_burned} kcal</td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-xl">${w.mood}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button onclick="deleteWorkout(${w.id})" class="text-red-400 hover:text-red-600 transition"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(meta) {
            const container = document.getElementById('pagination');
            container.innerHTML = `
                <p class="text-sm text-gray-500">Page ${meta.current_page} of ${meta.total_pages}</p>
                <div class="flex space-x-2">
                    <button onclick="fetchWorkouts(${meta.current_page - 1})" ${meta.current_page === 1 ? 'disabled' : ''} class="px-4 py-2 bg-white border rounded-lg text-sm font-bold disabled:opacity-50">Prev</button>
                    <button onclick="fetchWorkouts(${meta.current_page + 1})" ${meta.current_page === meta.total_pages ? 'disabled' : ''} class="px-4 py-2 bg-white border rounded-lg text-sm font-bold disabled:opacity-50">Next</button>
                </div>
            `;
        }

        function openWorkoutModal() { document.getElementById('workoutModal').style.display = 'flex'; }
        function closeWorkoutModal() { document.getElementById('workoutModal').style.display = 'none'; }

        document.getElementById('workoutForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'log');
            
            try {
                const res = await fetch('api/workouts.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') {
                    closeWorkoutModal();
                    fetchWorkouts(1);
                    this.reset();
                } else {
                    alert(result.message);
                }
            } catch (err) { console.error(err); }
        });

        async function deleteWorkout(id) {
            if (!confirm('Are you sure you want to delete this workout?')) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('workout_id', id);
            formData.append('csrf_token', '<?= csrf_token() ?>');

            try {
                const res = await fetch('api/workouts.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') fetchWorkouts(currentPage);
            } catch (err) { console.error(err); }
        }

        document.addEventListener('DOMContentLoaded', () => fetchWorkouts(1));
    </script>
</body>
</html>