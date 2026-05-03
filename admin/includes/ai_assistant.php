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

function ai_assistant_menu_catalog(): array {
    static $catalog = null;

    if ($catalog !== null) {
        return $catalog;
    }

    $menuPath = __DIR__ . '/menu.php';
    $loaded = file_exists($menuPath) ? require $menuPath : [];
    $catalog = is_array($loaded) ? $loaded : [];

    return $catalog;
}

function ai_assistant_site_profile(?PDO $pdo = null): array {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($row) && !empty($row)) {
            return $row;
        }
    } catch (Throwable $e) {
        // Fallback below.
    }

    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute(['system_settings']);
        $raw = $stmt->fetchColumn();
        $decoded = $raw ? json_decode((string)$raw, true) : [];
        if (!is_array($decoded)) {
            return [];
        }

        return [
            'site_name' => $decoded['site']['name'] ?? 'HIGH Q SOLID ACADEMY',
            'tagline' => $decoded['site']['tagline'] ?? '',
            'vision' => $decoded['site']['vision'] ?? '',
            'about' => $decoded['site']['about'] ?? '',
            'contact_email' => $decoded['contact']['email'] ?? '',
            'contact_phone' => $decoded['contact']['phone'] ?? '',
            'contact_address' => $decoded['contact']['address'] ?? '',
            'bank_name' => $decoded['site']['bank_name'] ?? '',
            'bank_account_name' => $decoded['site']['bank_account_name'] ?? '',
            'bank_account_number' => $decoded['site']['bank_account_number'] ?? '',
            'registration' => isset($decoded['security']['registration']) ? (int)!empty($decoded['security']['registration']) : null,
            'email_verification' => isset($decoded['security']['email_verification']) ? (int)!empty($decoded['security']['email_verification']) : null,
            'maintenance' => isset($decoded['security']['maintenance']) ? (int)!empty($decoded['security']['maintenance']) : null,
        ];
    } catch (Throwable $e) {
        return [];
    }
}

function ai_assistant_active_courses(?PDO $pdo = null, int $limit = 10): array {
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("SELECT title, slug, price, duration FROM courses WHERE is_active = 1 ORDER BY title ASC LIMIT ?");
        $stmt->bindValue(1, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        return [];
    }
}

function ai_assistant_count_safe(?PDO $pdo, string $sql): ?int {
    if (!$pdo) {
        return null;
    }

    try {
        $value = $pdo->query($sql)->fetchColumn();
        return $value !== false ? (int)$value : null;
    } catch (Throwable $e) {
        return null;
    }
}

function ai_assistant_module_summary(array $allowedSlugs): array {
    $catalog = ai_assistant_menu_catalog();
    $lines = [];

    foreach ($allowedSlugs as $slug) {
        $item = $catalog[$slug] ?? null;
        if (!is_array($item)) {
            continue;
        }

        $title = trim((string)($item['title'] ?? $slug));
        $url = trim((string)($item['url'] ?? 'index.php?pages=' . $slug));
        $lines[] = "- {$slug}: {$title} ({$url})";
    }

    return $lines;
}

