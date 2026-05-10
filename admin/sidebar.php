<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="bg-primary text-white w-64 min-h-screen flex-col shadow-xl hidden md:flex fixed z-20">
    <div class="p-6 border-b border-gray-800 flex items-center space-x-3">
        <i class="fas fa-dumbbell text-highlight text-2xl"></i>
        <h1 class="text-xl font-bold">Warzone Admin</h1>
    </div>
    
    <div class="flex items-center space-x-3 p-6 border-b border-gray-800">
        <img src="<?= htmlspecialchars(file_exists('../uploads/' . ($_SESSION['user_profile_picture'] ?? 'default.png')) 
                    ? '../uploads/' . $_SESSION['user_profile_picture'] 
                    : '../uploads/default.png') ?>" 
             alt="Profile" class="rounded-full w-12 h-12 border-2 border-highlight object-cover">
        <div>
            <p class="text-sm font-semibold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></p>
            <p class="text-xs text-green-400"><i class="fas fa-circle text-[8px] mr-1"></i> Online</p>
        </div>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <a href="index.php" class="block px-4 py-3 rounded-lg transition flex items-center <?= $current_page == 'index.php' ? 'bg-highlight text-white' : 'hover:bg-secondary hover:text-highlight' ?>">
            <i class="fas fa-home w-6"></i> <span>Dashboard</span>
        </a>
        <a href="users.php" class="block px-4 py-3 rounded-lg transition flex items-center <?= $current_page == 'users.php' ? 'bg-highlight text-white' : 'hover:bg-secondary hover:text-highlight' ?>">
            <i class="fas fa-users w-6"></i> <span>Users</span>
        </a>
        <a href="reports.php" class="block px-4 py-3 rounded-lg transition flex items-center <?= $current_page == 'reports.php' ? 'bg-highlight text-white' : 'hover:bg-secondary hover:text-highlight' ?>">
            <i class="fas fa-chart-pie w-6"></i> <span>Reports</span>
        </a>
        <a href="messages.php" class="block px-4 py-3 rounded-lg transition flex items-center <?= $current_page == 'messages.php' ? 'bg-highlight text-white' : 'hover:bg-secondary hover:text-highlight' ?>">
            <i class="fas fa-envelope w-6"></i> <span>Messages</span>
        </a>
        <a href="activity.php" class="block px-4 py-3 rounded-lg transition flex items-center <?= $current_page == 'activity.php' ? 'bg-highlight text-white' : 'hover:bg-secondary hover:text-highlight' ?>">
            <i class="fas fa-history w-6"></i> <span>Activity Log</span>
        </a>
    </nav>
    <div class="p-4 border-t border-gray-800">
        <a href="../logout.php" class="block px-4 py-3 text-gray-400 hover:text-highlight transition flex items-center">
            <i class="fas fa-sign-out-alt w-6"></i> <span>Logout</span>
        </a>
    </div>
</aside>

<!-- Mobile Header + Drawer -->
<div class="md:hidden sticky top-0 z-30">
    <div class="bg-primary text-white p-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center space-x-3">
            <i class="fas fa-dumbbell text-highlight text-xl"></i>
            <h1 class="text-lg font-bold">Warzone Admin</h1>
        </div>
        <button id="mobileMenuToggle" class="text-white focus:outline-none p-1" aria-label="Open navigation">
            <i class="fas fa-bars text-xl" id="menuIcon"></i>
        </button>
    </div>
    <!-- Slide-down drawer -->
    <div id="mobileDrawer" class="hidden bg-primary text-white border-t border-gray-800 shadow-lg">
        <nav class="px-4 py-3 space-y-1">
            <a href="index.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'index.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
                <i class="fas fa-home w-6"></i> Dashboard
            </a>
            <a href="users.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'users.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
                <i class="fas fa-users w-6"></i> Users
            </a>
            <a href="reports.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'reports.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
                <i class="fas fa-chart-pie w-6"></i> Reports
            </a>
            <a href="messages.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'messages.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
                <i class="fas fa-envelope w-6"></i> Messages
            </a>
            <a href="activity.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'activity.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
                <i class="fas fa-history w-6"></i> Activity Log
            </a>
            <a href="../logout.php" class="flex items-center px-4 py-3 rounded-lg text-gray-400 hover:text-highlight hover:bg-secondary">
                <i class="fas fa-sign-out-alt w-6"></i> Logout
            </a>
        </nav>
    </div>
</div>
<script>
    (function() {
        const toggle = document.getElementById('mobileMenuToggle');
        const drawer = document.getElementById('mobileDrawer');
        const icon   = document.getElementById('menuIcon');
        if (toggle && drawer) {
            toggle.addEventListener('click', function() {
                const isOpen = !drawer.classList.contains('hidden');
                drawer.classList.toggle('hidden', isOpen);
                icon.className = isOpen ? 'fas fa-bars text-xl' : 'fas fa-times text-xl';
            });
        }
    })();
</script>
