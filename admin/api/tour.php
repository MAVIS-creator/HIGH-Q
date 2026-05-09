<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

ensureAuthenticated();

$userId = (int)($_SESSION['user']['id'] ?? 0);
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$roleName = (string)($_SESSION['user']['role_name'] ?? 'Admin');
$roleSlug = (string)($_SESSION['user']['role_slug'] ?? 'admin');

$action = strtolower((string)($_GET['action'] ?? $_POST['action'] ?? 'status'));

$allowedSlugs = getRoleMenuPermissions($pdo, $roleId);
if (!in_array('dashboard', $allowedSlugs, true)) {
    $allowedSlugs[] = 'dashboard';
}

$roleTourContent = [
    'admin' => [
        'dashboard' => 'Start here for full academy oversight, health checks, and the quickest path to every control panel.',
        'users' => 'Manage all account types, approvals, and role assignments from this section.',
        'roles' => 'Define access control and decide which modules each role can see.',
        'settings' => 'Use this area to control site-wide behavior, support settings, and operational limits.',
        'courses' => 'Create and maintain the course catalog for the academy.',
        'payments' => 'Review payment flow, statuses, and confirmation actions.',
        'audit_logs' => 'Use audit logs to review admin activity and trace important changes.',
        'ai_assistant' => 'Use the assistant to explain admin actions, summarize activity, and prepare safe review tasks.',
    ],
    'sub-admin' => [
        'dashboard' => 'This dashboard gives you an overview of the sections you are allowed to manage.',
        'users' => 'Handle approved users, profiles, and supported account changes.',
        'courses' => 'Maintain course records and related academy content where permitted.',
        'payments' => 'Review payment records and follow confirmation workflows you are allowed to use.',
        'chat' => 'Respond to support threads and keep customer conversations moving.',
        'comments' => 'Moderate comments and keep public activity clean.',
        'ai_assistant' => 'Ask for explanations or safe drafts that stay inside your allowed modules.',
    ],
    'moderator' => [
        'dashboard' => 'Use the dashboard for a quick operational overview of moderation-related activity.',
        'comments' => 'This is your primary moderation area for pending and live comments.',
        'chat' => 'Monitor support chats and help resolve user questions safely.',
        'post' => 'Review news or blog content before it goes live.',
        'audit_logs' => 'Check logs when you need to review moderation history.',
        'ai_assistant' => 'Ask the assistant to summarize comments, chats, or moderation notes.',
    ],
    'student' => [
        'dashboard' => 'This dashboard shows your available student actions and account overview.',
        'payments' => 'View payment-related information and track your account status.',
        'courses' => 'Browse available course information where permitted.',
        'ai_assistant' => 'Ask for help understanding your account, payments, or available actions.',
    ],
    'applicant' => [
        'dashboard' => 'Use the dashboard to see your onboarding status and next steps.',
        'payments' => 'Review payment instructions or confirmation-related information.',
        'ai_assistant' => 'Ask for help understanding registration, payment, or onboarding steps.',
    ],
];

$fallbackRoleTourContent = [
    'dashboard' => 'Start here to view the pages and controls you are allowed to access.',
    'users' => 'Manage user accounts, activation status, and role assignments.',
    'roles' => 'Configure role access and module permissions.',
    'settings' => 'Update core site configuration and operational controls.',
    'courses' => 'Manage courses and learning offerings.',
    'tutors' => 'Manage tutor records and profile visibility.',
    'academic' => 'Handle academic operations and related management tasks.',
    'payments' => 'Review payment states and transaction records.',
    'create_payment_link' => 'Generate payment links for manual payment requests.',
    'post' => 'Create and manage news/blog content updates.',
    'comments' => 'Moderate pending comments and comment flow.',
    'chat' => 'Respond to support messages and threaded chats.',
    'appointments' => 'Manage appointment requests and scheduling.',
    'audit_logs' => 'Review traceable actions performed in admin workflows.',
    'sentinel' => 'Run and review security scan checks.',
    'automator' => 'Use controlled automation tools for repetitive operations.',
    'trap' => 'Monitor canary-trap events and suspicious activity markers.',
    'ai_assistant' => 'Ask for explanations, summaries, and safe automation suggestions.',
];

$roleKey = strtolower($roleSlug ?: $roleName);
$roleContent = $roleTourContent[$roleKey] ?? [];

if ($action === 'status') {
    $state = getUserOnboardingTourState($pdo, $userId);

    $show = (bool)($state['pending'] ?? true);
    if (!empty($state['completed_at'])) {
        $show = false;
    }

    // Session fallback for post-signup trigger.
    if (!empty($_SESSION['onboarding_tour']['show_after_signup'])) {
        $show = true;
    }

    $steps = [];
    $steps[] = [
        'title' => 'Welcome, ' . $roleName,
        'intro' => 'This tour is role-based. You will only see modules your account can access.',
        'selector' => '.sidebar-logo',
    ];

    foreach ($allowedSlugs as $slug) {
        $intro = $roleContent[$slug] ?? $fallbackRoleTourContent[$slug] ?? null;
        if ($intro === null) continue;

        $steps[] = [
            'title' => ucwords(str_replace('_', ' ', $slug)),
            'intro' => $intro,
            'slug' => $slug,
            'selector' => 'a[data-menu-slug="' . $slug . '"]',
        ];
    }

    $steps[] = [
        'title' => 'Profile and Notifications',
        'intro' => 'Use the bell icon and profile menu for alerts and account settings.',
        'slug' => '',
        'selector' => '#notifBtn',
    ];

    echo json_encode([
        'status' => 'ok',
        'show_tour' => $show,
        'role' => $roleName,
        'role_slug' => $roleSlug,
        'allowed_modules' => array_values($allowedSlugs),
        'steps' => $steps,
    ]);
    exit;
}

if (!in_array($action, ['start', 'complete', 'skip', 'restart'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unsupported action']);
    exit;
}

$csrf = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verifyToken('tour_api', (string)$csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$ok = false;
if ($action === 'start') {
    $ok = updateUserOnboardingTourState($pdo, $userId, [
        'started_at' => date('Y-m-d H:i:s'),
        'pending' => true,
    ]);
    $_SESSION['onboarding_tour']['pending'] = true;
} elseif ($action === 'complete') {
    $ok = updateUserOnboardingTourState($pdo, $userId, [
        'pending' => false,
        'completed_at' => date('Y-m-d H:i:s'),
    ]);
    $_SESSION['onboarding_tour']['pending'] = false;
    $_SESSION['onboarding_tour']['completed_at'] = date('Y-m-d H:i:s');
} elseif ($action === 'skip') {
    $ok = updateUserOnboardingTourState($pdo, $userId, [
        'pending' => false,
    ]);
    $_SESSION['onboarding_tour']['pending'] = false;
} elseif ($action === 'restart') {
    $ok = updateUserOnboardingTourState($pdo, $userId, [
        'pending' => true,
        'started_at' => null,
        'completed_at' => null,
    ]);
    $_SESSION['onboarding_tour']['pending'] = true;
    $_SESSION['onboarding_tour']['completed_at'] = null;
}

logAction($pdo, $userId, 'tour_' . $action, [
    'role' => $roleName,
    'db_updated' => $ok,
]);

echo json_encode([
    'status' => 'ok',
    'action' => $action,
    'db_updated' => $ok,
]);