function ai_assistant_site_knowledge(string $roleName, array $allowedSlugs, string $context = '', ?PDO $pdo = null): string {
    $blocks = [];

    $blocks[] = "Assistant identity:\n"
        . "- Name: HighQ AI\n"
        . "- Scope: Admin copilot for HIGH Q SOLID ACADEMY\n"
        . "- Current admin role: {$roleName}";

    $moduleLines = ai_assistant_module_summary($allowedSlugs);
    if (!empty($moduleLines)) {
        $blocks[] = "Role-accessible admin modules:\n" . implode("\n", $moduleLines);
    }

    $profile = ai_assistant_site_profile($pdo);
    $siteLines = [];
    $siteLines[] = "- Site name: " . trim((string)($profile['site_name'] ?? 'HIGH Q SOLID ACADEMY'));
    $siteLines[] = "- Tagline: " . trim((string)($profile['tagline'] ?? 'Always Ahead of Others'));

    if (!empty($profile)) {
        if (!empty($profile['tagline'])) {
            $siteLines[1] = "- Tagline: " . trim((string)$profile['tagline']);
        }
        if (!empty($profile['vision'])) {
            $siteLines[] = "- Vision: " . trim((string)$profile['vision']);
        }
        if (!empty($profile['about'])) {
            $siteLines[] = "- About: " . trim((string)$profile['about']);
        }
        if (!empty($profile['contact_email'])) {
            $siteLines[] = "- Contact email: " . trim((string)$profile['contact_email']);
        }
        if (!empty($profile['contact_phone'])) {
            $siteLines[] = "- Contact phone: " . trim((string)$profile['contact_phone']);
        }
        if (!empty($profile['contact_address'])) {
            $siteLines[] = "- Contact address: " . preg_replace('/\s+/', ' ', trim((string)$profile['contact_address']));
        }
        if (!empty($profile['bank_name']) || !empty($profile['bank_account_name']) || !empty($profile['bank_account_number'])) {
            $siteLines[] = "- Bank details: "
                . trim((string)($profile['bank_name'] ?? '')) . ' / '
                . trim((string)($profile['bank_account_name'] ?? '')) . ' / '
                . trim((string)($profile['bank_account_number'] ?? ''));
        }

        $registrationEnabled = array_key_exists('registration', $profile) ? ((int)$profile['registration'] === 1 ? 'enabled' : 'disabled') : 'unknown';
        $emailVerification = array_key_exists('email_verification', $profile) ? ((int)$profile['email_verification'] === 1 ? 'enabled' : 'disabled') : 'unknown';
        $maintenance = array_key_exists('maintenance', $profile) ? ((int)$profile['maintenance'] === 1 ? 'enabled' : 'disabled') : 'unknown';

        $siteLines[] = "- Registration status: {$registrationEnabled}";
        $siteLines[] = "- Email verification: {$emailVerification}";
        $siteLines[] = "- Maintenance mode: {$maintenance}";
    }

    $blocks[] = "Live site profile:\n" . implode("\n", $siteLines);

    $blocks[] = "Known public-side workflows:\n"
        . "- Current registration page in use: public/register-new.php\n"
        . "- Registrations feed the admin academic/registration workflow\n"
        . "- Payment activity can reach admin through payment records and admin notifications\n"
        . "- Public chat and contact flows are meant to surface into the admin support/chat workflow\n"
        . "- Admin notifications should be page-aware and only reach users who can access the target page";

    $blocks[] = "Supported public registration types:\n"
        . "- jamb\n"
        . "- waec\n"
        . "- postutme\n"
        . "- digital\n"
        . "- international";

    $courses = ai_assistant_active_courses($pdo, 10);
    if (!empty($courses)) {
        $courseLines = [];
        foreach ($courses as $course) {
            $line = "- " . trim((string)($course['title'] ?? 'Untitled course'));
            if (!empty($course['slug'])) {
                $line .= " [slug: " . trim((string)$course['slug']) . "]";
            }
            if (isset($course['price']) && $course['price'] !== null && $course['price'] !== '') {
                $line .= " [price: " . trim((string)$course['price']) . "]";
            }
            if (!empty($course['duration'])) {
                $line .= " [duration: " . trim((string)$course['duration']) . "]";
            }
            $courseLines[] = $line;
        }

        $blocks[] = "Active courses/programs:\n" . implode("\n", $courseLines);
    }

    $metricMap = [
        'users' => ['label' => 'Users', 'sql' => 'SELECT COUNT(*) FROM users'],
        'academic' => ['label' => 'Registrations', 'sql' => 'SELECT COUNT(*) FROM student_registrations'],
        'payments' => ['label' => 'Payments', 'sql' => 'SELECT COUNT(*) FROM payments'],
        'post' => ['label' => 'Posts', 'sql' => 'SELECT COUNT(*) FROM posts'],
        'appointments' => ['label' => 'Appointments', 'sql' => 'SELECT COUNT(*) FROM appointments'],
    ];
    $metricLines = [];
    foreach ($metricMap as $slug => $metric) {
        if (!in_array($slug, $allowedSlugs, true)) {
            continue;
        }

        $count = ai_assistant_count_safe($pdo, $metric['sql']);
        if ($count !== null) {
            $metricLines[] = "- {$metric['label']}: {$count}";
        }
    }
    if (!empty($metricLines)) {
        $blocks[] = "Quick admin metrics:\n" . implode("\n", $metricLines);
    }

    if ($context !== '') {
        $blocks[] = "Live browser context:\n" . trim($context);
    }

    return implode("\n\n", $blocks);
}

