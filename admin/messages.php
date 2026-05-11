<?php
// admin/messages.php - API-First Messaging System
require_once '../auth.php';
require_admin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Admin - Communications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', secondary: '#16213e', accent: '#0f3460', highlight: '#e94560' } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fa; }
        .conversation-item { transition: all 0.2s; border-radius: 1rem; margin-bottom: 0.5rem; }
        .conversation-item:hover { background: #f8fafc; }
        .conversation-active { background: #eff6ff !important; border-left: 4px solid #e94560; }
        .message-bubble { max-width: 75%; border-radius: 1.5rem; }
        .sent { background: #1a1a2e; color: white; border-bottom-right-radius: 0.5rem; }
        .received { background: #f1f5f9; color: #1e293b; border-bottom-left-radius: 0.5rem; }
    </style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 md:ml-64 w-full flex flex-col h-screen">
        <main class="flex-grow flex flex-col h-full overflow-hidden">
            <div class="p-8 border-b bg-white">
                <h1 class="text-3xl font-black text-gray-800 tracking-tighter">Communications Center</h1>
                <p class="text-gray-500 font-medium text-sm">Direct engagement with the Warzone community.</p>
            </div>

            <div class="flex-grow flex overflow-hidden">
                <!-- Left: Conversations -->
                <div class="w-1/3 border-r bg-white overflow-y-auto p-4" id="conversationList">
                    <div class="space-y-4">
                        <div class="h-16 bg-gray-50 rounded-2xl animate-pulse"></div>
                        <div class="h-16 bg-gray-50 rounded-2xl animate-pulse"></div>
                    </div>
                </div>

                <!-- Right: Active Chat -->
                <div class="flex-1 flex flex-col bg-gray-50" id="chatArea">
                    <div class="flex-1 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <i class="fas fa-comment-dots text-6xl mb-4 opacity-20"></i>
                            <p class="font-bold uppercase tracking-widest text-xs">Select a member to start chat</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let currentContactId = null;

        async function fetchConversations() {
            try {
                const response = await fetch('../api/messages.php');
                const res = await response.json();
                if (res.status === 'success') renderConversations(res.data.conversations);
            } catch (err) { console.error(err); }
        }

        function renderConversations(convs) {
            const list = document.getElementById('conversationList');
            if (convs.length === 0) {
                list.innerHTML = `<p class="text-center py-10 text-xs font-bold text-gray-400">No conversations yet.</p>`;
                return;
            }

            list.innerHTML = convs.map(c => `
                <div onclick="selectConversation(${c.contact_id})" class="conversation-item p-4 flex items-center cursor-pointer ${currentContactId === c.contact_id ? 'conversation-active' : ''}">
                    <img src="../uploads/${c.profile_picture || 'default.png'}" class="w-12 h-12 rounded-2xl object-cover mr-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <h4 class="font-black text-gray-800 truncate text-sm">${c.contact_name}</h4>
                            ${c.unread_count > 0 ? `<span class="bg-highlight text-white text-[9px] font-black px-2 py-0.5 rounded-full">${c.unread_count}</span>` : ''}
                        </div>
                        <p class="text-xs text-gray-400 truncate mt-1">${c.last_message || 'No messages yet'}</p>
                    </div>
                </div>
            `).join('');
        }

        async function selectConversation(id) {
            currentContactId = id;
            fetchConversations(); // refresh list to update active state
            
            const chatArea = document.getElementById('chatArea');
            chatArea.innerHTML = `
                <div class="flex-1 flex flex-col h-full overflow-hidden">
                    <div id="messagesFeed" class="flex-1 overflow-y-auto p-8 space-y-6">
                        <div class="text-center py-20"><i class="fas fa-spinner fa-spin text-highlight text-3xl"></i></div>
                    </div>
                    <div class="p-8 border-t bg-white">
                        <form id="messageForm" class="flex gap-4">
                            <textarea id="messageInput" class="flex-1 bg-gray-50 border-gray-100 border rounded-2xl px-6 py-4 focus:ring-2 focus:ring-highlight outline-none resize-none font-medium" rows="2" placeholder="Write a secure response..."></textarea>
                            <button type="submit" class="bg-primary text-white w-20 rounded-2xl shadow-xl hover:opacity-90 transition flex items-center justify-center">
                                <i class="fas fa-paper-plane text-xl"></i>
                            </button>
                        </form>
                    </div>
                </div>
            `;

            fetchMessages(id);

            document.getElementById('messageForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const msg = document.getElementById('messageInput').value.trim();
                if (!msg) return;

                const formData = new FormData();
                formData.append('receiver_id', id);
                formData.append('message', msg);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                try {
                    const res = await fetch('../api/messages.php', { method: 'POST', body: formData });
                    if ((await res.json()).status === 'success') {
                        document.getElementById('messageInput').value = '';
                        fetchMessages(id);
                    }
                } catch (err) { console.error(err); }
            });
        }

        async function fetchMessages(id) {
            try {
                const response = await fetch(`../api/messages.php?contact_id=${id}`);
                const res = await response.json();
                if (res.status === 'success') {
                    const feed = document.getElementById('messagesFeed');
                    feed.innerHTML = res.data.messages.map(m => `
                        <div class="flex ${m.sender_id == <?= (int)$_SESSION['user_id'] ?> ? 'justify-end' : 'justify-start'}">
                            <div class="message-bubble px-6 py-4 ${m.sender_id == <?= (int)$_SESSION['user_id'] ?> ? 'sent shadow-lg shadow-primary/10' : 'received'}">
                                <p class="text-sm font-medium leading-relaxed">${m.message}</p>
                                <p class="text-[9px] mt-2 font-black opacity-40 uppercase tracking-widest">${new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</p>
                            </div>
                        </div>
                    `).join('');
                    feed.scrollTop = feed.scrollHeight;
                }
            } catch (err) { console.error(err); }
        }

        document.addEventListener('DOMContentLoaded', fetchConversations);
    </script>
</body>
</html>