<?php
// admin/users.php - API-First Version
require_once '../auth.php';
require_admin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', secondary: '#16213e', accent: '#0f3460', highlight: '#e94560' } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fa; }
        .user-row { transition: all 0.2s; }
        .user-row:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 md:ml-64 w-full flex flex-col">
        <main class="container mx-auto px-6 py-8 flex-grow">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
                <div>
                    <h2 class="text-4xl font-black text-gray-800">Member Directory</h2>
                    <p class="text-gray-500 font-medium">Manage permissions and oversee gym membership.</p>
                </div>
                <a href="../register.php" class="bg-highlight text-white px-8 py-4 rounded-2xl font-bold flex items-center hover:opacity-90 transition shadow-xl">
                    <i class="fas fa-user-plus mr-2"></i> Register New
                </a>
            </div>

            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Member</th>
                                <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Account Info</th>
                                <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Role</th>
                                <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Joined Date</th>
                                <th class="px-8 py-5 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody" class="divide-y divide-gray-50">
                            <!-- Dynamic Rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <footer class="bg-white border-t border-gray-100 py-8">
            <div class="text-center text-gray-400 text-xs font-bold uppercase tracking-widest">
                © 2026 Warzone Gym CRM • Administrative Suite
            </div>
        </footer>
    </div>

    <script>
        async function fetchUsers() {
            const body = document.getElementById('usersTableBody');
            body.innerHTML = `<tr><td colspan="5" class="py-20 text-center"><i class="fas fa-spinner fa-spin text-3xl text-highlight"></i></td></tr>`;
            
            try {
                const response = await fetch('../api/admin/users.php');
                const res = await response.json();
                if (res.status === 'success') renderTable(res.data.users);
            } catch (err) { console.error(err); }
        }

        function renderTable(users) {
            const body = document.getElementById('usersTableBody');
            const currentUserId = <?= (int)$_SESSION['user_id'] ?>;
            
            body.innerHTML = users.map(u => `
                <tr class="user-row">
                    <td class="px-8 py-5 whitespace-nowrap">
                        <div class="flex items-center">
                            <img class="h-12 w-12 rounded-2xl object-cover border-2 border-gray-50" src="../uploads/${u.profile_picture || 'default.png'}" alt="">
                            <div class="ml-4">
                                <div class="text-sm font-black text-gray-800">${u.name}</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">ID: ${u.id}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-5 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-600">${u.email}</div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase">Last Login: ${u.last_login ? new Date(u.last_login).toLocaleString() : 'Never'}</div>
                    </td>
                    <td class="px-8 py-5 whitespace-nowrap">
                        <span class="px-3 py-1 text-[10px] font-black rounded-lg uppercase tracking-widest ${u.role === 'admin' ? 'bg-highlight text-white' : 'bg-gray-100 text-gray-600'}">
                            ${u.role}
                        </span>
                    </td>
                    <td class="px-8 py-5 whitespace-nowrap text-sm font-bold text-gray-400">
                        ${new Date(u.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                    </td>
                    <td class="px-8 py-5 whitespace-nowrap text-sm font-bold">
                        ${u.id !== currentUserId ? `
                            <button onclick="updateRole(${u.id}, '${u.role}')" class="text-blue-500 hover:text-blue-700 mr-4 transition uppercase tracking-tighter">
                                ${u.role === 'admin' ? 'Demote' : 'Promote'}
                            </button>
                            <button onclick="deleteUser(${u.id})" class="text-red-400 hover:text-red-600 transition uppercase tracking-tighter">
                                Delete
                            </button>
                        ` : '<span class="text-gray-300 italic uppercase text-[10px]">Active Session</span>'}
                    </td>
                </tr>
            `).join('');
        }

        async function updateRole(id, currentRole) {
            const newRole = currentRole === 'admin' ? 'user' : 'admin';
            const formData = new FormData();
            formData.append('action', 'update_role');
            formData.append('user_id', id);
            formData.append('role', newRole);
            formData.append('csrf_token', '<?= csrf_token() ?>');

            try {
                const res = await fetch('../api/admin/users.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') fetchUsers();
            } catch (err) { console.error(err); }
        }

        async function deleteUser(id) {
            if (!confirm('Permanently remove this member? This cannot be undone.')) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', id);
            formData.append('csrf_token', '<?= csrf_token() ?>');

            try {
                const res = await fetch('../api/admin/users.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') fetchUsers();
            } catch (err) { console.error(err); }
        }

        document.addEventListener('DOMContentLoaded', fetchUsers);
    </script>
</body>
</html>