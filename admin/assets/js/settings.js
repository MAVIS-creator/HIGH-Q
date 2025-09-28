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

            var data = new FormData(this);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', location.href, true);
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
            var endpoint = (action === 'runScan') ? '/HIGH-Q/admin/api/run-scan.php' : location.href;
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