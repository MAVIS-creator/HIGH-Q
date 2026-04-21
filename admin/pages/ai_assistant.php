<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('ai_assistant');

$pageTitle = 'AI Assistant';
$pageSubtitle = 'Role-aware explanations, summaries, and safe automation suggestions';
$csrf = function_exists('generateToken') ? generateToken('ai_assistant_api') : '';
$actionCsrf = function_exists('generateToken') ? generateToken('ai_action_api') : '';
?>

<div class="dashboard-container">
    <div class="page-hero">
        <div class="page-hero-content">
            <div>
                <span class="page-hero-badge"><i class='bx bx-bot'></i> AI Assistant</span>
                <h1 class="page-hero-title">Admin AI Assistant</h1>
                <p class="page-hero-subtitle">Explain pages, summarize operations, and suggest safe automations for review.</p>
            </div>
        </div>
    </div>

    <div class="admin-card" style="margin-bottom:16px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-shield-quarter'></i> Safety Rules</h3>
        </div>
        <div class="admin-card-body">
            <ul style="margin:0;padding-left:18px;line-height:1.8;">
                <li>All write actions require explicit admin confirmation.</li>
                <li>AI cannot bypass role permissions or expose out-of-scope data.</li>
                <li>Dangerous operations are never auto-executed.</li>
                <li>AI requests and lifecycle events are audit-logged.</li>
            </ul>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-message-dots'></i> Ask Assistant</h3>
        </div>
        <div class="admin-card-body">
            <form id="aiAssistantForm" class="d-grid" style="gap:12px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <label for="aiQuestion" class="form-label">Question</label>
                <textarea id="aiQuestion" name="question" class="form-control" rows="4" placeholder="Ask about settings, users, roles, payments, logs, or request a safe automation suggestion..." required></textarea>

                <label for="aiContext" class="form-label">Optional Context</label>
                <textarea id="aiContext" name="context" class="form-control" rows="5" placeholder="Paste a relevant error, log fragment, or admin context"></textarea>

                <div>
                    <button type="submit" class="btn btn-dark">
                        <i class='bx bx-send'></i> Ask AI Assistant
                    </button>
                </div>
            </form>

            <div id="aiAssistantResult" class="mt-4" style="display:none;">
                <div class="alert alert-secondary" role="alert">
                    <strong id="aiMetaLabel">Assistant response</strong>
                    <div id="aiAnswer" style="white-space:pre-wrap;margin-top:10px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card" style="margin-top:16px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-task'></i> Confirmed Suggestions</h3>
        </div>
        <div class="admin-card-body">
            <p style="margin-top:0;">When the assistant suggests a safe follow-up task, review it here and queue it for human review.</p>
            <form id="aiActionForm" class="d-grid" style="gap:12px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($actionCsrf) ?>">
                <input type="hidden" name="confirmed" value="1">
                <label for="actionType" class="form-label">Action Type</label>
                <input id="actionType" name="action_type" class="form-control" value="manual_review" placeholder="manual_review">

                <label for="proposalText" class="form-label">Proposal</label>
                <textarea id="proposalText" name="proposal" class="form-control" rows="4" placeholder="Paste the assistant's suggested action here" required></textarea>

                <label for="actionContext" class="form-label">Context</label>
                <textarea id="actionContext" name="context" class="form-control" rows="3" placeholder="Optional context for the queued review"></textarea>

                <div>
                    <button type="submit" class="btn btn-warning">
                        <i class='bx bx-check-shield'></i> Queue For Review
                    </button>
                </div>
            </form>

            <div id="aiActionResult" class="mt-3" style="display:none;">
                <div class="alert alert-info" role="alert">
                    <strong id="aiActionMeta">Action result</strong>
                    <div id="aiActionMessage" style="white-space:pre-wrap;margin-top:10px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const adminBase = (window.HQ_ADMIN_PATH || '').replace(/\/$/, '');
    const apiBase = adminBase ? adminBase + '/api' : '../api';
    const form = document.getElementById('aiAssistantForm');
    const resultBox = document.getElementById('aiAssistantResult');
    const answerEl = document.getElementById('aiAnswer');
    const metaEl = document.getElementById('aiMetaLabel');
    const actionForm = document.getElementById('aiActionForm');
    const actionResult = document.getElementById('aiActionResult');
    const actionMeta = document.getElementById('aiActionMeta');
    const actionMessage = document.getElementById('aiActionMessage');

    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Processing...";
        }

        try {
            const response = await fetch(apiBase + '/ai_assistant.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            if (!response.ok || data.status !== 'ok') {
                throw new Error(data.message || 'Request failed');
            }

            metaEl.textContent = `Assistant response (${data.provider || 'provider'} / ${data.model || 'model'})`;
            answerEl.textContent = data.answer || 'No response';
            resultBox.style.display = 'block';

            const proposalText = document.getElementById('proposalText');
            if (proposalText && data.answer) {
                proposalText.value = data.answer;
            }
        } catch (err) {
            metaEl.textContent = 'Assistant response';
            answerEl.textContent = 'Unable to complete request right now. ' + (err.message || 'Unknown error');
            resultBox.style.display = 'block';
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = "<i class='bx bx-send'></i> Ask AI Assistant";
            }
        }
    });

    if (actionForm) {
        actionForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(actionForm);
            const btn = actionForm.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Queueing...";
            }

            try {
                const response = await fetch(apiBase + '/ai_action.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (!response.ok || data.status !== 'ok') {
                    throw new Error(data.message || 'Request failed');
                }

                actionMeta.textContent = 'Action queued';
                actionMessage.textContent = `${data.message || 'Queued for review.'} Queue ID: ${data.queue_id || 'n/a'}`;
                actionResult.style.display = 'block';
            } catch (err) {
                actionMeta.textContent = 'Action result';
                actionMessage.textContent = 'Unable to queue proposal right now. ' + (err.message || 'Unknown error');
                actionResult.style.display = 'block';
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = "<i class='bx bx-check-shield'></i> Queue For Review";
                }
            }
        });
    }
})();
</script>
