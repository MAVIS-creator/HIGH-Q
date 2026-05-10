// Settings form submission handler
// Initialize immediately (works if script is loaded before or after DOMContentLoaded)
(function initSettings() {
    function start() {
        var form = document.getElementById('settingsForm');
        if (!form) return;

        // Tabs initialization (robust, works even if DOM loads late)
        (function initTabs(){
            var tabs = Array.from(document.querySelectorAll('.tab-btn'));
            var panels = Array.from(document.querySelectorAll('.tab-panel'));
            if (!tabs.length || !panels.length) return;

            function activate(tabName){
                tabs.forEach(function(tb){ tb.classList.toggle('active', tb.getAttribute('data-tab') === tabName); });
                panels.forEach(function(pn){
                    var match = pn.getAttribute('data-panel') === tabName;
                    pn.style.display = match ? 'block' : 'none';
                });
            }

            tabs.forEach(function(tb){
                tb.addEventListener('click', function(ev){
                    ev.preventDefault();
                    activate(tb.getAttribute('data-tab'));
                });
            });

            // Ensure first tab is visible on load
            var initial = tabs.find(function(t){ return t.classList.contains('active'); }) || tabs[0];
            if (initial) activate(initial.getAttribute('data-tab'));
        })();

        // Helper to parse JSON but handle HTML error pages gracefully
        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function tryParseJSONOrShowHtml(text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                // If server returned HTML, show it in a modal for debugging
                var trimmed = text.trim();
                if (trimmed && trimmed.charAt(0) === '<') {
                    var preview = trimmed.length > 3000 ? trimmed.slice(0,3000) + '\n\n(Truncated...)' : trimmed;
                    Swal.fire({
                        title: 'Server Error (HTML response)',
                        html: '<div style="text-align:left;max-height:400px;overflow:auto"><pre style="white-space:pre-wrap; word-wrap:break-word;">'+
                            escapeHtml(preview)+
                        '</pre></div>',
                        width: '80%',
                        confirmButtonText: 'OK'
                    });
                    console.error('Server returned HTML:', text);
                    return null;
                }
                throw e;
            }
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            // Show loading state
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we save your settings',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                preConfirm: () => false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Before building FormData, add explicit zero values for unchecked checkboxes
            // so that the server receives falsey values for toggles that are off.
            // Remove any previous helpers first.
            this.querySelectorAll('input.auto-zero').forEach(function(n){ n.remove(); });
            this.querySelectorAll('input[type="checkbox"]').forEach(function(cb){
                if (cb.disabled) {
                    return;
                }
                if (!cb.checked) {
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = cb.name;
                    hidden.value = '0';
                    hidden.className = 'auto-zero';
                    cb.closest('form').appendChild(hidden);
                }
            });

            var data = new FormData(this);
            var xhr = new XMLHttpRequest();
            // Submit settings to the dedicated JSON endpoint to ensure JSON-only responses
            xhr.open('POST', (window.HQ_ADMIN_BASE || '') + '/api/save-settings.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                var text = xhr.responseText || '';
                var parsed = null;
                try {
                    parsed = tryParseJSONOrShowHtml(text);
                } catch (err) {
                    Swal.fire({title:'Error',text:err.message || 'Unexpected response',icon:'error'});
                    return;
                }
                if (!parsed) return; // HTML preview was shown

                if (xhr.status === 200 && parsed.status === 'ok') {
                    Swal.fire({
                        title: parsed.title || 'Success',
                        text: parsed.message || 'Settings saved successfully',
                        icon: 'success',
                        confirmButtonColor: '#3085d6'
                    }).then(() => { location.reload(); });
                } else {
                    Swal.fire({
                        title: parsed.title || 'Error',
                        text: parsed.message || 'Failed to save settings',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            };

            xhr.onerror = function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Network error occurred while saving settings',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            };

            xhr.send(data);
        });


        // Action handlers
        function doAction(action) {
            var fd = new FormData();
            fd.append('action', action);
            fd.append('_csrf', document.querySelector('input[name="_csrf"]').value);

            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                preConfirm: () => false,
                didOpen: () => { Swal.showLoading(); }
            });

            var xhr = new XMLHttpRequest();
            // For runScan use the dedicated JSON-only endpoint to avoid HTML output from page includes
            // Use dedicated JSON endpoint for runScan, otherwise post to the admin router so pages return JSON for AJAX
            var endpoint = (action === 'runScan') ? (window.HQ_ADMIN_BASE || '') + '/api/run-scan.php' : (window.HQ_ADMIN_BASE || '') + '/index.php?pages=settings';
            xhr.open('POST', endpoint, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                var text = xhr.responseText || '';
                var parsed = null;
                try {
                    parsed = tryParseJSONOrShowHtml(text);
                } catch (err) {
                    Swal.fire({title:'Error',text:err.message || 'Unexpected response',icon:'error'});
                    return;
                }
                if (!parsed) return; // HTML preview was shown

                if (xhr.status === 200 && parsed.status === 'ok') {
                    Swal.fire({
                        title: parsed.title || 'Success',
                        text: parsed.message || 'Action completed successfully',
                        icon: 'success',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        if (action === 'clearIPs' || action === 'clearLogs' || action === 'clearHtpasswdToken') location.reload();
                    });
                } else {
                    Swal.fire({
                        title: parsed.title || 'Error',
                        text: parsed.message || 'Action failed',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            };

            xhr.onerror = function() {
                Swal.fire({ title: 'Error', text: 'Network error occurred', icon: 'error', confirmButtonColor: '#d33' });
            };

            xhr.send(fd);
        }

        // Action button handlers (guard nulls)
        var runScanBtn = document.getElementById('runScan');
        if (runScanBtn) {
            runScanBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Start Security Scan?',
                    text: 'This will scan your system for potential security issues.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, start scan'
                }).then((result) => { if (result.isConfirmed) doAction('runScan'); });
            });
        }

        var clearIPsBtn = document.getElementById('clearIPs');
        if (clearIPsBtn) {
            clearIPsBtn.addEventListener('click', function() { Swal.fire({
                title: 'Clear Blocked IPs?', text: 'This will remove all IP addresses from the block list.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'Yes, clear IPs'
            }).then((result) => { if (result.isConfirmed) doAction('clearIPs'); }); });
        }

        var clearLogsBtn = document.getElementById('clearLogs');
        if (clearLogsBtn) {
            clearLogsBtn.addEventListener('click', function() { Swal.fire({
                title: 'Clear Audit Logs?', text: 'This will clear all audit logs except seed data. This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'Yes, clear logs'
            }).then((result) => { if (result.isConfirmed) doAction('clearLogs'); }); });
        }

        var downloadLogsBtn = document.getElementById('downloadLogs');
        if (downloadLogsBtn) {
            downloadLogsBtn.addEventListener('click', function() {
                var url = (window.HQ_ADMIN_BASE || '') + '/index.php?pages=settings&action=download_logs';
                window.location.href = url;
            });
        }

        var exportClearBtn = document.getElementById('exportClear');
        if (exportClearBtn) {
            exportClearBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Export and clear logs?',
                    text: 'We will download the current audit logs first, then clear them.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, continue'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    var url = (window.HQ_ADMIN_BASE || '') + '/index.php?pages=settings&action=download_logs';
                    window.location.href = url;
                    setTimeout(function() {
                        doAction('clearLogs');
                    }, 1200);
                });
            });
        }

        var rotateHtpasswdTokenBtn = document.getElementById('rotateHtpasswdToken');
        if (rotateHtpasswdTokenBtn) {
            rotateHtpasswdTokenBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Generate new reset token?',
                    text: 'This replaces the current hosted reset token immediately.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Generate token'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    var fd = new FormData();
                    fd.append('action', 'rotateHtpasswdToken');
                    fd.append('_csrf', document.querySelector('input[name="_csrf"]').value);

                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', (window.HQ_ADMIN_BASE || '') + '/api/save-settings.php', true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.onload = function() {
                        var parsed = null;
                        try { parsed = JSON.parse(xhr.responseText || '{}'); } catch (e) {}
                        if (xhr.status === 200 && parsed && parsed.status === 'ok' && parsed.token) {
                            var statusNode = document.getElementById('htpasswdTokenStatus');
                            var updatedNode = document.getElementById('htpasswdTokenUpdated');
                            if (statusNode) statusNode.textContent = 'Configured';
                            if (updatedNode) updatedNode.textContent = ' | Updated: ' + (parsed.updated_at || 'just now');
                            var resetUrl = (window.HQ_ADMIN_BASE || '') + '/reset_htpasswd.php';
                            Swal.fire({
                                title: 'Reset Token Ready',
                                html: '<p style="margin-bottom:12px;">Copy this token now. For security, it will not be shown again.</p><textarea readonly style="width:100%;min-height:110px;padding:10px;border:1px solid #ccc;border-radius:8px;">' + escapeHtml(parsed.token) + '</textarea><p style="margin-top:12px;font-size:0.92rem;">Then open <code>' + escapeHtml(resetUrl) + '</code> and paste the token into the reset form.</p>',
                                icon: 'success',
                                confirmButtonText: 'Done'
                            });
                        } else {
                            Swal.fire('Error', (parsed && parsed.message) ? parsed.message : 'Could not generate reset token.', 'error');
                        }
                    };
                    xhr.onerror = function() {
                        Swal.fire('Error', 'Network error occurred while generating the token.', 'error');
                    };
                    xhr.send(fd);
                });
            });
        }

        var clearHtpasswdTokenBtn = document.getElementById('clearHtpasswdToken');
        if (clearHtpasswdTokenBtn) {
            clearHtpasswdTokenBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Disable hosted reset token?',
                    text: 'This removes the settings-managed token. Localhost reset will still work.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Disable token'
                }).then(function(result) {
                    if (result.isConfirmed) doAction('clearHtpasswdToken');
                });
            });
        }
    }

    // Try to start now, or wait for DOM if not ready
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
