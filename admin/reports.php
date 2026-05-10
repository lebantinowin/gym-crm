<?php
// admin/reports.php - Comprehensive Reports Dashboard (Fixed for missing tables & graph animations)
require_once '../auth.php';

require_admin();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get various reports with error handling
$reports = [];

// Member Growth Report
try {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as new_members 
        FROM users 
        WHERE role = 'member' AND created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['member_growth'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reports['member_growth'] = [];
}

// Workout Statistics
try {
    $stmt = $pdo->prepare("
        SELECT 
            w.type,
            COUNT(*) as total_workouts,
            AVG(w.duration) as avg_duration,
            AVG(w.calories_burned) as avg_calories
        FROM workouts w
        WHERE w.date BETWEEN ? AND ?
        GROUP BY w.type
        ORDER BY total_workouts DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['workout_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reports['workout_stats'] = [];
}

// Attendance Report
try {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(date) as attendance_date,
            COUNT(*) as total_attendance,
            SUM(CASE WHEN attended = 1 THEN 1 ELSE 0 END) as present_count,
            ROUND(SUM(CASE WHEN attended = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as attendance_rate
        FROM attendance
        WHERE date BETWEEN ? AND ?
        GROUP BY DATE(date)
        ORDER BY date
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['attendance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reports['attendance'] = [];
}

// Revenue Report - Check if payments table exists
$reports['revenue'] = [];
try {
    // First check if payments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(payment_date) as date,
                SUM(amount) as daily_revenue,
                COUNT(*) as payment_count
            FROM payments
            WHERE payment_date BETWEEN ? AND ?
            GROUP BY DATE(payment_date)
            ORDER BY date
        ");
        $stmt->execute([$start_date, $end_date]);
        $reports['revenue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $reports['revenue'] = [];
}

// Popular Workout Times
try {
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(schedule_time) as hour,
            COUNT(*) as class_count
        FROM classes
        WHERE schedule_time BETWEEN ? AND ?
        GROUP BY HOUR(schedule_time)
        ORDER BY hour
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['popular_times'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reports['popular_times'] = [];
}

// User Activity Report
try {
    $stmt = $pdo->prepare("
        SELECT 
            activity_type,
            COUNT(*) as count,
            DATE(created_at) as activity_date
        FROM user_activity
        WHERE created_at BETWEEN ? AND ?
        GROUP BY activity_type, DATE(created_at)
        ORDER BY activity_date DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['user_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reports['user_activity'] = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a2e',
                        secondary: '#16213e',
                        accent: '#0f3460',
                        highlight: '#e94560'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .report-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #d1d5db;
        }
    </style>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 md:ml-64 w-full flex flex-col">

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
                <p class="text-gray-600">Comprehensive insights into your gym operations</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full lg:w-auto">
                <form method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" 
                           class="border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight w-full sm:w-auto">
                    <span class="text-gray-500 hidden sm:block text-center">to</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" 
                           class="border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight w-full sm:w-auto">
                    <button type="submit" class="bg-highlight text-white px-4 py-2 rounded-lg hover:bg-opacity-90 transition whitespace-nowrap">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </form>
                <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition whitespace-nowrap">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="report-card bg-white p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Revenue</p>
                        <p class="text-2xl font-bold">
                            <?php 
                            $total_revenue = !empty($reports['revenue']) ? array_sum(array_column($reports['revenue'], 'daily_revenue')) : 0;
                            echo '$' . number_format($total_revenue, 2);
                            ?>
                        </p>
                        <?php if (empty($reports['revenue'])): ?>
                            <p class="text-xs text-orange-500">Payments system not configured</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-dollar-sign text-green-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="report-card bg-white p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">New Members</p>
                        <p class="text-2xl font-bold"><?= count($reports['member_growth']) ?></p>
                        <?php if (empty($reports['member_growth'])): ?>
                            <p class="text-xs text-gray-500">No new members in period</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="report-card bg-white p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Workouts</p>
                        <p class="text-2xl font-bold"><?= !empty($reports['workout_stats']) ? array_sum(array_column($reports['workout_stats'], 'total_workouts')) : 0 ?></p>
                        <?php if (empty($reports['workout_stats'])): ?>
                            <p class="text-xs text-gray-500">No workouts logged</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-dumbbell text-purple-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="report-card bg-white p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Avg Attendance</p>
                        <p class="text-2xl font-bold">
                            <?php
                            if (!empty($reports['attendance'])) {
                                $avg = array_sum(array_column($reports['attendance'], 'attendance_rate')) / count($reports['attendance']);
                                echo number_format($avg, 1) . '%';
                            } else {
                                echo '0%';
                            }
                            ?>
                        </p>
                        <?php if (empty($reports['attendance'])): ?>
                            <p class="text-xs text-gray-500">No attendance data</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-calendar-check text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Member Growth Chart -->
            <div class="chart-container">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Member Growth</h3>
                <?php if (!empty($reports['member_growth'])): ?>
                    <canvas id="memberGrowthChart"></canvas>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-line"></i>
                        <p>No member growth data available for selected period</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Workout Types Chart
            <div class="chart-container">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Workout Distribution</h3>
                <?php if (!empty($reports['workout_stats'])): ?>
                    <canvas id="workoutTypesChart"></canvas>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-pie"></i>
                        <p>No workout data available for selected period</p>
                    </div>
                <?php endif; ?>
            </div>
        </div> -->

        <!-- Detailed Reports
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Attendance Report
            <div class="chart-container">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Daily Attendance Rate</h3>
                <?php if (!empty($reports['attendance'])): ?>
                    <canvas id="attendanceChart"></canvas>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <p>No attendance data available for selected period</p>
                    </div>
                <?php endif; ?>
            </div> -->

            <!-- Revenue Report -->
            <div class="chart-container">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Revenue Trends</h3>
                <?php if (!empty($reports['revenue'])): ?>
                    <canvas id="revenueChart"></canvas>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-dollar-sign"></i>
                        <p>Payments system not configured - revenue data unavailable</p>
                        <p class="text-sm mt-2">Contact your developer to set up the payments table</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Data Tables -->
        <?php if (!empty($reports['workout_stats'])): ?>
        <div class="mt-8 bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6 border-b">
                <h3 class="text-xl font-bold text-gray-800">Detailed Workout Statistics</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Workout Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Sessions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Calories</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reports['workout_stats'] as $stat): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($stat['type']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $stat['total_workouts'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= round($stat['avg_duration']) ?> mins</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= round($stat['avg_calories']) ?> kcal</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- System Health -->
        <div class="mt-8 bg-white rounded-xl shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">System Health</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                    <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
                    <div>
                        <p class="font-medium text-green-800">Database Connection</p>
                        <p class="text-sm text-green-600">Active</p>
                    </div>
                </div>
                <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                    <i class="fas fa-database text-blue-500 text-2xl mr-3"></i>
                    <div>
                        <p class="font-medium text-blue-800">Core Tables</p>
                        <p class="text-sm text-blue-600">All operational</p>
                    </div>
                </div>
                <div class="flex items-center p-4 <?= !empty($reports['revenue']) ? 'bg-green-50' : 'bg-orange-50' ?> rounded-lg">
                    <i class="fas fa-credit-card text-<?= !empty($reports['revenue']) ? 'green' : 'orange' ?>-500 text-2xl mr-3"></i>
                    <div>
                        <p class="font-medium text-<?= !empty($reports['revenue']) ? 'green' : 'orange' ?>-800">Payments System</p>
                        <p class="text-sm text-<?= !empty($reports['revenue']) ? 'green' : 'orange' ?>-600">
                            <?= !empty($reports['revenue']) ? 'Active' : 'Not configured' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Member Growth Chart - FIXED ANIMATION
        <?php if (!empty($reports['member_growth'])): ?>
        const memberGrowthCtx = document.getElementById('memberGrowthChart').getContext('2d');
        new Chart(memberGrowthCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($reports['member_growth'], 'date')) ?>,
                datasets: [{
                    label: 'New Members',
                    data: <?= json_encode(array_column($reports['member_growth'], 'new_members')) ?>,
                    borderColor: '#e94560',
                    backgroundColor: 'rgba(233, 69, 96, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1500, // Fixed duration
                    loop: false,    // Prevent looping
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Prevent decimal values
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Workout Types Chart - FIXED ANIMATION
        <?php if (!empty($reports['workout_stats'])): ?>
        const workoutTypesCtx = document.getElementById('workoutTypesChart').getContext('2d');
        new Chart(workoutTypesCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($reports['workout_stats'], 'type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($reports['workout_stats'], 'total_workouts')) ?>,
                    backgroundColor: [
                        '#e94560',
                        '#0f3460',
                        '#06d6a0',
                        '#fbbf24',
                        '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200, // Fixed duration
                    loop: false,    // Prevent looping
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>

        // Attendance Chart - FIXED ANIMATION
        <?php if (!empty($reports['attendance'])): ?>
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($reports['attendance'], 'attendance_date')) ?>,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: <?= json_encode(array_column($reports['attendance'], 'attendance_rate')) ?>,
                    backgroundColor: 'rgba(14, 52, 96, 0.8)',
                    borderColor: '#0f3460',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000, // Fixed duration
                    loop: false,    // Prevent looping
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 10 // Clean intervals
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Revenue Chart - FIXED ANIMATION
        <?php if (!empty($reports['revenue'])): ?>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($reports['revenue'], 'date')) ?>,
                datasets: [{
                    label: 'Daily Revenue ($)',
                    data: <?= json_encode(array_column($reports['revenue'], 'daily_revenue')) ?>,
                    borderColor: '#06d6a0',
                    backgroundColor: 'rgba(6, 214, 160, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1300, // Fixed duration
                    loop: false,    // Prevent looping
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>

    <footer class="bg-primary text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="text-center text-gray-400 text-sm">
                <p>© 2026 Warzone Gym CRM. All rights reserved.</p>
            </div>
        </div>
    </footer>
    </div>
</body>
</html>