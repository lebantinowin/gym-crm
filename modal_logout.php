<!-- Warzone Logout Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Logout Confirmation</h3>
            <button id="closeLogoutModal" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="text-center py-4">
            <div class="w-16 h-16 bg-highlight text-white rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-door-open text-2xl"></i>
            </div>
            <p class="text-gray-700">
                Ready to tap out, ka-warzone?<br>
                Your progress is saved — come back stronger.
            </p>
        </div>
        <div class="flex space-x-3">
            <button id="confirmLogout" class="flex-1 bg-highlight text-white py-3 rounded-lg font-semibold hover:bg-opacity-90 transition">
                <i class="fas fa-sign-out-alt mr-2"></i> Yes, Logout
            </button>
            <button id="cancelLogout" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('a[href="logout.php"]').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        document.getElementById('logoutModal').classList.remove('hidden');
    });
});

['closeLogoutModal', 'cancelLogout'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', () => {
        document.getElementById('logoutModal').classList.add('hidden');
    });
});

document.getElementById('confirmLogout')?.addEventListener('click', () => {
    const btn = document.getElementById('confirmLogout');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Logging out...';
    btn.disabled = true;
    setTimeout(() => window.location.href = 'logout.php', 600);
});
</script>