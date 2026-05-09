<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requirePermission('ai_queue');

$pageTitle = 'AI Review Queue';
$pageSubtitle = 'Approve or reject AI-proposed tasks before any action is taken';
$csrf = function_exists('generateToken') ? generateToken('ai_action_review_api') : '';
$executeCsrf = function_exists('generateToken') ? generateToken('ai_action_execute_api') : '';

$items = [];
try {
    $stmt = $pdo->query("SELECT q.*, u.name AS user_name, u.email AS user_email, r.name AS role_name
                         FROM ai_action_queue q
                         LEFT JOIN users u ON u.id = q.user_id
                         LEFT JOIN roles r ON r.id = u.role_id
                         ORDER BY q.created_at DESC
                         LIMIT 100");
    $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $items = [];
}
?>

<div class="dashboard-container">
    <div class="page-hero">
        <div class="page-hero-content">
            <div>
                <span class="page-hero-badge"><i class='bx bx-list-check'></i> AI Review Queue</span>
                <h1 class="page-hero-title">AI Review Queue</h1>
                <p class="page-hero-subtitle">Review assistant suggestions before they are approved for follow-up handling.</p>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-shield-quarter'></i> Review Rules</h3>
        </div>
        <div class="admin-card-body">
            <ul style="margin:0;padding-left:18px;line-height:1.8;">
                <li>AI suggestions are never executed automatically.</li>
                <li>Every queue decision is logged for auditability.</li>
                <li>Review actions update the queue status only.</li>
            </ul>
        </div>
    </div>

    <div class="admin-card" style="margin-top:16px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-receipt'></i> Pending Suggestions</h3>
        </div>
        <div class="admin-card-body">
            <?php if (empty($items)): ?>
                <p style="margin:0;">No AI proposals are waiting for review.</p>
            <?php else: ?>
                <?php
                    $statusCounts = [
                        'all' => count($items),
                        'queued' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                        'executed' => 0,
                    ];
                    foreach ($items as $row) {
                        $st = strtolower((string)($row['status'] ?? 'queued'));
                        if (!isset($statusCounts[$st])) {
                            $statusCounts[$st] = 0;
                        }
                        $statusCounts[$st]++;
                    }
                ?>
                <div id="aiQueueFilters" class="mb-3" style="display:flex;flex-wrap:wrap;gap:8px;">
                    <button type="button" class="btn btn-sm btn-dark ai-filter-btn" data-status="all">All (<?= (int)$statusCounts['all'] ?>)</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ai-filter-btn" data-status="queued">Queued (<?= (int)$statusCounts['queued'] ?>)</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ai-filter-btn" data-status="approved">Approved (<?= (int)$statusCounts['approved'] ?>)</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ai-filter-btn" data-status="rejected">Rejected (<?= (int)$statusCounts['rejected'] ?>)</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ai-filter-btn" data-status="executed">Executed (<?= (int)$statusCounts['executed'] ?>)</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Proposal</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr class="ai-queue-row" data-status="<?= htmlspecialchars(strtolower((string)($item['status'] ?? 'queued'))) ?>">
                                    <td><?= (int)$item['id'] ?></td>
                                    <td><?= htmlspecialchars($item['action_type'] ?? '') ?></td>
                                    <td style="max-width:420px;white-space:normal;"><?= nl2br(htmlspecialchars($item['proposal'] ?? '')) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($item['user_name'] ?? 'Unknown') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($item['role_name'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <?php
                                            $status = (string)($item['status'] ?? 'queued');
                                            $badge = 'bg-secondary';
                                            if ($status === 'approved') $badge = 'bg-success';
                                            if ($status === 'rejected') $badge = 'bg-danger';
                                            if ($status === 'executed') $badge = 'bg-primary';
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                    <td style="max-width:280px;white-space:normal;">
                                        <?php
                                            $reviewNote = trim((string)($item['review_note'] ?? ''));
                                            $executionNote = trim((string)($item['execution_note'] ?? ''));
                                        ?>
                                        <?php if ($reviewNote !== ''): ?>
                                            <div><strong>Review:</strong> <?= nl2br(htmlspecialchars($reviewNote)) ?></div>
                                        <?php endif; ?>
                                        <?php if ($executionNote !== ''): ?>
                                            <div style="margin-top:6px;"><strong>Execution:</strong> <?= nl2br(htmlspecialchars($executionNote)) ?></div>
                                        <?php endif; ?>
                                        <?php if ($reviewNote === '' && $executionNote === ''): ?>
                                            <span class="text-muted">No notes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($item['status'] ?? '') === 'queued'): ?>
                                            <form class="ai-note-form" onsubmit="return false;" style="margin-bottom:6px;">
                                                <input type="text" class="form-control form-control-sm ai-note-input" maxlength="255" placeholder="Optional review note (audit log)">
                                            </form>
                                            <form class="d-inline ai-review-form" method="post" action="../api/ai_action_review.php" style="margin-right:6px;">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                <input type="hidden" name="queue_id" value="<?= (int)$item['id'] ?>">
                                                <input type="hidden" name="decision" value="approved">
                                                <input type="hidden" name="note" value="">
                                                <button class="btn btn-success btn-sm" type="submit"><i class='bx bx-check'></i> Approve</button>
                                            </form>
                                            <form class="d-inline ai-review-form" method="post" action="../api/ai_action_review.php">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                <input type="hidden" name="queue_id" value="<?= (int)$item['id'] ?>">
                                                <input type="hidden" name="decision" value="rejected">
                                                <input type="hidden" name="note" value="">
                                                <button class="btn btn-danger btn-sm" type="submit"><i class='bx bx-x'></i> Reject</button>
                                            </form>
                                        <?php elseif (($item['status'] ?? '') === 'approved'): ?>
                                            <form class="ai-note-form" onsubmit="return false;" style="margin-bottom:6px;">
                                                <input type="text" class="form-control form-control-sm ai-note-input" maxlength="255" placeholder="Optional execution note (audit log)">
                                            </form>
                                            <form class="d-inline ai-execute-form" method="post" action="../api/ai_action_execute.php" style="margin-right:6px;">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($executeCsrf) ?>">
                                                <input type="hidden" name="queue_id" value="<?= (int)$item['id'] ?>">
                                                <input type="hidden" name="note" value="">
                                                <button class="btn btn-primary btn-sm" type="submit"><i class='bx bx-play'></i> Mark Executed</button>
                                            </form>
                                            <span class="text-muted">Approved</span>
                                        <?php else: ?>
                                            <span class="text-muted">Reviewed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    const adminBase = (window.HQ_ADMIN_PATH || '').replace(/\/$/, '');
    const reviewUrl = adminBase ? adminBase + '/api/ai_action_review.php' : '../api/ai_action_review.php';
    const executeUrl = adminBase ? adminBase + '/api/ai_action_execute.php' : '../api/ai_action_execute.php';
    const filterButtons = document.querySelectorAll('.ai-filter-btn');
    const queueRows = document.querySelectorAll('.ai-queue-row');

    function syncActionNote(form) {
        const row = form.closest('tr');
        if (!row) return;
        const noteInput = row.querySelector('.ai-note-input');
        const hiddenNote = form.querySelector('input[name="note"]');
        if (hiddenNote && noteInput) {
            hiddenNote.value = (noteInput.value || '').trim();
        }
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const status = btn.getAttribute('data-status') || 'all';
            queueRows.forEach(function (row) {
                const rowStatus = row.getAttribute('data-status') || 'queued';
                row.style.display = (status === 'all' || rowStatus === status) ? '' : 'none';
            });

            filterButtons.forEach(function (other) {
                other.classList.remove('btn-dark');
                other.classList.remove('btn-outline-dark');
                if (!other.classList.contains('btn-outline-secondary')) {
                    other.classList.add('btn-outline-secondary');
                }
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-dark');
        });
    });

    const forms = document.querySelectorAll('.ai-review-form');
    const executeForms = document.querySelectorAll('.ai-execute-form');
    forms.forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            syncActionNote(form);
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
            }
            try {
                const response = await fetch(reviewUrl, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'ok') {
                    throw new Error(data.message || 'Unable to update queue item');
                }
                window.location.reload();
            } catch (err) {
                alert(err.message || 'Unable to update queue item');
                if (btn) btn.disabled = false;
            }
        });
    });

    executeForms.forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            syncActionNote(form);
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
            }

            try {
                const response = await fetch(executeUrl, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'ok') {
                    throw new Error(data.message || 'Unable to execute queue item');
                }
                window.location.reload();
            } catch (err) {
                alert(err.message || 'Unable to execute queue item');
                if (btn) btn.disabled = false;
            }
        });
    });
})();
</script>
