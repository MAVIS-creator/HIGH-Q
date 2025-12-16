<?php
// Test via HTTP request simulation
// This simulates what happens when browser makes AJAX call

// Start fresh session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if logged in
if (empty($_SESSION['user'])) {
    echo "Not logged in. Redirecting to login page to establish session...\n";
    echo "Please navigate to: http://127.0.0.1/HIGH-Q/admin/login.php\n";
    echo "Then come back and click the test buttons.\n";
    exit;
}

echo "Logged in as: " . $_SESSION['user']['name'] . "\n";
echo "User ID: " . $_SESSION['user']['id'] . "\n";
echo "Role ID: " . $_SESSION['user']['role_id'] . "\n";

// Generate valid CSRF token
require_once 'admin/includes/csrf.php';
$csrfComments = generateToken('comments_form');
$csrfChat = generateToken('chat_form');

?>
<!DOCTYPE html>
<html>
<head>
    <title>AJAX Test - Live</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        #log { background: #000; color: #0f0; padding: 15px; font-family: 'Courier New', monospace; min-height: 300px; margin-top: 20px; border-radius: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>AJAX Endpoint Test (Live Browser Request)</h1>
    
    <div class="test-section">
        <h2>Test Comments</h2>
        <button onclick="testComments('approve', 1)">Approve Comment ID 1</button>
        <button onclick="testComments('reject', 1)">Reject Comment ID 1</button>
    </div>
    
    <div class="test-section">
        <h2>Test Chat</h2>
        <button onclick="testChat('claim', 1)">Claim Thread ID 1</button>
    </div>
    
    <div id="log">Console Log:\n</div>
    
    <script>
        const ADMIN_BASE = window.location.origin + '/HIGH-Q/admin';
        const CSRF_COMMENTS = <?php echo json_encode($csrfComments); ?>;
        const CSRF_CHAT = <?php echo json_encode($csrfChat); ?>;
        
        function log(msg) {
            const logEl = document.getElementById('log');
            logEl.textContent += new Date().toISOString() + ' - ' + msg + '\n';
            logEl.scrollTop = logEl.scrollHeight;
        }
        
        function testComments(action, id) {
            log('===================');
            log('Testing Comments: ' + action + ' on ID ' + id);
            log('URL: ' + ADMIN_BASE + '/index.php?pages=comments');
            
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id', id);
            fd.append('_csrf', CSRF_COMMENTS);
            
            fetch(ADMIN_BASE + '/index.php?pages=comments', {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                log('Response Status: ' + response.status);
                log('Response Content-Type: ' + response.headers.get('Content-Type'));
                return response.text();
            })
            .then(text => {
                log('Response Length: ' + text.length + ' bytes');
                log('First 500 chars: ' + text.substring(0, 500));
                
                try {
                    const json = JSON.parse(text);
                    log('✓ Valid JSON!');
                    log('JSON Data: ' + JSON.stringify(json, null, 2));
                    
                    if (json.status === 'ok') {
                        Swal.fire('Success!', 'Action completed successfully', 'success');
                    } else {
                        Swal.fire('Failed', json.message || 'Operation failed', 'error');
                    }
                } catch(e) {
                    log('✗ NOT JSON! Parse Error: ' + e.message);
                    log('Full Response:\n' + text);
                    Swal.fire('Error', 'Got HTML instead of JSON', 'error');
                }
            })
            .catch(error => {
                log('✗ Network Error: ' + error.message);
                Swal.fire('Error', 'Network error: ' + error.message, 'error');
            });
        }
        
        function testChat(action, threadId) {
            log('===================');
            log('Testing Chat: ' + action + ' on Thread ' + threadId);
            log('URL: ' + ADMIN_BASE + '/index.php?pages=chat');
            
            const fd = new FormData();
            fd.append('action', action);
            fd.append('thread_id', threadId);
            fd.append('_csrf', CSRF_CHAT);
            
            fetch(ADMIN_BASE + '/index.php?pages=chat', {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                log('Response Status: ' + response.status);
                log('Response Content-Type: ' + response.headers.get('Content-Type'));
                return response.text();
            })
            .then(text => {
                log('Response Length: ' + text.length + ' bytes');
                log('First 500 chars: ' + text.substring(0, 500));
                
                try {
                    const json = JSON.parse(text);
                    log('✓ Valid JSON!');
                    log('JSON Data: ' + JSON.stringify(json, null, 2));
                    
                    if (json.status === 'ok') {
                        Swal.fire('Success!', 'Action completed successfully', 'success');
                    } else {
                        Swal.fire('Failed', json.message || 'Operation failed', 'error');
                    }
                } catch(e) {
                    log('✗ NOT JSON! Parse Error: ' + e.message);
                    log('Full Response:\n' + text);
                    Swal.fire('Error', 'Got HTML instead of JSON', 'error');
                }
            })
            .catch(error => {
                log('✗ Network Error: ' + error.message);
                Swal.fire('Error', 'Network error: ' + error.message, 'error');
            });
        }
        
        log('Ready! Click buttons to test.');
        log('Current page: ' + window.location.href);
    </script>
</body>
</html>
