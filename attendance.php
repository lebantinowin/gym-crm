<?php
// attendance.php - Attendance with Double-Click Remove
require_once 'auth.php';

require_login();

$user_id = $_SESSION['user_id'];

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark attendance
    if (isset($_POST['mark_attendance'])) {
        $date = $_POST['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) die("Invalid date.");
        $attended = isset($_POST['attended']) ? (int)$_POST['attended'] : 0;

        $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, attended) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE attended = ?");
        $stmt->execute([$user_id, $date, $attended, $attended]);

        $_SESSION['attendance_success'] = match($attended) {
            1 => "✅ Training logged!",
            0 => "🚫 Absent recorded.",
            -1 => "😴 Rest day honored."
        };
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
    
    // Remove attendance
    if (isset($_POST['remove_attendance'])) {
        $date = $_POST['date'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) die("Invalid date.");

        $stmt = $pdo->prepare("DELETE FROM attendance WHERE user_id = ? AND date = ?");
        $stmt->execute([$user_id, $date]);

        $_SESSION['attendance_success'] = "🗑️ Attendance removed for " . date('M j', strtotime($date));
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Get selected month/year
$selected_year = $_GET['year'] ?? date('Y');
$selected_month = $_GET['month'] ?? date('m');
$selected_year = (int)$selected_year;
$selected_month = (int)$selected_month;
if ($selected_month < 1 || $selected_month > 12) $selected_month = date('m');

// Fetch attendance
$stmt = $pdo->prepare("SELECT date, attended FROM attendance WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ? ORDER BY date");
$stmt->execute([$user_id, $selected_year, $selected_month]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$attendance_rate = calculate_attendance_rate($user_id);

function monthName($month_num) {
    $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
               7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
    return $months[$month_num] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .calendar-header { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: 600; margin-bottom: 1rem; }
        .calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        .calendar-day {
            height: 50px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center;
            border-radius: 0.5rem; 
            border: 1px solid #e2e8f0; 
            cursor: pointer; 
            transition: all 0.2s;
            position: relative;
        }
        .calendar-day:hover:not(.empty) {
            background-color: #f1f5f9; 
            transform: translateY(-2px);
        }
        .calendar-day.present { background-color: #f0fdf4; border: 2px solid #22c55e; }
        .calendar-day.rest { background-color: #f0f9ff; border: 2px solid #3b82f6; }
        .calendar-day.absent { background-color: #fef2f2; border: 2px solid #ef4444; }
        .calendar-day.today { background-color: #dbeafe; border: 2px solid #3b82f6; }
        .attendance-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 4px; }
        .present-dot { background-color: #22c55e; }
        .rest-dot { background-color: #3b82f6; }
        .absent-dot { background-color: #ef4444; }
        
        /* Tooltip for removable days */
        .tooltip {
            position: absolute;
            bottom: -28px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 10;
        }
        .calendar-day[data-has-attendance="true"]:hover .tooltip {
            opacity: 1;
        }
        
        .btn-primary { background: linear-gradient(45deg, #e94560, #0f3460); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(233, 69, 96, 0.3); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; border-radius: 1rem; padding: 1.5rem; max-width: 400px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal h3 { font-size: 1.3rem; margin-bottom: 1rem; color: #1e293b; }
        .modal-buttons { display: flex; gap: 0.75rem; flex-wrap: wrap; justify-content: center; margin-top: 1.5rem; }
        .btn-yes { background: linear-gradient(45deg, #06d6a0, #10b981); color: white; padding: 0.5rem 1.5rem; border-radius: 0.5rem; font-weight: 600; }
        .btn-no { background: #64748b; color: white; padding: 0.5rem 1.5rem; border-radius: 0.5rem; font-weight: 600; }
        .btn-present { background: linear-gradient(45deg, #22c55e, #16a34a); color: white; }
        .btn-rest { background: linear-gradient(45deg, #3b82f6, #2563eb); color: white; }
        .btn-absent { background: linear-gradient(45deg, #ef4444, #dc2626); color: white; }
        .nav-btn { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: #4b5563; }
        .nav-btn:hover { background: #e2e8f0; transform: scale(1.05); }
        .empty { cursor: default; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-primary text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-dumbbell text-highlight text-2xl"></i>
                <a href="dashboard.php" class="text-xl font-bold text-white hover:text-highlight transition">
                    Warzone Gym CRM
                </a>
            </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="dashboard.php" class="hover:text-highlight transition font-semibold">Dashboard</a>
                    <a href="workouts.php" class="hover:text-highlight transition">Workouts</a>
                    <a href="attendance.php" class="hover:text-highlight transition">Attendance</a>
                    <a href="journal.php" class="hover:text-highlight transition">Journal</a>
                    <a href="chat.php" class="hover:text-highlight transition">Chat</a>
                    <a href="profile.php" class="hover:text-highlight transition">Profile</a>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <!-- <div class="relative">
                        <button id="notificationBtn" class="text-xl relative">
                            <i class="fas fa-bell"></i>
                            <?php if (!empty($notifications)): ?>
                                <span class="notification-badge"><?= count($notifications) ?></span>
                            <?php endif; ?>
                        </button>
                    </div> -->

                    <!-- ✅ Profile Picture (CLICKABLE → profile.php) -->
                    <a href="profile.php" class="flex items-center space-x-2 group" title="View Profile">
                        <img src="<?= htmlspecialchars(file_exists('uploads/' . ($_SESSION['user_profile_picture'] ?? 'default.png')) 
                                    ? 'uploads/' . $_SESSION['user_profile_picture'] 
                                    : 'uploads/default.png') ?>" 
                            alt="Profile" 
                            class="rounded-full w-10 h-10 transition-transform duration-200 group-hover:scale-105 group-hover:ring-2 group-hover:ring-highlight">
                        <!-- <span class="hidden md:inline font-medium group-hover:text-highlight"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span> -->
                        <!-- Logout icon stays next to name -->
                        <a href="logout.php" 
                        class="text-gray-400 hover:text-highlight transition ml-1 opacity-75 group-hover:opacity-100" 
                        title="Logout">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </a>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Attendance Tracker</h2>
            <div class="text-right">
                <p class="text-lg font-bold text-highlight"><?= $attendance_rate ?>%</p>
                <p class="text-gray-600">Attendance Rate</p>
            </div>
        </div>
        
        <?php if (isset($_SESSION['attendance_success'])): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                <?= $_SESSION['attendance_success'] ?>
            </div>
            <?php unset($_SESSION['attendance_success']); ?>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow p-6 max-w-4xl mx-auto">
            <!-- Month Navigation -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center space-x-4">
                    <a href="?year=<?= $selected_month == 1 ? $selected_year - 1 : $selected_year ?>&month=<?= $selected_month == 1 ? 12 : $selected_month - 1 ?>" class="nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <select id="monthSelect" class="border rounded-lg px-3 py-1 focus:ring-2 focus:ring-highlight">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>><?= monthName($m) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="yearSelect" class="border rounded-lg px-3 py-1 focus:ring-2 focus:ring-highlight">
                        <?php for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--): ?>
                            <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <a href="?year=<?= $selected_month == 12 ? $selected_year + 1 : $selected_year ?>&month=<?= $selected_month == 12 ? 1 : $selected_month + 1 ?>" class="nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <!-- Calendar -->
            <div class="calendar-header text-gray-500">
                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
            </div>
            
            <div class="calendar-days">
                <?php 
                $first_day = date('w', mktime(0,0,0,$selected_month,1,$selected_year));
                $days_in_month = date('t', mktime(0,0,0,$selected_month,1,$selected_year));
                $today = date('Y-m-d');
                
                // Empty cells before month
                for ($i = 0; $i < $first_day; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                
                // Days with attendance
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date_str = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $day);
                    $is_today = ($date_str === $today);
                    $status = $attendance_records[$date_str] ?? null;
                    
                    $classes = "calendar-day";
                    $has_attendance = $status !== null;
                    if ($has_attendance) $classes .= " removable";
                    if ($is_today) $classes .= " today";
                    if ($has_attendance) {
                        $classes .= match((int)$status) {
                            1 => " present",
                            -1 => " rest",
                            0 => " absent",
                            default => ""
                        };
                    }
                    
                    echo '<div class="' . $classes . '" 
                             data-date="' . $date_str . '" 
                             data-has-attendance="' . ($has_attendance ? 'true' : 'false') . '">';
                    echo '<span class="font-medium">' . $day . '</span>';
                    if ($has_attendance) {
                        $dot_class = match((int)$status) {
                            1 => 'present-dot',
                            -1 => 'rest-dot',
                            0 => 'absent-dot',
                            default => ''
                        };
                        echo '<span class="attendance-dot ' . $dot_class . '"></span>';
                        echo '<span class="tooltip">Double-click to remove</span>';
                    }
                    echo '</div>';
                }
                
                // Empty cells after month
                $total_cells = 42;
                $filled_cells = $first_day + $days_in_month;
                for ($i = 0; $i < ($total_cells - $filled_cells); $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                ?>
            </div>
        </div>
        
        <div class="mt-8 bg-white rounded-xl shadow p-6 max-w-4xl mx-auto">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Attendance Legend</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
                    <span>✅ Present (trained)</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-blue-500 rounded-full mr-3"></div>
                    <span>😴 Rest (planned)</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-500 rounded-full mr-3"></div>
                    <span>❌ Absent (missed)</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-blue-300 rounded-full mr-3"></div>
                    <span>📅 Today</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Action Modal -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <i class="fas fa-calendar-check text-blue-500 text-3xl mb-3"></i>
            <h3 id="modalDate">Mark for <span class="font-bold">Jan 1</span></h3>
            <p class="text-gray-600 mb-2">What happened on this day?</p>
            <div class="modal-buttons">
                <button id="btnPresent" class="btn-present px-4 py-2">✅ Present</button>
                <button id="btnRest" class="btn-rest px-4 py-2">😴 Rest</button>
                <button id="btnAbsent" class="btn-absent px-4 py-2">❌ Absent</button>
            </div>
        </div>
    </div>

    <!-- Honest Modals -->
    <div id="honestPresentModal" class="modal">
        <div class="modal-content">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
            <h3 class="font-bold">Honest?</h3>
            <p class="text-gray-600 mb-2">Warzone values integrity over perfect attendance.</p>
            <p class="text-gray-600">Are you <strong>100% sure</strong> you trained this day?</p>
            <div class="modal-buttons">
                <button id="confirmPresentYes" class="btn-yes">Yes, I trained</button>
                <button id="confirmPresentNo" class="btn-no">No, I didn’t</button>
            </div>
        </div>
    </div>

    <div id="honestRestModal" class="modal">
        <div class="modal-content">
            <i class="fas fa-bed text-blue-500 text-4xl mb-4"></i>
            <h3 class="font-bold">Planned Rest?</h3>
            <p class="text-gray-600 mb-2">Rest is part of training — but only if intentional.</p>
            <p class="text-gray-600">Was this a <strong>planned recovery day</strong>?</p>
            <div class="modal-buttons">
                <button id="confirmRestYes" class="btn-yes">Yes, planned</button>
                <button id="confirmRestNo" class="btn-no">No, I skipped</button>
            </div>
        </div>
    </div>

    <footer class="bg-primary text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            <p>© 2026 Warzone Gym CRM. All rights reserved.</p>
        </div>
    </footer>

    <script>
        let selectedDate = null;

        // Navigation
        document.getElementById('monthSelect').addEventListener('change', updateUrl);
        document.getElementById('yearSelect').addEventListener('change', updateUrl);
        function updateUrl() {
            const m = document.getElementById('monthSelect').value;
            const y = document.getElementById('yearSelect').value;
            window.location.href = `?month=${m}&year=${y}`;
        }

        // Double-click to remove attendance
        document.querySelectorAll('.calendar-day.removable').forEach(day => {
            day.addEventListener('dblclick', function(e) {
                e.stopPropagation();
                const date = this.dataset.date;
                
                // Show confirmation modal (optional)
                if (confirm(`Remove attendance for ${new Date(date).toLocaleDateString()}?`)) {
                    removeAttendance(date);
                }
            });
        });

        // Single-click to add/edit
        document.querySelectorAll('.calendar-day[data-date]').forEach(day => {
            if (!day.classList.contains('empty')) {
                day.addEventListener('click', function(e) {
                    // Prevent double-click conflict
                    if (e.detail === 1) {
                        setTimeout(() => {
                            if (e.detail === 1) {
                                const date = this.dataset.date;
                                selectedDate = date;
                                const dateObj = new Date(date);
                                const formatted = dateObj.toLocaleDateString('en-US', { 
                                    month: 'short', 
                                    day: 'numeric', 
                                    year: 'numeric' 
                                });
                                document.getElementById('modalDate').innerHTML = `Mark for <span class="font-bold">${formatted}</span>`;
                                document.getElementById('actionModal').style.display = 'flex';
                            }
                        }, 200);
                    }
                });
            }
        });

        // Action buttons
        document.getElementById('btnPresent').addEventListener('click', () => {
            document.getElementById('actionModal').style.display = 'none';
            document.getElementById('honestPresentModal').style.display = 'flex';
        });
        document.getElementById('btnRest').addEventListener('click', () => {
            document.getElementById('actionModal').style.display = 'none';
            document.getElementById('honestRestModal').style.display = 'flex';
        });
        document.getElementById('btnAbsent').addEventListener('click', () => {
            submitAttendance(0);
        });

        // Honest modals
        document.getElementById('confirmPresentYes').addEventListener('click', () => {
            document.getElementById('honestPresentModal').style.display = 'none';
            submitAttendance(1);
        });
        document.getElementById('confirmRestYes').addEventListener('click', () => {
            document.getElementById('honestRestModal').style.display = 'none';
            submitAttendance(-1);
        });

        // Cancel
        document.getElementById('confirmPresentNo').addEventListener('click', cancelHonest);
        document.getElementById('confirmRestNo').addEventListener('click', cancelHonest);
        function cancelHonest() {
            document.getElementById('honestPresentModal').style.display = 'none';
            document.getElementById('honestRestModal').style.display = 'none';
            document.getElementById('actionModal').style.display = 'flex';
        }

        // Close modals
        [document.getElementById('actionModal'), 
         document.getElementById('honestPresentModal'), 
         document.getElementById('honestRestModal')].forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
        });

        // Submit functions
        function submitAttendance(attended) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            ['date', 'attended', 'mark_attendance'].forEach((name, i) => {
                const val = [selectedDate, attended, '1'][i];
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = val;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }

        function removeAttendance(date) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            const inputs = [
                { name: 'date', value: date },
                { name: 'remove_attendance', value: '1' }
            ];
            inputs.forEach(({name, value}) => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <?php include 'modal_logout.php'; ?>
</body>
</html>