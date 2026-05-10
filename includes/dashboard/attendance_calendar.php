<?php
// includes/dashboard/attendance_calendar.php
?>
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-6 border-b flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-gray-800">Attendance Calendar</h3>
            <p class="text-gray-600">Track your gym visits</p>
        </div>
        <a href="attendance.php" class="text-highlight hover:underline font-medium text-sm">
            View all
        </a>
    </div>

    <div class="p-6">
        <!-- Calendar Header -->
        <div class="grid grid-cols-7 gap-1 mb-4">
            <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                <div class="text-center font-medium text-gray-500"><?= $d ?></div>
            <?php endforeach; ?>
        </div>
        
        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-1">
            <?php 
            $current_month = date('m');
            $current_year = date('Y');
            $first_day = date('w', strtotime("$current_year-$current_month-01"));
            $days_in_month = date('t', strtotime("$current_year-$current_month-01"));
            
            // Get attendance for current month
            $stmt = $pdo->prepare("SELECT date, attended FROM attendance WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
            $stmt->execute([$user_id, $current_year, $current_month]);
            $attendance_map = [];
            while ($row = $stmt->fetch()) {
                $attendance_map[date('j', strtotime($row['date']))] = $row['attended'];
            }
            
            // Empty cells before month
            for ($i = 0; $i < $first_day; $i++) echo '<div class="h-12"></div>';
            
            // Days
            for ($day = 1; $day <= $days_in_month; $day++) {
                $status = $attendance_map[$day] ?? null;
                $classes = "h-12 flex flex-col items-center justify-center relative rounded-lg transition-colors";
                $dot_class = "";
                
                if ($status !== null) {
                    $classes .= $status ? " bg-green-50" : " bg-red-50";
                    $dot_class = $status ? "bg-green-500" : "bg-red-500";
                }
                
                echo "<div class=\"$classes\">";
                echo "<span class=\"text-gray-700 text-sm\">$day</span>";
                if ($dot_class) echo "<span class=\"w-1.5 h-1.5 rounded-full $dot_class mt-1\"></span>";
                echo "</div>";
            }
            
            // Fill grid
            $total = 35; // or 42
            $filled = $first_day + $days_in_month;
            if ($filled > 35) $total = 42;
            for ($i = 0; $i < ($total - $filled); $i++) echo '<div class="h-12"></div>';
            ?>
        </div>
    </div>
</div>
