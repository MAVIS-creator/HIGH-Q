<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requirePermission('settings');
} catch (Throwable $e) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute(['ai_assistant_settings']);
        $raw = $stmt->fetchColumn();
        $cfg = $raw ? json_decode((string)$raw, true) : [];
        if (!is_array($cfg)) $cfg = [];

        echo json_encode([
            'status' => 'ok',
            'settings' => array_merge([
                'enabled' => 1,
                'provider' => 'env_auto',
                'model_override' => '',
                'service_url' => '',
            ], $cfg),
            'available' => [
                'service' => !empty($_ENV['AI_ASSISTANT_SERVICE_URL']) || !empty(getenv('AI_ASSISTANT_SERVICE_URL')),
                'groq' => !empty($_ENV['GROQ_API_KEY']) || !empty(getenv('GROQ_API_KEY')),
                'openrouter' => !empty($_ENV['AI_OPENROUTER_API_KEY']) || !empty(getenv('AI_OPENROUTER_API_KEY')),
                'gemini' => !empty($_ENV['AI_GEMINI_API_KEY']) || !empty(getenv('AI_GEMINI_API_KEY')),
            ],
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Unable to load provider settings']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$csrf = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verifyToken('ai_provider_settings_api', (string)$csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$enabled = !empty($_POST['enabled']) ? 1 : 0;
$provider = strtolower(trim((string)($_POST['provider'] ?? 'env_auto')));
$modelOverride = trim((string)($_POST['model_override'] ?? ''));
$serviceUrl = trim((string)($_POST['service_url'] ?? ''));

$allowedProviders = ['env_auto', 'service', 'groq', 'openrouter', 'gemini'];
if (!in_array($provider, $allowedProviders, true)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid provider']);
    exit;
}

$payload = json_encode([
    'enabled' => $enabled,
    'provider' => $provider,
    'model_override' => $modelOverride,
    'service_url' => $serviceUrl,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

try {
    $stmt = $pdo->prepare("SELECT id FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute(['ai_assistant_settings']);
    $id = $stmt->fetchColumn();

    if ($id) {
        $upd = $pdo->prepare("UPDATE settings SET value = ? WHERE id = ?");
        $upd->execute([$payload, $id]);
    } else {
        $ins = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?)");
        $ins->execute(['ai_assistant_settings', $payload]);
    }

    logAction($pdo, (int)($_SESSION['user']['id'] ?? 0), 'ai_provider_settings_saved', [
        'enabled' => $enabled,
        'provider' => $provider,
        'has_model_override' => $modelOverride !== '',
        'has_service_url' => $serviceUrl !== '',
    ]);

    echo json_encode(['status' => 'ok', 'message' => 'AI provider settings saved']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to save provider settings']);
}
