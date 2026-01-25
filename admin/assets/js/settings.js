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

        form.addEventListener('submit', function(e) {
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
                allowEnterKey: false,
                showConfirmButton: false,
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
                    }).then(() => { if (action === 'clearIPs' || action === 'clearLogs') location.reload(); });
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
    }

    // Try to start now, or wait for DOM if not ready
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();