function ai_assistant_menu_title(string $slug): string {
    $catalog = ai_assistant_menu_catalog();
    return trim((string)($catalog[$slug]['title'] ?? ucwords(str_replace('_', ' ', $slug))));
}

function ai_assistant_fallback_page_help(string $slug): string {
    $map = [
        'dashboard' => 'The dashboard gives a quick overview of the admin area, counts, shortcuts, and recent operational activity.',
        'users' => 'The users page is where you manage account approvals, activation status, role assignments, and profile-level admin actions.',
        'roles' => 'The roles page controls which menu modules each role can access.',
        'settings' => 'The settings page controls site information, contact details, notifications, security toggles, and advanced operational settings.',
        'courses' => 'The courses page manages the course catalog, course details, prices, and learning-program visibility.',
        'academic' => 'The academic page is the main registration-management area for reviewing student submissions and academic records.',
        'payments' => 'The payments page is where you review payment states, receipts, confirmations, and payment-related follow-up.',
        'create_payment_link' => 'The create payment link page is used to generate payment requests for students or applicants.',
        'post' => 'The news and blog page is where admins draft, edit, publish, and manage public posts.',
        'comments' => 'The comments page is for moderation and review of public comment activity.',
        'chat' => 'The chat page is for responding to support messages and managing live user conversations.',
        'appointments' => 'The appointments page is for reviewing and managing booked meeting or consultation requests.',
        'ai_assistant' => 'The AI assistant page introduces HighQ AI and gives access to provider settings and the review queue.',
        'ai_queue' => 'The AI review queue holds sensitive AI-proposed actions for manual review before execution.',
        'ai_provider' => 'The AI provider settings page manages which AI backend is active and whether the assistant is enabled.',
        'audit_logs' => 'The audit logs page lets you trace what admins have done and when they did it.',
    ];

    return $map[$slug] ?? ('The ' . ai_assistant_menu_title($slug) . ' page is one of the admin modules available to this role.');
}

function ai_assistant_local_fallback(string $question, string $roleName, array $allowedSlugs, string $context = '', ?PDO $pdo = null, ?string $reason = null): array {
    $q = strtolower(trim($question));
    $siteProfile = ai_assistant_site_profile($pdo);
    $siteName = trim((string)($siteProfile['site_name'] ?? 'HIGH Q SOLID ACADEMY'));
    $prefix = "HighQ AI provider is unavailable right now, so I'm answering from the built-in {$siteName} admin knowledge.\n\n";

    if (preg_match('/what can you do|help me|capabilities|what do you do/', $q)) {
        $answer = $prefix
            . "I can still help with:\n"
            . "- explaining admin pages and workflows\n"
            . "- guiding registrations, payments, chat, and settings questions\n"
            . "- summarising what a role can access\n"
            . "- drafting posts, announcements, support replies, and admin messages\n"
            . "- suggesting the next admin page or action to use\n\n"
            . "Your current role is `{$roleName}` and your accessible modules are: " . implode(', ', $allowedSlugs) . '.';
        return ['answer' => $answer, 'provider' => 'local_fallback', 'model' => 'highq-knowledge'];
    }

    if (str_contains($q, 'register-new') || str_contains($q, 'registration') || str_contains($q, 'register')) {
        $answer = $prefix
            . "The active public registration flow is `public/register-new.php`.\n\n"
            . "HighQ currently supports these registration types:\n"
            . "- jamb\n- waec\n- postutme\n- digital\n- international\n\n"
            . "Those submissions are meant to flow into the admin academic/registration workflow, with payment and notification follow-up handled from the admin side.";
        return ['answer' => $answer, 'provider' => 'local_fallback', 'model' => 'highq-knowledge'];
    }

    if (str_contains($q, 'draft') || str_contains($q, 'post') || str_contains($q, 'blog') || str_contains($q, 'announcement')) {
        $topic = 'HighQ update';
        if (preg_match('/(?:draft|write|create)\s+(?:a\s+)?(?:post|announcement|blog post)\s+(?:for|about)\s+(.+)/i', $question, $m)) {
            $topic = trim($m[1]);
        }

        $answer = $prefix
            . "Here is a clean admin-ready draft format you can use.\n\n"
            . "**Title**\n"
            . ucfirst($topic) . "\n\n"
            . "**Short Summary**\n"
            . "A concise update for students, parents, or visitors about {$topic}.\n\n"
            . "**Main Post**\n"
            . "We are pleased to share an update about {$topic}. This notice is to help our community stay informed and take the right next step where needed. For the latest guidance, kindly follow the instructions shared through our official HighQ channels and admin-approved notices.\n\n"
            . "**Suggested CTA**\n"
            . "Contact HighQ for guidance or proceed through the relevant registration, payment, or support channel.\n\n"
            . "**Suggested Tags**\n"
            . "highq, updates, students, academy";
        return ['answer' => $answer, 'provider' => 'local_fallback', 'model' => 'highq-knowledge'];
    }

    foreach ($allowedSlugs as $slug) {
        $title = strtolower(ai_assistant_menu_title($slug));
        if (str_contains($q, $slug) || ($title !== '' && str_contains($q, $title))) {
            $answer = $prefix . ai_assistant_fallback_page_help($slug);
            return ['answer' => $answer, 'provider' => 'local_fallback', 'model' => 'highq-knowledge'];
        }
    }

    $answer = $prefix
        . "I can still answer HighQ admin questions even without the live provider.\n\n"
        . "Try asking about one of these areas:\n"
        . "- registrations and the academic page\n"
        . "- payments and confirmation flow\n"
        . "- users, roles, and settings\n"
        . "- chat support or appointments\n"
        . "- drafting a post or announcement\n\n"
        . "Accessible modules for this role: " . implode(', ', $allowedSlugs) . '.';

    if (!empty($reason)) {
        $answer .= "\n\nTechnical note: " . $reason;
    }

    return ['answer' => $answer, 'provider' => 'local_fallback', 'model' => 'highq-knowledge'];
}

