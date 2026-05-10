<?php
// journal.php - Journey Journal (Fixed Mood Selection & Archive)
require_once 'auth.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get journal entries
$filter = $_GET['filter'] ?? 'active';

$query = "SELECT * FROM mood_checkins WHERE user_id = ?";
$params = [$user_id];

if ($filter === 'archived') {
    $query .= " AND archived = 1";
} elseif ($filter === 'starred') {
    $query .= " AND starred = 1 AND archived = 0";
} else {
    $query .= " AND archived = 0";
}

$query .= " ORDER BY date DESC, id DESC";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM mood_checkins WHERE user_id = ? AND starred = 1 AND archived = 0");
$stmt->execute([$user_id]);
$starred_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM mood_checkins WHERE user_id = ? AND archived = 1");
$stmt->execute([$user_id]);
$archived_count = (int)$stmt->fetchColumn();

// Pagination logic
$items_per_page = 10;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

// Get total count for current filter
$count_query = str_replace("SELECT *", "SELECT COUNT(*)", $query);
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_items = (int)$stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

$query .= " LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle journal actions (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        $id = $_POST['id'] ?? null;
        $mood = $_POST['mood'] ?? '😊'; // ✅ Default to 😊
        $notes = trim($_POST['notes'] ?? '');
        $date = $_POST['date'] ?? date('Y-m-d');
        
        $result = ['success' => false, 'message' => 'Invalid action'];
        
        switch ($action) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO mood_checkins (user_id, date, mood, notes) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $date, $mood, $notes]);
                $result = ['success' => true, 'message' => '✅ Entry added!'];
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("UPDATE mood_checkins SET mood = ?, notes = ?, date = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$mood, $notes, $date, $id, $user_id]);
                $result = ['success' => true, 'message' => '✅ Entry updated!'];
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM mood_checkins WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $result = ['success' => true, 'message' => '🗑️ Entry deleted.'];
                break;
                
            case 'archive':
                $stmt = $pdo->prepare("UPDATE mood_checkins SET archived = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $result = ['success' => true, 'message' => '📦 Archived.'];
                break;
                
            case 'unarchive':
                $stmt = $pdo->prepare("UPDATE mood_checkins SET archived = 0 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $result = ['success' => true, 'message' => '↩️ Restored from archive.'];
                break;
                
            case 'star':
                $stmt = $pdo->prepare("UPDATE mood_checkins SET starred = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $result = ['success' => true, 'message' => '⭐ Starred!'];
                break;
                
            case 'unstar':
                $stmt = $pdo->prepare("UPDATE mood_checkins SET starred = 0 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $result = ['success' => true, 'message' => '☆ Unstarred.'];
                break;
        }
        
        echo json_encode($result);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '⚠️ ' . $e->getMessage()]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM - Journey Journal</title>
    <!-- ✅ FIXED CDN URLs (removed trailing spaces) -->
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
        .journal-entry {
            transition: all 0.3s ease;
            border-left: 4px solid #e2e8f0;
        }
        .journal-entry.starred { 
            border-left-color: #fbbf24;
            background-color: #fffbeb;
        }
        .journal-entry:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .journal-actions { display: flex; gap: 0.25rem; }
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
        }
        .mood-btn { 
            width: 60px; height: 60px; font-size: 2rem; 
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
        .btn-primary {
            background: linear-gradient(45deg, #e94560, #0f3460);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-50">

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
                <div class="flex items-center space-x-3">
                    <a href="profile.php" class="hidden md:flex items-center space-x-2 group" title="Profile">
                        <img src="<?= htmlspecialchars(file_exists('uploads/' . ($_SESSION['user_profile_picture'] ?? 'default.png')) ? 'uploads/' . $_SESSION['user_profile_picture'] : 'uploads/default.png') ?>" 
                             alt="Profile" 
                             class="rounded-full w-10 h-10 transition-transform duration-200 group-hover:scale-105 group-hover:ring-2 group-hover:ring-highlight">
                        <a href="logout.php" 
                        class="text-gray-400 hover:text-highlight transition ml-1 opacity-75 group-hover:opacity-100" 
                        title="Logout">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </a>
                    </a>
                    <button id="userNavToggle" class="md:hidden text-white focus:outline-none p-1" aria-label="Open menu">
                        <i class="fas fa-bars text-xl" id="userNavIcon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <!-- Mobile nav drawer -->
    <div id="userNavDrawer" class="md:hidden hidden bg-primary text-white border-t border-gray-800 shadow-lg sticky top-0 z-40">
        <div class="px-4 py-3 space-y-1">
            <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-home w-6"></i> Dashboard
            </a>
            <a href="workouts.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-dumbbell w-6"></i> Workouts
            </a>
            <a href="attendance.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-calendar-check w-6"></i> Attendance
            </a>
            <a href="journal.php" class="flex items-center px-4 py-3 rounded-lg bg-highlight text-white">
                <i class="fas fa-book w-6"></i> Journal
            </a>
            <a href="chat.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
                <i class="fas fa-robot w-6"></i> AI Coach
            </a>
            <a href="profile.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-secondary text-gray-300 hover:text-highlight">
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
            });
        }
    })();
    </script>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Journey Journal</h1>
                <p class="text-gray-600">Your fitness & mood history — one entry at a time</p>
            </div>
            <button id="addEntryBtn" class="btn-primary px-4 py-2 rounded-lg flex items-center whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i> New Entry
            </button>
        </div>

        <!-- Journal Controls -->
        <div class="flex space-x-2 mb-6">
            <a href="journal.php?filter=active"
            class="px-3 py-1 rounded-lg <?= $filter === 'active' ? 'bg-highlight text-white' : 'bg-gray-200' ?>">
                Active
            </a>

            <a href="journal.php?filter=starred"
            class="px-3 py-1 rounded-lg flex items-center gap-1
            <?= $filter === 'starred' ? 'bg-yellow-400 text-black' : 'bg-gray-200' ?>">
                <i class="fas fa-star"></i>
                Starred
                <span class="bg-yellow-600 text-white text-xs px-1.5 py-0.5 rounded-full">
                    <?= $starred_count ?>
                </span>
            </a>

            <a href="journal.php?filter=archived"
            class="px-3 py-1 rounded-lg flex items-center
            <?= $filter === 'archived' ? 'bg-gray-800 text-white' : 'bg-gray-200' ?>">
                Archived
                <span class="ml-1 bg-gray-700 text-xs px-1.5 py-0.5 rounded-full">
                    <?= $archived_count ?>
                </span>
            </a>
        </div>

        <!-- Journal Entries -->
        <?php if (empty($entries)): ?>
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-book-open text-5xl text-gray-300"></i>
                <h3 class="mt-4 text-xl font-bold text-gray-800">No journal entries yet</h3>
                <p class="text-gray-600 mt-2">Your journey starts with a single entry.</p>
                <button id="addFirstEntryBtn" class="mt-4 btn-primary px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i> Start Journaling
                </button>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($entries as $entry): ?>
                <div class="journal-entry <?= $entry['starred'] ? 'starred' : '' ?> bg-white rounded-xl shadow p-5">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center">
                            <span class="text-3xl mr-3"><?= htmlspecialchars($entry['mood']) ?></span>
                            <div>
                                <div class="flex items-center">
                                    <h3 class="font-bold text-gray-800"><?= date('F j, Y', strtotime($entry['date'])) ?></h3>
                                    <?php if ($entry['starred']): ?>
                                        <i class="fas fa-star text-yellow-500 ml-2"></i>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($entry['notes'])): ?>
                                    <p class="text-gray-700 mt-2"><?= htmlspecialchars($entry['notes']) ?></p>
                                <?php else: ?>
                                    <p class="text-gray-500 italic">No notes</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="journal-actions flex space-x-2">
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($entry)) ?>)" class="p-2 text-gray-500 hover:text-blue-500 rounded-full hover:bg-gray-100">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleStar(<?= $entry['id'] ?>, <?= $entry['starred'] ?>)"
                                class="p-2 text-gray-500 hover:text-yellow-500 rounded-full hover:bg-gray-100">
                                <?php if ($entry['starred']): ?>
                                    <i class="fas fa-star text-yellow-500"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            </button>
                            <button onclick="archiveEntry(<?= $entry['id'] ?>, <?= $entry['archived'] ?>)" class="p-2 text-gray-500 hover:text-gray-700 rounded-full hover:bg-gray-100">
                                <i class="fas fa-archive"></i>
                            </button>
                            <button onclick="deleteEntry(<?= $entry['id'] ?>)" class="p-2 text-gray-500 hover:text-red-500 rounded-full hover:bg-gray-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex flex-col sm:flex-row justify-between items-center bg-white p-4 rounded-xl shadow gap-4">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $items_per_page, $total_items) ?></span> of <span class="font-medium"><?= $total_items ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($current_page > 1): ?>
                        <a href="?filter=<?= $filter ?>&page=<?= $current_page - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-highlight bg-highlight text-white"><?= $i ?></span>
                            <?php elseif ($i >= $current_page - 2 && $i <= $current_page + 2): ?>
                                <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                        <a href="?filter=<?= $filter ?>&page=<?= $current_page + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- Response Modal -->
    <div id="responseModal" class="modal">
        <div class="modal-content p-6 text-center">
            <i id="modalIcon" class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
            <h3 id="modalTitle" class="text-xl font-bold text-gray-800 mb-2">Success!</h3>
            <p id="modalMessage" class="text-gray-600 mb-4">Action completed.</p>
            <button id="modalClose" class="px-4 py-2 bg-highlight text-white rounded-lg">OK</button>
        </div>
    </div>

    <!-- Add/Edit Entry Modal -->
    <div id="entryModal" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800">New Journal Entry</h3>
                <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700 text-2xl font-bold">
                    &times;
                </button>
            </div>
            <form id="journalForm" class="p-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="entryId">
                <input type="hidden" name="ajax" value="1">
                
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">How are you feeling today?</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <?php 
                    $moods = ['😠','😐','😊','😁']; 
                    $labels = ['Frustrated','Neutral','Happy','Energized'];
                    for($i = 0; $i < count($moods); $i++): 
                        $isDefault = ($moods[$i] === '😊');
                    ?>
                        <label class="flex flex-col items-center cursor-pointer">
                            <!-- ✅ Button with data-mood (no emoji in ID) -->
                            <div class="mood-btn flex items-center justify-center <?= $isDefault ? 'active' : '' ?>" 
                                data-mood="<?= htmlspecialchars($moods[$i]) ?>">
                                <span class="text-2xl"><?= $moods[$i] ?></span>
                            </div>
                            <span class="mt-2 text-sm text-gray-600"><?= $labels[$i] ?></span>
                            <!-- ✅ Input inside label, no ID needed -->
                            <input type="radio" name="mood" value="<?= htmlspecialchars($moods[$i]) ?>" 
                                class="hidden" <?= $isDefault ? 'checked' : '' ?>>
                        </label>
                    <?php endfor; ?>
                </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Date</label>
                    <input type="date" name="date" id="entryDate" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight"
                           value="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">Journal Entry</label>
                    <textarea name="notes" id="entryNotes" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight"
                              placeholder="What's on your mind? Progress, struggles, wins..."></textarea>
                </div>
                
                <button type="submit" class="w-full btn-primary py-3 rounded-lg">
                    <i class="fas fa-save mr-2"></i> <span id="saveBtnText">Save Entry</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Action Confirmation Modal -->
    <div id="actionModal" class="modal">
        <div class="modal-content p-6">
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

    <footer class="bg-primary text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            <p>© 2026 Warzone Gym CRM. All rights reserved.</p>
        </div>
    </footer>

    <script>
