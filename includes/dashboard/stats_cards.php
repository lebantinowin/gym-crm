<!-- includes/dashboard/stats_cards.php -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="statsContainer">
    <!-- Skeleton Loaders -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 animate-pulse">
        <div class="w-12 h-12 bg-gray-100 rounded-2xl"></div>
        <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-100 rounded w-1/2"></div>
            <div class="h-6 bg-gray-100 rounded w-1/4"></div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 animate-pulse">
        <div class="w-12 h-12 bg-gray-100 rounded-2xl"></div>
        <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-100 rounded w-1/2"></div>
            <div class="h-6 bg-gray-100 rounded w-1/4"></div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 animate-pulse">
        <div class="w-12 h-12 bg-gray-100 rounded-2xl"></div>
        <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-100 rounded w-1/2"></div>
            <div class="h-6 bg-gray-100 rounded w-1/4"></div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 animate-pulse">
        <div class="w-12 h-12 bg-gray-100 rounded-2xl"></div>
        <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-100 rounded w-1/2"></div>
            <div class="h-6 bg-gray-100 rounded w-1/4"></div>
        </div>
    </div>
</div>

<script>
async function loadDashboardStats() {
    try {
        const response = await fetch('api/dashboard_stats.php');
        const result = await response.json();
        
        if (result.status === 'success') {
            const data = result.data;
            const container = document.getElementById('statsContainer');
            
            container.innerHTML = `
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 hover:shadow-md transition group">
                    <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl group-hover:scale-110 transition"><i class="fas fa-dumbbell text-xl"></i></div>
                    <div><p class="text-gray-500 text-sm font-medium">Total Workouts</p><h3 class="text-2xl font-bold">${data.total_workouts}</h3></div>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 hover:shadow-md transition group">
                    <div class="p-4 bg-green-50 text-green-600 rounded-2xl group-hover:scale-110 transition"><i class="fas fa-fire text-xl"></i></div>
                    <div><p class="text-gray-500 text-sm font-medium">Current Streak</p><h3 class="text-2xl font-bold">${data.streak} Days</h3></div>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 hover:shadow-md transition group">
                    <div class="p-4 bg-purple-50 text-purple-600 rounded-2xl group-hover:scale-110 transition"><i class="fas fa-weight text-xl"></i></div>
                    <div><p class="text-gray-500 text-sm font-medium">Current Weight</p><h3 class="text-2xl font-bold">${data.current_weight} kg</h3></div>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 hover:shadow-md transition group">
                    <div class="p-4 bg-orange-50 text-orange-600 rounded-2xl group-hover:scale-110 transition"><i class="fas fa-calendar-check text-xl"></i></div>
                    <div><p class="text-gray-500 text-sm font-medium">Week Avg</p><h3 class="text-2xl font-bold">${data.weekly_attendance} Days</h3></div>
                </div>
            `;
        }
    } catch (err) {
        console.error('Failed to load stats:', err);
    }
}

document.addEventListener('DOMContentLoaded', loadDashboardStats);
</script>
