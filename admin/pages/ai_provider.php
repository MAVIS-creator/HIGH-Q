<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requirePermission('settings');

$pageTitle = 'AI Provider Settings';
$pageSubtitle = 'Select runtime provider and model override for admin assistant';
$csrf = function_exists('generateToken') ? generateToken('ai_provider_settings_api') : '';
?>

<div class="dashboard-container">
    <div class="page-hero">
        <div class="page-hero-content">
            <div>
                <span class="page-hero-badge"><i class='bx bx-slider-alt'></i> AI Provider</span>
                <h1 class="page-hero-title">AI Provider Settings</h1>
                <p class="page-hero-subtitle">Configure runtime provider preference while keeping API keys in environment variables.</p>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-info-circle'></i> Configuration Rules</h3>
        </div>
        <div class="admin-card-body">
            <ul style="margin:0;padding-left:18px;line-height:1.8;">
                <li>Secrets remain in environment variables; this page stores only provider preference and model override.</li>
                <li>When provider is <strong>env_auto</strong>, runtime picks the first available provider in configured order.</li>
                <li>Disabling assistant here blocks AI responses without removing credentials.</li>
            </ul>
        </div>
    </div>

    <div class="admin-card" style="margin-top:16px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-cog'></i> Runtime Settings</h3>
        </div>
        <div class="admin-card-body">
            <form id="aiProviderForm" class="d-grid" style="gap:12px;max-width:760px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="aiEnabled" name="enabled" value="1" checked>
                    <label class="form-check-label" for="aiEnabled">Enable AI Assistant</label>
                </div>

                <label class="form-label" for="providerSelect">Provider Mode</label>
                <select id="providerSelect" name="provider" class="form-select">
                    <option value="env_auto">env_auto (recommended)</option>
                    <option value="service">service (internal endpoint)</option>
                    <option value="groq">groq</option>
                    <option value="openrouter">openrouter</option>
                    <option value="gemini">gemini</option>
                </select>

                <label class="form-label" for="serviceUrl">Service URL override</label>
                <input id="serviceUrl" name="service_url" class="form-control" placeholder="https://internal-ai-service.local/api/assist">

                <label class="form-label" for="modelOverride">Model override (optional)</label>
                <input id="modelOverride" name="model_override" class="form-control" placeholder="e.g. llama-3.3-70b-versatile">

                <div>
                    <button type="submit" class="btn btn-dark"><i class='bx bx-save'></i> Save Provider Settings</button>
                </div>
            </form>

            <div id="aiProviderAvailability" class="mt-4"></div>
            <div id="aiProviderResult" class="mt-3" style="display:none;">
                <div class="alert alert-secondary" role="alert">
                    <strong id="aiProviderResultLabel">Result</strong>
                    <div id="aiProviderResultMessage" style="margin-top:8px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const adminBase = (window.HQ_ADMIN_PATH || '').replace(/\/$/, '');
    const settingsUrl = adminBase ? adminBase + '/api/ai_provider_settings.php' : '../api/ai_provider_settings.php';

    const form = document.getElementById('aiProviderForm');
    const availabilityEl = document.getElementById('aiProviderAvailability');
    const resultBox = document.getElementById('aiProviderResult');
    const resultLabel = document.getElementById('aiProviderResultLabel');
    const resultMsg = document.getElementById('aiProviderResultMessage');

    async function loadSettings() {
        try {
            const res = await fetch(settingsUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'ok') throw new Error(data.message || 'Load failed');

            const cfg = data.settings || {};
            document.getElementById('aiEnabled').checked = Number(cfg.enabled) === 1;
            document.getElementById('providerSelect').value = cfg.provider || 'env_auto';
            document.getElementById('serviceUrl').value = cfg.service_url || '';
            document.getElementById('modelOverride').value = cfg.model_override || '';

            const available = data.available || {};
            const chips = Object.keys(available).map(function (k) {
                const ok = !!available[k];
                const cls = ok ? 'bg-success' : 'bg-secondary';
                const txt = ok ? 'available' : 'not configured';
                return `<span class="badge ${cls}" style="margin-right:6px;">${k}: ${txt}</span>`;
            }).join('');
            availabilityEl.innerHTML = `<div><strong>Environment availability:</strong></div><div style="margin-top:8px;">${chips}</div>`;
        } catch (err) {
            availabilityEl.innerHTML = '<div class="text-danger">Unable to load provider availability.</div>';
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Saving...";
        }

        try {
            const fd = new FormData(form);
            if (!document.getElementById('aiEnabled').checked) {
                fd.delete('enabled');
            }
            const res = await fetch(settingsUrl, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'ok') throw new Error(data.message || 'Save failed');

            resultLabel.textContent = 'Saved';
            resultMsg.textContent = data.message || 'Settings saved';
            resultBox.style.display = 'block';
            loadSettings();
        } catch (err) {
            resultLabel.textContent = 'Error';
            resultMsg.textContent = err.message || 'Unable to save settings';
            resultBox.style.display = 'block';
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = "<i class='bx bx-save'></i> Save Provider Settings";
            }
        }
    });

    loadSettings();
})();
</script>
