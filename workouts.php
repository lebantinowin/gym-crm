<?php
// workouts.php - Complete Fixed Version with Emoji Support
require_once 'auth.php';

require_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_email = $_SESSION['user_email'];
$user_profile_picture = $_SESSION['user_profile_picture'] ?? 'default.png';

// Get all workouts with pagination
$items_per_page = 10;
$current_page = max(1, $_GET['page'] ?? 1);

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_items = (int)$stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// Get paginated results
$offset = ($current_page - 1) * $items_per_page;
$limit = (int)$items_per_page;
$offset = (int)$offset;

$stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete workout (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && isset($_POST['delete_workout'])) {
    header('Content-Type: application/json');
    
    try {
        $workout_id = (int)$_POST['workout_id'];
        
        // Verify workout belongs to user
        $stmt = $pdo->prepare("SELECT id FROM workouts WHERE id = ? AND user_id = ?");
        $stmt->execute([$workout_id, $user_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '⚠️ Workout not found or unauthorized']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM workouts WHERE id = ?");
        $stmt->execute([$workout_id]);
        
        echo json_encode(['success' => true, 'message' => '🗑️ Workout deleted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '⚠️ ' . $e->getMessage()]);
    }
    exit();
}

// Handle log workout (AJAX) - FIXED EMOJI HANDLING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && isset($_POST['log_workout'])) {
    header('Content-Type: application/json');
    
    try {
        $type = filter_var($_POST['workout_type'], FILTER_SANITIZE_STRING);
        $duration = intval($_POST['duration']);
        $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : 0;
        $notes = !empty($_POST['workout_notes']) ? filter_var($_POST['workout_notes'], FILTER_SANITIZE_STRING) : '';
        
        // FIXED: Get mood from POST data and validate it
        $mood = $_POST['workout_mood'] ?? '😊';
        
        // Validate mood is one of the allowed emojis
        $allowed_moods = ['😠', '😐', '😊', '😁'];
        if (!in_array($mood, $allowed_moods)) {
            $mood = '😊'; // Default to happy if invalid
        }
        
        // Create exercises array (mock data for demo)
        $exercises = [];
        if ($type === 'Upper Body') {
            $exercises = ['Bench Press', 'Pull-ups', 'Shoulder Press', 'Bicep Curls'];
        } elseif ($type === 'Lower Body') {
            $exercises = ['Squats', 'Deadlifts', 'Lunges', 'Calf Raises'];
        } elseif ($type === 'Cardio') {
            $exercises = ['Running', 'Cycling', 'Jump Rope', 'Burpees'];
        } else {
            $exercises = ['Push-ups', 'Sit-ups', 'Planks', 'Burpees'];
        }
        
        // Ensure proper charset for emoji storage
        $pdo->exec("SET NAMES utf8mb4");
        
        $stmt = $pdo->prepare("INSERT INTO workouts (user_id, date, type, duration, calories_burned, notes, mood, exercises) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $duration, $calories, $notes, $mood, json_encode($exercises)]);
        
        // Also log attendance
        $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, attended) VALUES (?, CURDATE(), 1) ON DUPLICATE KEY UPDATE attended = 1");
        $stmt->execute([$user_id]);
        
        echo json_encode(['success' => true, 'message' => '✅ Workout logged successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '⚠️ ' . $e->getMessage()]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Workout History</title>
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
                        highlight: '#e94560',
                        success: '#06d6a0'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .btn-primary {
            background: linear-gradient(45deg, #e94560, #0f3460);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .workout-card {
            transition: all 0.3s ease;
            border-radius: 0.5rem;
        }
        .workout-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .modal { 
            display: none; position: fixed; top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
            justify-content: center; align-items: center; 
        }
        .modal-content { 
            background: white; border-radius: 1rem; 
            width: 100%; max-width: 600px; 
            padding: 1.5rem;
        }
        .mood-btn { 
            width: 48px; height: 48px; font-size: 1.5rem; 
            border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            background: #f1f5f9; cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .mood-btn:hover { 
            transform: scale(1.1); 
            background: #e2e8f0;
        }
        .mood-btn.active { 
            background: linear-gradient(45deg, #e94560, #0f3460); 
            color: white;
            border-color: #e94560;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">

    <!-- Navigation Bar -->
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
                    <a href="dashboard.php" class="hover:text-highlight transition">Dashboard</a>
                    <a href="workouts.php" class="hover:text-highlight transition font-semibold">Workouts</a>
                    <a href="attendance.php" class="hover:text-highlight transition">Attendance</a>
                    <a href="journal.php" class="hover:text-highlight transition">Journal</a>
                    <a href="chat.php" class="hover:text-highlight transition">Chat</a>
                    <a href="profile.php" class="hover:text-highlight transition">Profile</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="flex items-center space-x-2 group" title="Profile">
                        <img src="<?= htmlspecialchars(file_exists('uploads/' . $user_profile_picture) ? 'uploads/' . $user_profile_picture : 'uploads/default.png') ?>" 
                             alt="Profile" 
                             class="rounded-full w-10 h-10 transition-transform duration-200 group-hover:scale-105 group-hover:ring-2 group-hover:ring-highlight">
                        <a href="logout.php" 
                           class="text-gray-400 hover:text-highlight transition ml-1 opacity-75 group-hover:opacity-100" 
                           title="Logout">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </a>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Workout History</h1>
                <p class="text-gray-600">All your training sessions in one place</p>
            </div>
            <button id="logWorkoutBtn" class="btn-primary px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Log Workout
            </button>
        </div>

        <?php if (empty($workouts)): ?>
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-dumbbell text-5xl text-gray-300"></i>
                <h3 class="mt-4 text-xl font-bold text-gray-800">No workouts logged yet</h3>
                <p class="text-gray-600 mt-2">Start logging your workouts to track your progress</p>
                <button id="logFirstWorkoutBtn" class="mt-4 btn-primary px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i> Log First Workout
                </button>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calories</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mood</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($workouts as $workout): 
                                // Debug: Check what mood value we actually have
                                error_log("Workout ID: " . $workout['id'] . " Mood: " . $workout['mood']);
                            ?>
                            <tr class="workout-card hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('M j, Y', strtotime($workout['date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                            <i class="fas fa-running text-green-500"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($workout['type']) ?></div>
                                            <?php if (!empty($workout['notes'])): ?>
                                                <div class="text-gray-500 text-xs"><?= htmlspecialchars(substr($workout['notes'], 0, 30)) ?><?= strlen($workout['notes']) > 30 ? '...' : '' ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $workout['duration'] ?> mins
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $workout['calories_burned'] ?> kcal
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php
                                // FIXED: Handle emoji moods properly
                                $mood = $workout['mood'] ?? '😊';
                                $moodStyles = [
                                    '😠' => 'bg-red-100 text-red-800',
                                    '😐' => 'bg-yellow-100 text-yellow-800',
                                    '😊' => 'bg-green-100 text-green-800',
                                    '😁' => 'bg-blue-100 text-blue-800'
                                ];

                                $moodClass = $moodStyles[$mood] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $moodClass ?>">
                                    <?= htmlspecialchars($mood) ?>
                                </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="deleteWorkout(<?= $workout['id'] ?>)" 
                                            class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $limit, $total_items) ?></span> of <span class="font-medium"><?= $total_items ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($current_page > 1): ?>
                                <a href="?page=<?= $current_page - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == $current_page): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-highlight bg-highlight text-white"><?= $i ?></span>
                                    <?php elseif ($i >= $current_page - 2 && $i <= $current_page + 2): ?>
                                        <a href="?page=<?= $i ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Workout Log Modal -->
    <div id="workoutModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Log Your Workout</h3>
                <button id="closeWorkoutModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="workoutForm" class="space-y-4">
                <input type="hidden" name="log_workout" value="1">
                <input type="hidden" name="ajax" value="1">

                <div>
                    <label class="block text-gray-700 mb-2">Workout Type</label>
                    <select name="workout_type" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" required>
                        <option value="">Select type</option>
                        <option value="Cardio">Cardio</option>
                        <option value="Upper Body">Upper Body</option>
                        <option value="Lower Body">Lower Body</option>
                        <option value="Full Body">Full Body</option>
                        <option value="HIIT">HIIT</option>
                        <option value="Yoga">Yoga</option>
                        <option value="CrossFit">CrossFit</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Duration (mins)</label>
                        <input type="number" name="duration" min="1" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Calories Burned</label>
                        <input type="number" name="calories" min="0" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 mb-2">Workout Notes</label>
                    <textarea name="workout_notes" placeholder="Describe your workout..." class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-highlight" rows="3"></textarea>
                </div>

                <div>
                    <label class="block text-gray-700 mb-2">How are you feeling?</label>
                    <div class="flex justify-around">
                        <?php 
                        $moods = [
                            '😠' => 'Frustrated',
                            '😐' => 'Neutral', 
                            '😊' => 'Happy',
                            '😁' => 'Energized'
                        ];
                        
                        foreach ($moods as $mood => $label): 
                        ?>
                        <div class="flex flex-col items-center">
                            <div class="mood-btn <?php echo $mood === '😊' ? 'active' : ''; ?>" 
                                 data-mood="<?php echo $mood; ?>" 
                                 id="moodBtn_<?php echo $mood; ?>">
                                <span class="text-xl"><?php echo $mood; ?></span>
                            </div>
                            <span class="mt-1 text-xs text-gray-600"><?php echo $label; ?></span>
                        </div>
                        <input type="radio" name="workout_mood" value="<?php echo $mood; ?>" 
                               id="mood_<?php echo $mood; ?>" 
                               class="hidden" 
                               <?php echo $mood === '😊' ? 'checked' : ''; ?>>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="w-full btn-primary py-3 rounded-lg">
                    <i class="fas fa-dumbbell mr-2"></i> Log Workout
                </button>
            </form>
        </div>
    </div>

    <!-- Action Modal -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 id="actionModalTitle" class="text-xl font-bold text-gray-800"></h3>
                <button id="closeActionModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p id="actionModalMessage" class="text-gray-600 mb-6"></p>
            <div class="flex space-x-3">
                <button id="actionConfirm" class="flex-1 bg-highlight text-white py-2 rounded-lg font-semibold"></button>
                <button id="actionCancel" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg font-semibold">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="modal">
        <div class="modal-content text-center">
            <i id="modalIcon" class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
            <h3 id="modalTitle" class="text-xl font-bold mb-2">Success!</h3>
            <p id="modalMessage" class="text-gray-600 mb-4">Action completed.</p>
            <button id="modalClose" class="px-4 py-2 bg-highlight text-white rounded-lg">OK</button>
        </div>
    </div>

    <footer class="bg-primary text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            <p>© 2026 Warzone Gym CRM. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // FIXED: Initialize mood buttons properly
        function initializeMoodButtons() {
            // Remove any existing event listeners to prevent duplicates
            document.querySelectorAll('.mood-btn').forEach(btn => {
                // Clone the button to remove all existing listeners
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
            });

            // Add fresh event listeners
            document.querySelectorAll('.mood-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent any default behavior
                    e.stopPropagation(); // Stop event bubbling
                    
                    // Remove active class from all mood buttons
                    document.querySelectorAll('.mood-btn').forEach(b => {
                        b.classList.remove('active');
                    });
                    
                    // Remove checked from all radio buttons
                    document.querySelectorAll('input[name="workout_mood"]').forEach(radio => {
                        radio.checked = false;
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get the mood from data attribute
                    const mood = this.dataset.mood;
                    
                    // Check the corresponding radio button
                    const radio = document.getElementById('mood_' + mood);
                    if (radio) {
                        radio.checked = true;
                    }
                    
                    console.log('Selected mood:', mood); // Debug log
                });
            });
        }

        // Initialize mood buttons
        initializeMoodButtons();

        // FIXED: Workout form submission with proper emoji handling
        document.getElementById('workoutForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Get selected mood from radio buttons
            const selectedMoodRadio = document.querySelector('input[name="workout_mood"]:checked');
            const selectedMood = selectedMoodRadio ? selectedMoodRadio.value : '😊';
            
            console.log('Submitting mood:', selectedMood); // Debug log
            
            const formData = new FormData(e.target);
            formData.append('workout_mood', selectedMood);
            
            try {
                const response = await fetch('workouts.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                console.log('Server response:', result); // Debug log
                
                showResponse(result.success, result.message, true);
                
                if (result.success) {
                    document.getElementById('workoutModal').style.display = 'none';
                }
            } catch (err) {
                console.error('Error:', err); // Debug log
                showResponse(false, 'Network error. Please try again.');
            }
        });

        // FIXED: Open workout modal with proper initialization
        function openWorkoutModal() {
            document.getElementById('modalTitle').textContent = 'Log Your Workout';
            
            // Reset form
            document.getElementById('workoutForm').reset();
            
            // Reset mood buttons
            document.querySelectorAll('.mood-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Set default mood to 😊
            const defaultMoodBtn = document.getElementById('moodBtn_😊');
            const defaultMoodRadio = document.getElementById('mood_😊');
            
            if (defaultMoodBtn && defaultMoodRadio) {
                defaultMoodBtn.classList.add('active');
                defaultMoodRadio.checked = true;
            }
            
            document.getElementById('workoutModal').style.display = 'flex';
            
            // Auto-focus workout type
            setTimeout(() => {
                document.querySelector('select[name="workout_type"]')?.focus();
            }, 150);
        }

        // Open modals
        document.getElementById('logWorkoutBtn')?.addEventListener('click', openWorkoutModal);
        document.getElementById('logFirstWorkoutBtn')?.addEventListener('click', openWorkoutModal);
        
        // Close modals
        document.getElementById('closeWorkoutModal')?.addEventListener('click', () => {
            document.getElementById('workoutModal').style.display = 'none';
        });
        
        document.getElementById('workoutModal')?.addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                e.currentTarget.style.display = 'none';
            }
        });

        // Delete workout function
        window.deleteWorkout = function(id) {
            openActionModal(
                'Delete Workout',
                'Are you sure you want to delete this workout? This cannot be undone.',
                'Delete',
                () => {
                    const formData = new FormData();
                    formData.append('ajax', '1');
                    formData.append('delete_workout', '1');
                    formData.append('workout_id', id);
                    
                    fetch('workouts.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(result => {
                            showResponse(result.success, result.message, true);
                        })
                        .catch(error => {
                            showResponse(false, 'Failed to delete workout.');
                        });
                }
            );
        };

        // Action modal handler
        window.openActionModal = function(title, message, confirmText, onConfirm) {
            document.getElementById('actionModalTitle').textContent = title;
            document.getElementById('actionModalMessage').textContent = message;
            document.getElementById('actionConfirm').textContent = confirmText;
            document.getElementById('actionModal').style.display = 'flex';
            
            // Set up handlers
            document.getElementById('actionConfirm').onclick = function() {
                onConfirm();
                document.getElementById('actionModal').style.display = 'none';
            };
            document.getElementById('actionCancel').onclick = function() {
                document.getElementById('actionModal').style.display = 'none';
            };
            document.getElementById('closeActionModal').onclick = function() {
                document.getElementById('actionModal').style.display = 'none';
            };
            
            // Close on outside click
            document.getElementById('actionModal').onclick = (e) => {
                if (e.target === e.currentTarget) {
                    document.getElementById('actionModal').style.display = 'none';
                }
            };
        };

        // Response modal
        function showResponse(success, message, autoReload = false) {
            const modal = document.getElementById('responseModal');
            const icon = document.getElementById('modalIcon');
            const title = document.getElementById('modalTitle');
            const msg = document.getElementById('modalMessage');
            
            if (success) {
                icon.className = 'fas fa-check-circle text-green-500 text-4xl mb-4';
                title.textContent = 'Success!';
            } else {
                icon.className = 'fas fa-exclamation-triangle text-red-500 text-4xl mb-4';
                title.textContent = 'Error';
            }
            
            msg.textContent = message;
            modal.style.display = 'flex';
            
            // Auto-close after 3 seconds
            const timeout = setTimeout(() => {
                modal.style.display = 'none';
                if (autoReload && success) {
                    location.reload();
                }
            }, 3000);
            
            // Manual close
            document.getElementById('modalClose').onclick = () => {
                clearTimeout(timeout);
                modal.style.display = 'none';
                if (autoReload && success) location.reload();
            };
            
            modal.onclick = (e) => {
                if (e.target === modal) {
                    clearTimeout(timeout);
                    modal.style.display = 'none';
                    if (autoReload && success) location.reload();
                }
            };
        }
    });
    </script>

    <?php include 'modal_logout.php'; ?>
</body>
</html>