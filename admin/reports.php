<?php
// admin/reports.php - API-First Analytics Version
require_once '../auth.php';
require_admin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Analytics Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', secondary: '#16213e', accent: '#0f3460', highlight: '#e94560' } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fa; }
        .chart-container { background: white; border-radius: 2rem; border: 1px solid #f1f5f9; padding: 2rem; }
    </style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 md:ml-64 w-full flex flex-col">
        <main class="container mx-auto px-8 py-10 flex-grow">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-12">
                <div>
                    <h1 class="text-4xl font-black text-gray-800 tracking-tighter">Business Intelligence</h1>
                    <p class="text-gray-500 font-medium">Real-time gym performance and growth metrics.</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="fetchReports()" class="bg-primary text-white px-6 py-3 rounded-2xl font-bold shadow-lg hover:opacity-90 transition">
                        <i class="fas fa-sync-alt mr-2"></i> Refresh Data
                    </button>
                    <button onclick="window.print()" class="bg-white border text-gray-700 px-6 py-3 rounded-2xl font-bold shadow-sm hover:bg-gray-50 transition">
                        <i class="fas fa-file-export mr-2"></i> Export
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Monthly Revenue</p>
                    <h2 id="monthlyRevenue" class="text-4xl font-black text-gray-800">$0.00</h2>
                    <div class="mt-4 flex items-center text-green-500 text-xs font-bold">
                        <i class="fas fa-arrow-up mr-1"></i> <span>Live Data</span>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Active Members</p>
                    <h2 id="totalMembers" class="text-4xl font-black text-gray-800">0</h2>
                    <div id="newMembersText" class="mt-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">+0 New this month</div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Retention Rate</p>
                    <h2 id="activeRate" class="text-4xl font-black text-highlight">0%</h2>
                    <div class="mt-4 w-full bg-gray-100 rounded-full h-1.5">
                        <div id="activeRateBar" class="bg-highlight h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="chart-container">
                    <h3 class="text-xl font-black text-gray-800 mb-6">Workout Trends (Last 7 Days)</h3>
                    <canvas id="trendsChart" height="300"></canvas>
                </div>
                <div class="chart-container">
                    <h3 class="text-xl font-black text-gray-800 mb-6">Popular Sessions</h3>
                    <canvas id="workoutDistChart" height="300"></canvas>
                </div>
            </div>
        </main>

        <footer class="bg-white border-t border-gray-100 py-10">
            <div class="text-center text-gray-400 text-xs font-bold uppercase tracking-widest">
                Warzone Analytics Engine v2.0 • Data Secured & Encrypted
            </div>
        </footer>
    </div>

    <script>
        let trendsChart, distChart;

        async function fetchReports() {
            try {
                const response = await fetch('../api/admin/reports.php');
                const res = await response.json();
                
                if (res.status === 'success') {
                    updateKPIs(res.data);
                    renderTrends(res.data.trends.workouts);
                    renderDistribution(res.data.trends.top_types);
                }
            } catch (err) { console.error('Reports error:', err); }
        }

        function updateKPIs(data) {
            document.getElementById('monthlyRevenue').innerText = `$${data.revenue.monthly.toLocaleString()}`;
            document.getElementById('totalMembers').innerText = data.members.total;
            document.getElementById('newMembersText').innerText = `+${data.members.new_30d} New this month`;
            document.getElementById('activeRate').innerText = `${data.members.active_rate}%`;
            document.getElementById('activeRateBar').style.width = `${data.members.active_rate}%`;
        }

        function renderTrends(data) {
            const ctx = document.getElementById('trendsChart').getContext('2d');
            if (trendsChart) trendsChart.destroy();
            
            trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => new Date(d.date).toLocaleDateString('en-US', { weekday: 'short' })),
                    datasets: [{
                        label: 'Total Workouts',
                        data: data.map(d => d.count),
                        borderColor: '#e94560',
                        backgroundColor: 'rgba(233, 69, 96, 0.05)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { display: false }, ticks: { font: { weight: 'bold' } } },
                        x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
                    }
                }
            });
        }

        function renderDistribution(data) {
            const ctx = document.getElementById('workoutDistChart').getContext('2d');
            if (distChart) distChart.destroy();
            
            distChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.type),
                    datasets: [{
                        data: data.map(d => d.count),
                        backgroundColor: ['#e94560', '#1a1a2e', '#06d6a0', '#0f3460', '#fbbf24'],
                        borderWidth: 0,
                        hoverOffset: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, font: { weight: 'bold', size: 10 } } }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', fetchReports);
    </script>
</body>
</html>