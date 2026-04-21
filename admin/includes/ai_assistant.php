<?php

/**
 * Central AI assistant adapter.
 *
 * Provider selection order (from env):
 * 1) AI_ASSISTANT_SERVICE_URL (custom internal service)
 * 2) GROQ_API_URL + GROQ_API_KEY
 * 3) AI_OPENROUTER_API_URL + AI_OPENROUTER_API_KEY
 * 4) AI_GEMINI_API_KEY
 */

function ai_assistant_load_runtime_settings(?PDO $pdo = null): array {
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute(['ai_assistant_settings']);
        $raw = $stmt->fetchColumn();
        if (!$raw) return [];

        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : [];
    } catch (Throwable $e) {
        return [];
    }
}

function ai_assistant_pick_provider(?PDO $pdo = null): array {
    $runtime = ai_assistant_load_runtime_settings($pdo);
    $selectedProvider = strtolower(trim((string)($runtime['provider'] ?? 'env_auto')));
    $modelOverride = trim((string)($runtime['model_override'] ?? ''));
    $serviceOverride = trim((string)($runtime['service_url'] ?? ''));

    if (!empty($runtime['enabled']) && (int)$runtime['enabled'] === 0) {
        return ['provider' => 'none'];
    }

    $serviceUrl = trim((string)($_ENV['AI_ASSISTANT_SERVICE_URL'] ?? getenv('AI_ASSISTANT_SERVICE_URL') ?? ''));
    if ($serviceOverride !== '') {
        $serviceUrl = $serviceOverride;
    }
    if ($serviceUrl !== '') {
        if ($selectedProvider !== 'env_auto' && $selectedProvider !== 'service') {
            // Skip service when user explicitly selected a different provider.
        } else {
        return ['provider' => 'service', 'url' => $serviceUrl];
        }
    }

    $groqKey = trim((string)($_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?? ''));
    $groqUrl = trim((string)($_ENV['GROQ_API_URL'] ?? getenv('GROQ_API_URL') ?? 'https://api.groq.com/openai/v1/chat/completions'));
    if ($groqKey !== '') {
        if ($selectedProvider === 'env_auto' || $selectedProvider === 'groq') {
            $model = (string)($_ENV['GROQ_MODEL'] ?? getenv('GROQ_MODEL') ?? 'llama-3.1-8b-instant');
            if ($modelOverride !== '') $model = $modelOverride;
            return ['provider' => 'groq', 'url' => $groqUrl, 'api_key' => $groqKey, 'model' => $model];
        }
    }

    $orKey = trim((string)($_ENV['AI_OPENROUTER_API_KEY'] ?? getenv('AI_OPENROUTER_API_KEY') ?? ''));
    $orUrl = trim((string)($_ENV['AI_OPENROUTER_API_URL'] ?? getenv('AI_OPENROUTER_API_URL') ?? 'https://openrouter.ai/api/v1/chat/completions'));
    if ($orKey !== '') {
        if ($selectedProvider === 'env_auto' || $selectedProvider === 'openrouter') {
            $model = (string)($_ENV['AI_OPENROUTER_MODEL'] ?? getenv('AI_OPENROUTER_MODEL') ?? 'openrouter/auto');
            if ($modelOverride !== '') $model = $modelOverride;
            return ['provider' => 'openrouter', 'url' => $orUrl, 'api_key' => $orKey, 'model' => $model];
        }
    }

    $geminiKey = trim((string)($_ENV['AI_GEMINI_API_KEY'] ?? getenv('AI_GEMINI_API_KEY') ?? ''));
    if ($geminiKey !== '') {
        if ($selectedProvider === 'env_auto' || $selectedProvider === 'gemini') {
            $model = (string)($_ENV['AI_GEMINI_MODEL'] ?? getenv('AI_GEMINI_MODEL') ?? 'gemini-2.0-flash');
            if ($modelOverride !== '') $model = $modelOverride;
            return ['provider' => 'gemini', 'api_key' => $geminiKey, 'model' => $model];
        }
    }

    return ['provider' => 'none'];
}

