<?php
// ai_coach.php — Warzone AI Coach (Groq-first, Qwen fallback)

function get_ai_response(string $user_message, array $context): string {
    // 🔑 Load & sanitize key: trim + remove control chars (CR/LF/NBSP)
    $raw_key = $_ENV['QWEN_API_KEY'] ?? $_ENV['GROQ_API_KEY'] ?? '';
    $api_key = preg_replace('/[\x00-\x1F\x7F]/', '', trim($raw_key));

    if (!$api_key) {
        return "⚠️ Coach offline. Admin: Add GROQ_API_KEY or QWEN_API_KEY to .env";
    }

    // 🔍 Auto-detect: gsk_ → Groq, sk- → Qwen
    if (strpos($api_key, 'gsk_') === 0) {
        return call_groq_api($api_key, $user_message, $context);
    } elseif (strpos($api_key, 'sk-') === 0) {
        return call_qwen_api($api_key, $user_message, $context);
    } else {
        return "⚠️ Invalid key format. Use Groq (gsk_...) or Qwen (sk-...)";
    }
}

function call_groq_api(string $api_key, string $user_message, array $context): string {
    $style = $_SESSION['user_coach_style'] ?? 'balanced';
    $system_prompt = generate_coach_prompt($style, $context);

    $url = 'https://api.groq.com/openai/v1/chat/completions'; // ✅ FIXED: removed trailing spaces
    $data = [
        'model' => 'llama-3.1-8b-instant',
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_message]
        ],
        'temperature' => 0.7,
        'max_tokens' => 300
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$api_key}",
            "Content-Type: application/json",
            "User-Agent: WarzoneGym/1.0"
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false, // ⚠️ XAMPP dev only — disable cert check
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("Groq cURL error: {$error}");
        return "📡 Network error: " . ($error ?: 'Unknown');
    }

    $resp = json_decode($response, true);
    if (isset($resp['error'])) {
        $msg = $resp['error']['message'] ?? "HTTP {$http_code}";
        error_log("Groq API error ({$http_code}): {$msg}");
        return "🤖 Groq: {$msg}";
    }

    $content = $resp['choices'][0]['message']['content'] ?? '';
    return $content ? trim($content) : "💬 Silent reply — try again.";
}

function call_qwen_api(string $api_key, string $user_message, array $context): string {
    $style = $_SESSION['user_coach_style'] ?? 'balanced';
    $system_prompt = generate_coach_prompt($style, $context);

    $url = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation'; // ✅ FIXED: removed trailing spaces
    $data = [
        'model' => 'qwen-turbo',
        'input' => [
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_message]
            ]
        ],
        'parameters' => ['result_format' => 'message']
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$api_key}",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("Qwen cURL error: {$error}");
        return "📡 Qwen network error: " . substr($error, 0, 60);
    }

    $resp = json_decode($response, true);
    $content = $resp['output']['choices'][0]['message']['content'] ?? '';
    return $content ? trim($content) : "🤖 Qwen silent — try again.";
}

function generate_coach_prompt(string $style, array $context): string {
    $user_name = htmlspecialchars($_SESSION['user_name'] ?? 'ka-warzone');
    $goal = $context['goal'] ?? 'general fitness';
    $last_workout = $context['last_workout'] ? date('F j', strtotime($context['last_workout'])) : 'never';
    $streak = $context['streak_days'] ?? 0;
    $total = $context['total_sessions'] ?? 0;

    $base = "You are Warzone AI Coach — a Filipino-rooted, science-backed fitness mentor. ";
    $base .= "Speak in natural Taglish when fitting (e.g., 'Sugod!', 'Tama yan!', 'Galing!'), but stay clear. ";
    $base .= "User: {$user_name}. Goal: {$goal}. Last workout: {$last_workout}. Streak: {$streak}d. Sessions: {$total}. ";

    $styles = [
        'gentle' => $base . "Be warm, patient, lolo/lola energy: encouraging, no shame, focus on small wins.",
        'balanced' => $base . "Be direct, practical, gym-rat style: mix science + street smarts. Occasional tough love, but respect effort.",
        'hardcore' => $base . "Be intense, drill-sergeant: zero excuses, high pride/challenge. Use 'TANGINA', 'SUGOD KA!', 'Wala kang excuse.' — but care deeply."
    ];

    return $styles[$style] ?? $styles['balanced'];
}

function getUserContext(PDO $pdo, int $user_id): array {
    $stmt = $pdo->prepare("
        SELECT u.goal, MAX(w.date) AS last_workout, COUNT(w.id) AS total_sessions
        FROM users u
        LEFT JOIN workouts w ON w.user_id = u.id
        WHERE u.id = ?
        GROUP BY u.id, u.goal
    ");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM (
            SELECT DISTINCT DATE(date) FROM workouts
            WHERE user_id = ? AND date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) AS s
    ");
    $stmt->execute([$user_id]);
    $streak = (int) $stmt->fetchColumn();

    return [
        'goal' => $row['goal'] ?? 'General fitness',
        'last_workout' => $row['last_workout'] ?? null,
        'total_sessions' => (int) ($row['total_sessions'] ?? 0),
        'streak_days' => $streak,
    ];
}