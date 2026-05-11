<?php
// admin/ai_settings.php - API-First AI Configuration
require_once '../auth.php';
require_admin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Admin - AI Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', secondary: '#16213e', accent: '#0f3460', highlight: '#e94560' } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fa; }
        .persona-card { transition: all 0.3s ease; }
        .persona-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .active-persona { border-color: #e94560; background: #fff1f2; }
    </style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 md:ml-64 w-full flex flex-col">
        <main class="container mx-auto px-8 py-10 flex-grow">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-12">
                <div>
                    <h1 class="text-4xl font-black text-gray-800 tracking-tighter">AI Behavioral Intelligence</h1>
                    <p class="text-gray-500 font-medium">Configure global coach personalities and automated directives.</p>
                </div>
                <button onclick="openModal()" class="bg-highlight text-white px-8 py-4 rounded-2xl font-bold shadow-xl hover:opacity-90 transition">
                    <i class="fas fa-plus mr-2"></i> Add Persona
                </button>
            </div>

            <div id="personaList" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Dynamic Personas -->
                <div class="h-48 bg-gray-100 rounded-3xl animate-pulse"></div>
                <div class="h-48 bg-gray-100 rounded-3xl animate-pulse"></div>
            </div>
        </main>

        <footer class="bg-white border-t border-gray-100 py-10">
            <div class="text-center text-gray-400 text-xs font-bold uppercase tracking-widest">
                Warzone AI Engine • Neural Configuration Suite
            </div>
        </footer>
    </div>

    <!-- Modal -->
    <div id="aiModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 p-6 backdrop-blur-sm">
        <div class="bg-white rounded-3xl w-full max-w-xl p-10 shadow-2xl border border-gray-100">
            <h2 id="modalTitle" class="text-3xl font-black text-gray-800 mb-8 tracking-tighter">Neural Directive</h2>
            <form id="aiForm" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="personaId">
                
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Persona Identity</label>
                    <input type="text" name="name" id="personaName" required class="w-full border-gray-100 border bg-gray-50 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-highlight outline-none font-bold" placeholder="e.g. Master Sergeant">
                </div>
                
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Cognitive Instructions</label>
                    <textarea name="instructions" id="personaInstructions" rows="6" required class="w-full border-gray-100 border bg-gray-50 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-highlight outline-none font-medium text-sm leading-relaxed" placeholder="Define the behavioral traits, tone, and directives..."></textarea>
                </div>

                <div class="flex gap-4 pt-6">
                    <button type="button" onclick="closeModal()" class="flex-1 py-5 text-gray-400 font-black uppercase tracking-widest hover:text-gray-600 transition">Cancel</button>
                    <button type="submit" class="flex-1 bg-primary text-white py-5 rounded-2xl font-black shadow-xl hover:opacity-95 transition tracking-widest">SAVE DIRECTIVE</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function fetchPersonas() {
            try {
                const response = await fetch('../api/admin/ai.php');
                const res = await response.json();
                if (res.status === 'success') renderPersonas(res.data.personas);
            } catch (err) { console.error(err); }
        }

        function renderPersonas(personas) {
            const container = document.getElementById('personaList');
            container.innerHTML = personas.map(p => `
                <div class="persona-card bg-white p-8 rounded-3xl shadow-sm border ${p.status === 'active' ? 'active-persona border-highlight/20' : 'border-gray-100'}">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="text-2xl font-black text-gray-800 tracking-tighter">${p.name}</h3>
                                ${p.status === 'active' ? '<span class="px-2 py-1 bg-highlight text-white text-[9px] font-black rounded-lg uppercase tracking-widest">Live</span>' : ''}
                            </div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Core ID: ${p.id}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick='editPersona(${JSON.stringify(p)})' class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 hover:text-blue-500 transition flex items-center justify-center"><i class="fas fa-brain"></i></button>
                            <button onclick="toggleStatus(${p.id}, '${p.status}')" class="w-10 h-10 rounded-xl ${p.status === 'active' ? 'bg-green-50 text-green-500' : 'bg-gray-50 text-gray-400'} hover:opacity-80 transition flex items-center justify-center"><i class="fas fa-power-off"></i></button>
                        </div>
                    </div>
                    <div class="bg-white/50 p-6 rounded-2xl text-sm text-gray-600 font-medium italic border border-gray-50/50 line-clamp-3">
                        "${p.instructions}"
                    </div>
                </div>
            `).join('');
        }

        async function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const formData = new FormData();
            formData.append('action', 'toggle_persona');
            formData.append('id', id);
            formData.append('status', newStatus);
            formData.append('csrf_token', '<?= csrf_token() ?>');

            try {
                const res = await fetch('../api/admin/ai.php', { method: 'POST', body: formData });
                if ((await res.json()).status === 'success') fetchPersonas();
            } catch (err) { console.error(err); }
        }

        function openModal() {
            document.getElementById('modalTitle').innerText = 'New Neural Directive';
            document.getElementById('personaId').value = '';
            document.getElementById('aiForm').reset();
            document.getElementById('aiModal').classList.remove('hidden');
        }

        function closeModal() { document.getElementById('aiModal').classList.add('hidden'); }

        function editPersona(p) {
            document.getElementById('modalTitle').innerText = 'Reconfigure Directive';
            document.getElementById('personaId').value = p.id;
            document.getElementById('personaName').value = p.name;
            document.getElementById('personaInstructions').value = p.instructions;
            document.getElementById('aiModal').classList.remove('hidden');
        }

        document.getElementById('aiForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_persona');
            
            try {
                const res = await fetch('../api/admin/ai.php', { method: 'POST', body: formData });
                if ((await res.json()).status === 'success') {
                    closeModal();
                    fetchPersonas();
                }
            } catch (err) { console.error(err); }
        });

        document.addEventListener('DOMContentLoaded', fetchPersonas);
    </script>
</body>
</html>
