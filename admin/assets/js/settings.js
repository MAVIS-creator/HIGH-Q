// Settings form submission handler
// Initialize immediately (works if script is loaded before or after DOMContentLoaded)
(function initSettings() {
    function start() {
        var form = document.getElementById('settingsForm');
        if (!form) return;

        // Tabs initialization (if present)
        document.querySelectorAll('.tab-btn').forEach(function(b){
            b.addEventListener('click', function(){
                document.querySelectorAll('.tab-btn').forEach(x=>x.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(x=>x.style.display='none');
                b.classList.add('active');
                var t = b.getAttribute('data-tab');
                var panel = document.querySelector('.tab-panel[data-panel="'+t+'"]');
                if (panel) panel.style.display='block';
            });
        });

        // Helper to parse JSON but handle HTML error pages gracefully
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
                            Swal.escapeHtml(preview)+
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

    form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Show loading state
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we save your settings',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Before building FormData, add explicit zero values for unchecked checkboxes
            // so that the server receives falsey values for toggles that are off.
            // Remove any previous helpers first.
            this.querySelectorAll('input.auto-zero').forEach(function(n){ n.remove(); });
            this.querySelectorAll('input[type="checkbox"]').forEach(function(cb){
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
            // Use hqFetch so auth errors are handled centrally (redirect with return_to)
            // Prefer the compatibility wrapper (which calls hqFetch internally if present)
            const endpoint = (typeof window.adminApi === 'function') ? window.adminApi('save-settings.php') : 'api/save-settings.php';
            const opts = { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
            const resp = await (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat(endpoint, opts) : (typeof hqFetch === 'function' ? hqFetch(endpoint, opts) : fetch(endpoint, opts)));

            // Normalize parsed result
            let parsed = null;
            try {
                if (resp && resp._parsed) parsed = resp._parsed;
                else if (resp && typeof resp.text === 'function') {
                    const txt = await resp.text();
                    parsed = tryParseJSONOrShowHtml(txt);
                } else parsed = resp;
            } catch (err) { parsed = null; }

            if (!parsed) return;
            if (parsed.status === 'ok') { Swal.fire({title: parsed.title||'Success', text: parsed.message||'Settings saved', icon:'success'}).then(()=>location.reload()); }
            else { Swal.fire({title: parsed.title||'Error', text: parsed.message||'Failed to save', icon:'error'}); }
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
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            var endpoint = (action === 'runScan') ? ((typeof window.adminApi === 'function') ? window.adminApi('run-scan.php') : 'api/run-scan.php') : 'index.php?pages=settings';
            if (typeof hqFetch !== 'function') {
                fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r){ return r.text(); })
                    .then(function(text){
                        var parsed = tryParseJSONOrShowHtml(text); if (!parsed) return; if (parsed.status === 'ok') { Swal.fire({title: parsed.title||'Success', text: parsed.message||'Action completed', icon:'success'}).then(()=>{ if (action === 'clearIPs' || action === 'clearLogs') location.reload(); }); } else { Swal.fire({title: parsed.title||'Error', text: parsed.message||'Action failed', icon:'error'}); }
                    }).catch(function(err){ Swal.fire({ title: 'Error', text: err.message || 'Network error occurred', icon: 'error' }); });
            } else {
                hqFetch(endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(parsed){ if (!parsed) return; if (parsed.status === 'ok') { Swal.fire({title: parsed.title||'Success', text: parsed.message||'Action completed', icon:'success'}).then(()=>{ if (action === 'clearIPs' || action === 'clearLogs') location.reload(); }); } else { Swal.fire({title: parsed.title||'Error', text: parsed.message||'Action failed', icon:'error'}); } })
                    .catch(function(err){ if (err && (err.message==='auth' || err.message==='auth-html')) return; Swal.fire({ title: 'Error', text: err.message || 'Network error occurred', icon: 'error' }); });
            }
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
    }

    // Try to start now, or wait for DOM if not ready
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();