document.addEventListener('DOMContentLoaded', function() {

    // =========================
    // MOOD SELECTION HANDLER
    // =========================
    function activateMoodButton(mood) {
        document.querySelectorAll('.mood-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.mood === mood) {
                btn.classList.add('active');
                const radio = document.querySelector(`input[name="mood"][value="${CSS.escape(mood)}"]`);
                if (radio) radio.checked = true;
            }
        });
    }

    // Click mood button
    document.querySelectorAll('.mood-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            activateMoodButton(this.dataset.mood);
        });
    });

    // Handle label clicks (progressive enhancement)
    document.querySelectorAll('label').forEach(label => {
        label.addEventListener('click', function(e) {
            if (e.target.closest('.mood-btn')) return; // Avoid double-trigger
            const moodBtn = this.querySelector('.mood-btn');
            if (moodBtn) moodBtn.click();
        });
    });

    // =========================
    // OPEN NEW ENTRY MODAL
    // =========================
    function openNewEntryModal() {
        document.getElementById('modalTitle').textContent = 'New Journal Entry';
        document.getElementById('formAction').value = 'add';
        document.getElementById('entryId').value = '';
        document.getElementById('saveBtnText').textContent = 'Save Entry';

        // Reset form
        document.getElementById('entryDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('entryNotes').value = '';

        // Reset mood buttons to 😊
        activateMoodButton('😊');

        document.getElementById('entryModal').style.display = 'flex';

        // Auto-focus notes field
        setTimeout(() => {
            document.getElementById('entryNotes')?.focus();
        }, 150);
    }

    document.getElementById('addEntryBtn')?.addEventListener('click', openNewEntryModal);
    document.getElementById('addFirstEntryBtn')?.addEventListener('click', openNewEntryModal);

    // =========================
    // OPEN EDIT ENTRY MODAL
    // =========================
    window.openEditModal = function(entry) {
        document.getElementById('modalTitle').textContent = 'Edit Entry';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('entryId').value = entry.id;
        document.getElementById('saveBtnText').textContent = 'Update Entry';

        // Prefill form
        document.getElementById('entryDate').value = entry.date;
        document.getElementById('entryNotes').value = entry.notes || '';

        // Prefill mood
        activateMoodButton(entry.mood);

        document.getElementById('entryModal').style.display = 'flex';
    };

    // =========================
    // CLOSE ENTRY MODAL
    // =========================
    document.getElementById('closeModalBtn')?.addEventListener('click', () => {
        document.getElementById('entryModal').style.display = 'none';
    });

    document.getElementById('entryModal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) {
            e.currentTarget.style.display = 'none';
        }
    });

    // =========================
    // ACTION MODAL
    // =========================
    window.openActionModal = function(title, message, confirmText, onConfirm) {
        document.getElementById('actionModalTitle').textContent = title;
        document.getElementById('actionModalMessage').textContent = message;
        document.getElementById('actionConfirm').textContent = confirmText;
        document.getElementById('actionModal').style.display = 'flex';

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
        document.getElementById('actionModal').onclick = (e) => {
            if (e.target === e.currentTarget) {
                document.getElementById('actionModal').style.display = 'none';
            }
        };
    };

    // =========================
    // DELETE / ARCHIVE / STAR
    // =========================
    window.deleteEntry = function(id) {
        openActionModal(
            'Delete Entry',
            'Are you sure you want to delete this journal entry? This cannot be undone.',
            'Delete',
            () => sendAjax('delete', id)
        );
    };

    window.archiveEntry = function(id, isArchived) {
        const action = isArchived ? 'unarchive' : 'archive';
        const verb = isArchived ? 'restore' : 'archive';
        openActionModal(
            isArchived ? 'Restore Entry' : 'Archive Entry',
            `Are you sure you want to ${verb} this entry?`,
            isArchived ? 'Restore' : 'Archive',
            () => sendAjax(action, id)
        );
    };

    window.toggleStar = function(id, isStarred) {
        const action = isStarred ? 'unstar' : 'star';
        openActionModal(
            isStarred ? 'Unstar Entry' : 'Star Entry',
            isStarred ? 'Remove this entry from favorites?' : 'Add this entry to favorites?',
            isStarred ? 'Unstar' : 'Star',
            () => sendAjax(action, id)
        );
    };

    function sendAjax(action, id) {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', action);
        formData.append('id', id);

        fetch('journal.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => showResponse(res.success, res.message, true))
            .catch(() => showResponse(false, 'Network error.'));
    }

    // =========================
    // FORM SUBMISSION
    // =========================
    document.getElementById('journalForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('ajax', '1');

        try {
            const res = await fetch('journal.php', { method: 'POST', body: formData });
            const result = await res.json();
            showResponse(result.success, result.message, true);
            if (result.success) document.getElementById('entryModal').style.display = 'none';
        } catch {
            showResponse(false, 'Network error. Please try again.');
        }
    });

    // =========================
    // RESPONSE MODAL
    // =========================
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

        const timeout = setTimeout(() => {
            modal.style.display = 'none';
            if (autoReload && success) location.reload();
        }, 3000);

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