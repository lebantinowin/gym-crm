<?php
// includes/user_nav.php - Reusable User Navigation
$active_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-primary text-white shadow-lg sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-dumbbell text-highlight text-2xl"></i>
                <a href="dashboard.php" class="text-xl font-bold text-white hover:text-highlight transition">
                    Warzone Gym CRM
                </a>
            </div>
            <div class="hidden md:flex items-center space-x-6">
                <a href="dashboard.php" class="hover:text-highlight transition <?= $active_page == 'dashboard.php' ? 'font-semibold text-highlight' : '' ?>">Dashboard</a>
                <a href="workouts.php" class="hover:text-highlight transition <?= $active_page == 'workouts.php' ? 'font-semibold text-highlight' : '' ?>">Workouts</a>
                <a href="attendance.php" class="hover:text-highlight transition <?= $active_page == 'attendance.php' ? 'font-semibold text-highlight' : '' ?>">Attendance</a>
                <a href="journal.php" class="hover:text-highlight transition <?= $active_page == 'journal.php' ? 'font-semibold text-highlight' : '' ?>">Journal</a>
                <a href="chat.php" class="hover:text-highlight transition <?= $active_page == 'chat.php' ? 'font-semibold text-highlight' : '' ?>">Chat</a>
                <a href="profile.php" class="hover:text-highlight transition <?= $active_page == 'profile.php' ? 'font-semibold text-highlight' : '' ?>">Profile</a>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Profile picture (desktop) -->
                <a href="profile.php" class="hidden md:flex items-center space-x-2 group" title="View Profile">
                    <img src="<?= htmlspecialchars(file_exists('uploads/' . ($_SESSION['user_profile_picture'] ?? 'default.png')) 
                                ? 'uploads/' . $_SESSION['user_profile_picture'] 
                                : 'uploads/default.png') ?>" 
                        alt="Profile" 
                        class="rounded-full w-10 h-10 transition-transform duration-200 group-hover:scale-105 group-hover:ring-2 group-hover:ring-highlight">
                    <a href="logout.php" 
                    class="text-gray-400 hover:text-highlight transition ml-1 opacity-75 group-hover:opacity-100" 
                    title="Logout">
                        <i class="fas fa-sign-out-alt text-sm"></i>
                    </a>
                </a>
                <!-- Mobile: hamburger -->
                <button id="userNavToggle" class="md:hidden text-white focus:outline-none p-1" aria-label="Open menu">
                    <i class="fas fa-bars text-xl" id="userNavIcon"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile nav drawer -->
<div id="userNavDrawer" class="md:hidden hidden bg-primary text-white border-t border-gray-800 shadow-lg fixed top-[60px] left-0 w-full z-40 overflow-y-auto max-h-[calc(100vh-60px)]">
    <div class="px-4 py-3 space-y-1">
        <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg <?= $active_page == 'dashboard.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
            <i class="fas fa-home w-6"></i> Dashboard
        </a>
        <a href="workouts.php" class="flex items-center px-4 py-3 rounded-lg <?= $active_page == 'workouts.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
            <i class="fas fa-dumbbell w-6"></i> Workouts
        </a>
        <a href="attendance.php" class="flex items-center px-4 py-3 rounded-lg <?= $active_page == 'attendance.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
            <i class="fas fa-calendar-check w-6"></i> Attendance
        </a>
        <a href="journal.php" class="flex items-center px-4 py-3 rounded-lg <?= $active_page == 'journal.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
            <i class="fas fa-book w-6"></i> Journal
        </a>
        <a href="chat.php" class="flex items-center px-4 py-3 rounded-lg <?= $active_page == 'chat.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
            <i class="fas fa-robot w-6"></i> AI Coach
        </a>
        <a href="profile.php" class="flex items-center px-4 py-3 rounded-lg <?= $active_page == 'profile.php' ? 'bg-highlight text-white' : 'hover:bg-secondary text-gray-300 hover:text-highlight' ?>">
            <i class="fas fa-user w-6"></i> Profile
        </a>
        <a href="logout.php" class="flex items-center px-4 py-3 rounded-lg text-gray-400 hover:text-highlight hover:bg-secondary">
            <i class="fas fa-sign-out-alt w-6"></i> Logout
        </a>
    </div>
</div>

<script>
(function() {
    const toggle = document.getElementById('userNavToggle');
    const drawer = document.getElementById('userNavDrawer');
    const icon   = document.getElementById('userNavIcon');
    if (toggle && drawer) {
        toggle.addEventListener('click', function() {
            const isOpen = !drawer.classList.contains('hidden');
            drawer.classList.toggle('hidden', isOpen);
            icon.className = isOpen ? 'fas fa-bars text-xl' : 'fas fa-times text-xl';
            document.body.style.overflow = isOpen ? '' : 'hidden'; // Prevent body scroll when menu open
        });
    }
})();
</script>
