<?php
// journal.php - API-First Version (Mood + Metrics)
require_once 'auth.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Journey Journal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', secondary: '#16213e', accent: '#0f3460', highlight: '#e94560', success: '#06d6a0' } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fa; }
        .journal-card { transition: all 0.3s ease; border-left: 5px solid transparent; }
        .journal-card:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -10px rgba(0,0,0,0.1); }
        .journal-card.starred { border-left-color: #fbbf24; background: #fffbeb; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; border-radius: 2rem; width: 100%; max-width: 600px; padding: 2.5rem; overflow-y: auto; max-height: 90vh; }
        .mood-btn { width: 55px; height: 55px; font-size: 1.8rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; background: #f8fafc; cursor: pointer; transition: all 0.2s; border: 2px solid transparent; }
        .mood-btn:hover { background: #f1f5f9; transform: scale(1.05); }
        .mood-btn.active { background: #fee2e2; border-color: #e94560; transform: scale(1.1); }
        .metric-input { text-align: center; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <?php include 'includes/user_nav.php'; ?>

    <main class="container mx-auto px-4 py-8 flex-grow">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Journey Journal</h1>
                <p class="text-gray-500">Document your evolution, from mindset to metrics.</p>
            </div>
            <button onclick="openModal()" class="bg-highlight text-white px-8 py-4 rounded-2xl font-bold shadow-xl hover:opacity-90 transition transform hover:-translate-y-1">
                <i class="fas fa-plus mr-2"></i> New Entry
            </button>
        </div>

        <!-- Filters -->
        <div class="flex space-x-3 mb-8 overflow-x-auto pb-2">
            <button onclick="setFilter('active')" id="filterActive" class="px-6 py-2 rounded-xl font-bold transition whitespace-nowrap bg-highlight text-white">Active</button>
            <button onclick="setFilter('starred')" id="filterStarred" class="px-6 py-2 rounded-xl font-bold transition whitespace-nowrap bg-white text-gray-500 border">Starred</button>
            <button onclick="setFilter('archived')" id="filterArchived" class="px-6 py-2 rounded-xl font-bold transition whitespace-nowrap bg-white text-gray-500 border">Archived</button>
        </div>

        <div id="journalEntries" class="space-y-6 max-w-4xl mx-auto">
            <!-- Dynamic Entries -->
        </div>
    </main>

    <!-- Entry Modal -->
    <div id="journalModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-8">
                <h3 class="text-2xl font-black text-gray-800">Daily Log</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            <form id="journalForm" class="space-y-6">
                <?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Date</label>
                        <input type="date" name="date" required value="<?= date('Y-m-d') ?>" class="w-full border-gray-100 border bg-gray-50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-highlight outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Mood</label>
                        <div class="flex justify-between">
                            <div class="mood-btn active" data-mood="😊">😊</div>
                            <div class="mood-btn" data-mood="😁">😁</div>
                            <div class="mood-btn" data-mood="😐">😐</div>
                            <div class="mood-btn" data-mood="😠">😠</div>
                        </div>
                        <input type="hidden" name="mood" id="selectedMood" value="😊">
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase text-center mb-1">Weight (kg)</label>
                        <input type="number" name="weight" step="0.1" class="w-full bg-transparent metric-input outline-none">
                    </div>
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase text-center mb-1">Chest (cm)</label>
                        <input type="number" name="chest" step="0.1" class="w-full bg-transparent metric-input outline-none">
                    </div>
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase text-center mb-1">Waist (cm)</label>
                        <input type="number" name="waist" step="0.1" class="w-full bg-transparent metric-input outline-none">
                    </div>
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase text-center mb-1">Hips (cm)</label>
                        <input type="number" name="hips" step="0.1" class="w-full bg-transparent metric-input outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Notes & Thoughts</label>
                    <textarea name="notes" class="w-full border-gray-100 border bg-gray-50 rounded-2xl px-4 py-4 focus:ring-2 focus:ring-highlight outline-none" rows="4" placeholder="How was the session? What's on your mind?"></textarea>
                </div>

                <button type="submit" class="w-full bg-primary text-white py-5 rounded-2xl font-bold text-lg shadow-xl hover:opacity-95 transition">Save Journey Entry</button>
            </form>
        </div>
    </div>

    <script>
        let currentFilter = 'active';

        async function fetchEntries() {
            const container = document.getElementById('journalEntries');
            container.innerHTML = '<div class="text-center py-20"><i class="fas fa-spinner fa-spin text-3xl text-highlight"></i></div>';
            
            try {
                const response = await fetch(`api/journal.php?filter=${currentFilter}`);
                const res = await response.json();
                if (res.status === 'success') renderEntries(res.data.history);
            } catch (err) { console.error(err); }
        }

        function renderEntries(entries) {
            const container = document.getElementById('journalEntries');
            if (entries.length === 0) {
                container.innerHTML = `<div class="bg-white p-20 rounded-3xl text-center border-2 border-dashed border-gray-100">
                    <i class="fas fa-book-open text-4xl text-gray-200 mb-4"></i>
                    <p class="text-gray-400 font-bold">Your story hasn't started yet.</p>
                </div>`;
                return;
            }

            container.innerHTML = entries.map(e => `
                <div class="journal-card bg-white p-6 rounded-3xl shadow-sm border border-gray-100 ${e.starred ? 'starred' : ''}">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-4">
                            <span class="text-3xl">${e.mood}</span>
                            <div>
                                <h4 class="font-black text-gray-800">${new Date(e.date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</h4>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Entry #${e.id}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="toggleAction(${e.id}, '${e.starred ? 'unstar' : 'star'}')" class="p-2 rounded-lg hover:bg-yellow-50 text-yellow-500 transition"><i class="${e.starred ? 'fas' : 'far'} fa-star"></i></button>
                            <button onclick="toggleAction(${e.id}, '${e.archived ? 'unarchive' : 'archive'}')" class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 transition"><i class="fas fa-archive"></i></button>
                            <button onclick="toggleAction(${e.id}, 'delete')" class="p-2 rounded-lg hover:bg-red-50 text-red-400 transition"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>

                    ${(e.weight || e.chest || e.waist || e.hips) ? `
                        <div class="grid grid-cols-4 gap-4 mb-4">
                            ${e.weight ? `<div class="bg-gray-50 p-3 rounded-xl"><p class="text-[10px] font-bold text-gray-400 uppercase">Weight</p><p class="font-black text-gray-700">${e.weight}kg</p></div>` : ''}
                            ${e.chest ? `<div class="bg-gray-50 p-3 rounded-xl"><p class="text-[10px] font-bold text-gray-400 uppercase">Chest</p><p class="font-black text-gray-700">${e.chest}cm</p></div>` : ''}
                            ${e.waist ? `<div class="bg-gray-50 p-3 rounded-xl"><p class="text-[10px] font-bold text-gray-400 uppercase">Waist</p><p class="font-black text-gray-700">${e.waist}cm</p></div>` : ''}
                            ${e.hips ? `<div class="bg-gray-50 p-3 rounded-xl"><p class="text-[10px] font-bold text-gray-400 uppercase">Hips</p><p class="font-black text-gray-700">${e.hips}cm</p></div>` : ''}
                        </div>
                    ` : ''}

                    <p class="text-gray-600 leading-relaxed">${e.notes || '<span class="italic text-gray-300">No notes written for this entry.</span>'}</p>
                </div>
            `).join('');
        }

        async function toggleAction(id, action) {
            if (action === 'delete' && !confirm('Permanently delete this entry?')) return;
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', action);
            formData.append('csrf_token', '<?= csrf_token() ?>');

            try {
                const response = await fetch('api/journal.php', { method: 'POST', body: formData });
                const res = await response.json();
                if (res.status === 'success') fetchEntries();
            } catch (err) { console.error(err); }
        }

        function setFilter(filter) {
            currentFilter = filter;
            document.querySelectorAll('[id^="filter"]').forEach(btn => {
                btn.classList.remove('bg-highlight', 'text-white');
                btn.classList.add('bg-white', 'text-gray-500', 'border');
            });
            const activeBtn = document.getElementById(`filter${filter.charAt(0).toUpperCase() + filter.slice(1)}`);
            activeBtn.classList.remove('bg-white', 'text-gray-500', 'border');
            activeBtn.classList.add('bg-highlight', 'text-white');
            fetchEntries();
        }

        // Mood Buttons Logic
        document.querySelectorAll('.mood-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selectedMood').value = this.dataset.mood;
            });
        });

        function openModal() { document.getElementById('journalModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('journalModal').style.display = 'none'; }

        document.getElementById('journalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save');
            
            try {
                const res = await fetch('api/journal.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') {
                    closeModal();
                    fetchEntries();
                    this.reset();
                } else alert(result.message);
            } catch (err) { console.error(err); }
        });

        document.addEventListener('DOMContentLoaded', fetchEntries);
    </script>
    <?php include 'modal_logout.php'; ?>
</body>
</html>