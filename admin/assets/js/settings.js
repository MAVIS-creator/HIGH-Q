// Settings form submission handler
document.addEventListener('DOMContentLoaded', function() {
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
        try {
            var response = JSON.parse(xhr.responseText);
            if (xhr.status === 200 && response.status === 'ok') {
                Swal.fire({
                    title: response.title || 'Success',
                    text: response.message || 'Settings saved successfully',
                    icon: 'success',
                    confirmButtonColor: '#3085d6'
                });
            } else {
                throw new Error(response.message || 'Failed to save settings');
            }
        } catch (error) {
            Swal.fire({
                title: 'Error',
                text: error.message || 'An unexpected error occurred',
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
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', location.href, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        try {
            var response = JSON.parse(xhr.responseText);
            if (xhr.status === 200 && response.status === 'ok') {
                Swal.fire({
                    title: 'Success',
                    text: response.message || 'Action completed successfully',
                    icon: 'success',
                    confirmButtonColor: '#3085d6'
                }).then((result) => {
                    if (action === 'clearIPs' || action === 'clearLogs') {
                        location.reload();
                    }
                });
            } else {
                throw new Error(response.message || 'Action failed');
            }
        } catch (error) {
            Swal.fire({
                title: 'Error',
                text: error.message || 'An unexpected error occurred',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }
    };
    
    xhr.onerror = function() {
        Swal.fire({
            title: 'Error',
            text: 'Network error occurred',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
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
            }).then((result) => {
                if (result.isConfirmed) doAction('runScan');
            });
        });
    }

    var clearIPsBtn = document.getElementById('clearIPs');
    if (clearIPsBtn) {
        clearIPsBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Clear Blocked IPs?',
                text: 'This will remove all IP addresses from the block list.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, clear IPs'
            }).then((result) => {
                if (result.isConfirmed) doAction('clearIPs');
            });
        });
    }

    var clearLogsBtn = document.getElementById('clearLogs');
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Clear Audit Logs?',
                text: 'This will clear all audit logs except seed data. This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, clear logs'
            }).then((result) => {
                if (result.isConfirmed) doAction('clearLogs');
            });
        });
    }

});