function ai_assistant_http_json(string $url, array $payload, array $headers = [], int $timeout = 25): array {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL extension is required for AI assistant API requests.');
    }

    $ch = curl_init($url);
    $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $httpHeaders = ['Content-Type: application/json'];
    foreach ($headers as $h) {
        $httpHeaders[] = $h;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $httpHeaders,
        CURLOPT_POSTFIELDS => $encoded,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        throw new RuntimeException('AI request failed: ' . $error);
    }

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('AI provider returned HTTP ' . $status);
    }

    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('AI provider returned non-JSON response.');
    }

    return $decoded;
}

function ai_assistant_system_prompt(string $roleName, array $allowedSlugs): string {
    $allowed = implode(', ', $allowedSlugs);

    return "You are the HIGH-Q Admin AI Assistant.\n"
        . "Current admin role: {$roleName}.\n"
        . "Allowed modules for this role: {$allowed}.\n\n"
        . "What you can do:\n"
        . "- Explain what each admin page does\n"
        . "- Answer questions about site settings, payments, users, courses, and roles\n"
        . "- Summarize logs, alerts, and system activity\n"
        . "- Draft responses for support or admin communication\n"
        . "- Prepare safe automation suggestions for review\n\n"
        . "What you cannot do directly:\n"
        . "- Do not delete or change records without explicit confirmation\n"
        . "- Do not bypass role permissions\n"
        . "- Do not expose sensitive data outside role access\n"
        . "- Do not run dangerous actions automatically\n\n"
        . "Safety rules:\n"
        . "- All write actions require admin confirmation\n"
        . "- Every action is logged\n"
        . "- Respect role-based access control\n"
        . "- Use only approved tools and provided context\n\n"
        . "If requested action is out of role scope, explain why and suggest a safe next step.";
}

function ai_assistant_query(string $question, string $context, string $roleName, array $allowedSlugs, ?PDO $pdo = null): array {
    $provider = ai_assistant_pick_provider($pdo);
    if (($provider['provider'] ?? 'none') === 'none') {
        throw new RuntimeException('AI assistant provider is not configured in environment variables.');
    }

    $systemPrompt = ai_assistant_system_prompt($roleName, $allowedSlugs);
    $userPrompt = trim($question);
    if ($context !== '') {
        $userPrompt .= "\n\nContext:\n" . $context;
    }

    if ($provider['provider'] === 'service') {
        $response = ai_assistant_http_json(
            $provider['url'],
            [
                'question' => $question,
                'context' => $context,
                'role_name' => $roleName,
                'allowed_modules' => $allowedSlugs,
                'policy' => 'confirm-before-write',
            ],
            []
        );

        $answer = trim((string)($response['answer'] ?? $response['message'] ?? ''));
        if ($answer === '') {
            throw new RuntimeException('AI assistant service did not return an answer.');
        }

        return ['answer' => $answer, 'provider' => 'service', 'model' => (string)($response['model'] ?? 'service')];
    }

    if ($provider['provider'] === 'groq' || $provider['provider'] === 'openrouter') {
        $response = ai_assistant_http_json(
            $provider['url'],
            [
                'model' => $provider['model'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.2,
            ],
            [
                'Authorization: Bearer ' . $provider['api_key'],
                'HTTP-Referer: ' . (admin_url('index.php?pages=ai_assistant')),
                'X-Title: HIGH-Q Admin Assistant',
            ]
        );

        $answer = trim((string)($response['choices'][0]['message']['content'] ?? ''));
        if ($answer === '') {
            throw new RuntimeException('AI provider did not return a response message.');
        }

        return ['answer' => $answer, 'provider' => $provider['provider'], 'model' => (string)$provider['model']];
    }

    if ($provider['provider'] === 'gemini') {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($provider['model'])
            . ':generateContent?key=' . rawurlencode($provider['api_key']);

        $response = ai_assistant_http_json(
            $url,
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\n" . $userPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                ],
            ]
        );

        $answer = trim((string)($response['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        if ($answer === '') {
            throw new RuntimeException('Gemini did not return generated content.');
        }

        return ['answer' => $answer, 'provider' => 'gemini', 'model' => (string)$provider['model']];
    }

    throw new RuntimeException('Unsupported AI provider configuration.');
}
