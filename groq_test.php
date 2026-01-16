<?php
// ✅ Use YOUR actual key here (no quotes/extra spaces)
$api_key = 'gsk_yi14...'; // ← REPLACE WITH FULL KEY

$data = json_encode([
    'model' => 'llama3-8b-8192',
    'messages' => [['role' => 'user', 'content' => 'Sugod!']]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.groq.com/openai/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$api_key}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>📡 Groq Test</h2>";
echo "<p><b>HTTP Code:</b> {$http_code}</p>";
echo "<p><b>cURL Error:</b> " . htmlspecialchars($error) . "</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";