function ai_assistant_system_prompt(string $roleName, array $allowedSlugs): string {
    $allowed = implode(', ', $allowedSlugs);

    return "You are HighQ AI.\n"
        . "You are the site-aware admin copilot for HIGH Q SOLID ACADEMY.\n"
        . "Current admin role: {$roleName}.\n"
        . "Allowed modules for this role: {$allowed}.\n\n"
        . "What you can do:\n"
        . "- Explain what each admin page does and how it fits the site workflow\n"
        . "- Answer questions about site settings, payments, users, courses, posts, registrations, roles, and support activity\n"
        . "- Summarize logs, alerts, metrics, and system activity using the supplied HighQ context\n"
        . "- Draft support replies, admin replies, announcements, blog posts, and operational updates\n"
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
        . "Answering rules:\n"
        . "- Use the provided HighQ knowledge context first before guessing\n"
        . "- If a fact is missing, say that clearly instead of inventing it\n"
        . "- When asked about an admin page, mention the likely page slug or menu entry when helpful\n"
        . "- Keep answers practical, concise, and admin-friendly\n"
        . "- If requested action is out of role scope, explain why and suggest a safe next step\n\n"
        . "When drafting posts or content:\n"
        . "- Default to a polished admin-ready draft with clear sections\n"
        . "- Prefer this structure unless the user asks otherwise: Title, Short Summary, Main Post, Suggested CTA, Suggested Tags\n"
        . "- Keep claims aligned with known HighQ details and avoid making up offers, prices, or dates.";
}

function ai_assistant_query(string $question, string $context, string $roleName, array $allowedSlugs, ?PDO $pdo = null): array {
    $provider = ai_assistant_pick_provider($pdo);
    if (($provider['provider'] ?? 'none') === 'none') {
        throw new RuntimeException('AI assistant provider is not configured in environment variables.');
    }

    $systemPrompt = ai_assistant_system_prompt($roleName, $allowedSlugs);
    $knowledgeContext = ai_assistant_site_knowledge($roleName, $allowedSlugs, $context, $pdo);
    $userPrompt = trim($question);
    if ($knowledgeContext !== '') {
        $userPrompt .= "\n\nHighQ context:\n" . $knowledgeContext;
    }

    try {
        if ($provider['provider'] === 'service') {
            $response = ai_assistant_http_json(
                $provider['url'],
                [
                    'question' => $question,
                    'context' => $knowledgeContext,
                    'role_name' => $roleName,
                    'allowed_modules' => $allowedSlugs,
                    'assistant_name' => 'HighQ AI',
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
                    'X-Title: HighQ AI',
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
    } catch (Throwable $e) {
        return ai_assistant_local_fallback($question, $roleName, $allowedSlugs, $context, $pdo, $e->getMessage());
    }
}
