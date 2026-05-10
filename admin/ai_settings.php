<?php
// admin/ai_settings.php - Manage Global AI Personalities
require_once '../auth.php';
require_admin();

// Fetch existing settings
$stmt = $pdo->query("SELECT * FROM ai_personas ORDER BY created_at DESC");
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Persona CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Invalid token");
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_persona') {
        $id = $_POST['persona_id'] ?? null;
        $name = htmlspecialchars(trim($_POST['name']));
        $instructions = htmlspecialchars(trim($_POST['instructions']));
        $status = $_POST['status'] ?? 'active';

        if ($id) {
            $stmt = $pdo->prepare("UPDATE ai_personas SET name = ?, instructions = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $instructions, $status, $id]);
            log_activity($_SESSION['user_id'], 'AI Persona Updated', "Updated persona: $name");
        } else {
            $stmt = $pdo->prepare("INSERT INTO ai_personas (name, instructions, status) VALUES (?, ?, ?)");
            $stmt->execute([$name, $instructions, $status]);
            log_activity($_SESSION['user_id'], 'AI Persona Created', "Created persona: $name");
        }
        $_SESSION['admin_success'] = "AI Persona saved successfully!";
    } elseif ($action === 'delete_persona') {
        $id = (int)$_POST['persona_id'];
        $pdo->prepare("DELETE FROM ai_personas WHERE id = ?")->execute([$id]);
        log_activity($_SESSION['user_id'], 'AI Persona Deleted', "Deleted persona ID: $id");
        $_SESSION['admin_success'] = "AI Persona deleted!";
    }
    
    header("Location: ai_settings.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warzone Admin - AI Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#1a1a2e', highlight: '#e94560' } } } }
    </script>
</head>
<body class="bg-gray-50 md:flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 md:ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">AI Personality Management</h1>
                <p class="text-gray-600">Configure global coach styles and instructions.</p>
            </div>
            <button onclick="openPersonaModal()" class="bg-highlight text-white px-6 py-2 rounded-lg font-bold shadow-lg hover:opacity-90 transition">
                <i class="fas fa-plus mr-2"></i> New Persona
            </button>
        </div>

        <?php if (isset($_SESSION['admin_success'])): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                <?= $_SESSION['admin_success']; unset($_SESSION['admin_success']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <?php foreach ($personas as $persona): ?>
                <div class="bg-white p-6 rounded-xl shadow-sm border <?= $persona['status'] === 'active' ? 'border-green-100' : 'border-gray-200' ?> relative group">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 flex items-center">
                                <?= htmlspecialchars($persona['name']) ?>
                                <?php if ($persona['status'] === 'active'): ?>
                                    <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-600 text-[10px] rounded-full uppercase">Active</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-[10px] text-gray-400 font-mono mt-1">ID: <?= $persona['id'] ?></p>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick='editPersona(<?= json_encode($persona) ?>)' class="text-gray-400 hover:text-blue-500 transition"><i class="fas fa-edit"></i></button>
                            <form method="POST" onsubmit="return confirm('Delete this persona?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_persona">
                                <input type="hidden" name="persona_id" value="<?= $persona['id'] ?>">
                                <button type="submit" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-600 italic line-clamp-4">
                        "<?= htmlspecialchars($persona['instructions']) ?>"
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="personaModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-lg p-8 shadow-2xl">
            <h2 id="modalTitle" class="text-2xl font-bold mb-6">Create AI Persona</h2>
            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_persona">
                <input type="hidden" name="persona_id" id="personaId">
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="personaName" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-highlight outline-none" placeholder="e.g. Drill Sergeant">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Instructions (The AI's personality/tone)</label>
                    <textarea name="instructions" id="personaInstructions" rows="6" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-highlight outline-none" placeholder="You are a tough coach who doesn't take excuses..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Status</label>
                    <select name="status" id="personaStatus" class="w-full border rounded-lg px-4 py-2">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="flex space-x-4 pt-4">
                    <button type="button" onclick="closePersonaModal()" class="flex-1 bg-gray-100 py-3 rounded-lg font-bold">Cancel</button>
                    <button type="submit" class="flex-1 bg-highlight text-white py-3 rounded-lg font-bold">Save Persona</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPersonaModal() {
            document.getElementById('modalTitle').textContent = 'Create AI Persona';
            document.getElementById('personaId').value = '';
            document.getElementById('personaName').value = '';
            document.getElementById('personaInstructions').value = '';
            document.getElementById('personaStatus').value = 'active';
            document.getElementById('personaModal').classList.remove('hidden');
        }
        function closePersonaModal() {
            document.getElementById('personaModal').classList.add('hidden');
        }
        function editPersona(p) {
            document.getElementById('modalTitle').textContent = 'Edit Persona';
            document.getElementById('personaId').value = p.id;
            document.getElementById('personaName').value = p.name;
            document.getElementById('personaInstructions').value = p.instructions;
            document.getElementById('personaStatus').value = p.status;
            document.getElementById('personaModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
