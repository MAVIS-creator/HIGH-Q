<?php
// Admin Automator page - Tailwind redesign
$pageTitle = 'Automator';
$pageSubtitle = 'SEO and maintenance automation.';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requirePermission('automator');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
    <div class="min-h-screen max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-500 via-violet-500 to-purple-500 p-8 shadow-xl text-white">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.12),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(255,255,255,0.12),transparent_25%),radial-gradient(circle_at_50%_80%,rgba(255,255,255,0.1),transparent_30%)]"></div>
            <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-indigo-100/80">Automation Suite</p>
                    <h1 class="mt-2 text-3xl sm:text-4xl font-semibold leading-tight"><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="mt-2 text-indigo-50/90 max-w-2xl"><?= htmlspecialchars($pageSubtitle) ?></p>
                </div>
                <div class="flex items-center gap-2 text-sm bg-white/10 backdrop-blur-md border border-white/20 rounded-full px-4 py-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                    <span class="text-indigo-50">Live Automation Ready</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-slate-500">Automation Workspace</p>
                    <h2 class="text-lg font-semibold text-slate-800">Workflow Console</h2>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>Embedded module</span>
                </div>
            </div>
            <div class="bg-slate-900/90">
                <iframe src="../modules/automator.php" class="w-full h-[780px] md:h-[820px] border-0"></iframe>
            </div>
        </div>
    </div>
</body>
</html>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
