<?php
// Single-file chatbox: API handlers (send/get) + embeddable widget
require_once __DIR__ . '/config/db.php';

function jsonResponse(array $data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// Handle send_message (creates thread if needed, saves message, optional image upload)
if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? 'Guest');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $thread_id = isset($_POST['thread_id']) && is_numeric($_POST['thread_id']) ? (int)$_POST['thread_id'] : null;

    try {
        if (!$thread_id) {
            $stmt = $pdo->prepare('INSERT INTO chat_threads (visitor_name, visitor_email, created_at, last_activity) VALUES (:name, :email, NOW(), NOW())');
            $stmt->execute([':name' => $name, ':email' => $email]);
            $thread_id = (int)$pdo->lastInsertId();
        }

        $messageHtml = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Handle optional image upload
        if (!empty($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $f = $_FILES['attachment'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($f['type'], $allowed, true)) {
                $uploadDir = __DIR__ . '/uploads/chat/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $nameSafe = bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = $uploadDir . $nameSafe;
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    $url = 'uploads/chat/' . $nameSafe;
                    $messageHtml .= '<br><img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="attachment">';
                }
            }
        }

        $stmt = $pdo->prepare('INSERT INTO chat_messages (thread_id, sender_name, message, is_from_staff, created_at) VALUES (:thread_id, :sender_name, :message, 0, NOW())');
        $stmt->execute([':thread_id' => $thread_id, ':sender_name' => $name, ':message' => $messageHtml]);

        // Update last_activity for admin visibility
        $u = $pdo->prepare('UPDATE chat_threads SET last_activity = NOW() WHERE id = :id');
        $u->execute([':id' => $thread_id]);

        jsonResponse(['status' => 'ok', 'thread_id' => $thread_id]);
    } catch (Throwable $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Handle fetching messages for a thread
if ($action === 'get_messages' && isset($_GET['thread_id'])) {
    $thread_id = (int)$_GET['thread_id'];
    try {
        $stmt = $pdo->prepare('SELECT id, sender_name, message, is_from_staff, created_at FROM chat_messages WHERE thread_id = :tid ORDER BY created_at ASC');
        $stmt->execute([':tid' => $thread_id]);
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['status' => 'ok', 'messages' => $msgs]);
    } catch (Throwable $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// If no API action, render the embeddable widget (when opened directly or via iframe)
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Start Chat</title>
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600&display=swap" rel="stylesheet">
    <style>
        :root{ --hq-yellow:#f5b904; --hq-yellow-2:#d99a00; --hq-dark:#171716; --hq-muted:#f4f4f6; }
        *{box-sizing:border-box}
        body{font-family: 'Raleway', system-ui; margin:0; background:transparent; display:flex; align-items:center; justify-content:center; height:100vh}
    .card{ width:320px; max-width:94%; background:transparent; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,0.12); overflow:hidden }
    .card-header{ padding:18px 20px; background:linear-gradient(90deg,var(--hq-yellow),var(--hq-yellow-2)); color:var(--hq-dark) }
        .card-header h3{ margin:0; font-size:18px }
        .card-body{ padding:18px }
        .field{ margin-bottom:12px }
    input[type=text], input[type=email], textarea{ width:100%; padding:14px; border-radius:30px; border:1px solid rgba(0,0,0,0.08); background:transparent; font-size:14px; outline:none; color:inherit }
        textarea{ min-height:100px; resize:vertical }
    .btn-start{ display:block; width:100%; padding:14px; border-radius:30px; border:none; color:#111; font-weight:600; background:linear-gradient(90deg,var(--hq-yellow),var(--hq-yellow-2)); cursor:pointer }
        .note{ font-size:13px; color:#666; margin-bottom:8px }
        .success{ padding:12px; background:#e6fff6; border-left:4px solid #39a37a; color:#064; border-radius:8px; margin-top:12px }
        @media (max-width:420px){ body{height:100vh} .card{ width:92% } }
    </style>
</head>
<body>
    <div class="card" role="dialog" aria-label="Start chat">
        <div class="card-header">
            <h3>Let's chat? — Online</h3>
        </div>
        <div class="card-body">
            <p class="note">Please fill out the form below to start chatting with the next available agent.</p>
            <form id="startChatForm">
                <div class="field"><input type="text" id="c_name" name="name" placeholder="Your Name" required></div>
                <div class="field"><input type="email" id="c_email" name="email" placeholder="Email Address"></div>
                <div class="field"><textarea id="c_message" name="message" placeholder="Explain your queries.." required></textarea></div>
                <button class="btn-start" id="startBtn" type="submit">Start Chat</button>
            </form>
            <div id="result"></div>
        </div>
    </div>

    <script>
        (function(){
            var form = document.getElementById('startChatForm');
            var startBtn = document.getElementById('startBtn');
            var result = document.getElementById('result');

            form.addEventListener('submit', async function(e){
                e.preventDefault();
                startBtn.disabled = true; startBtn.textContent = 'Starting...'; result.innerHTML = '';
                var fd = new FormData(form);
                try{
                    var res = await fetch('?action=send_message', { method: 'POST', body: fd });
                    var j = await res.json();
                    if(j.status === 'ok'){
                        // store thread id for later
                        try{ localStorage.setItem('hq_thread_id', j.thread_id); }catch(e){}
                        result.innerHTML = '<div class="success">Chat started. An agent will be with you shortly.</div>';
                        startBtn.textContent = 'Started';
                    } else {
                        result.innerHTML = '<div class="note">Failed to start chat: ' + (j.message||'Unknown error') + '</div>';
                        startBtn.disabled = false; startBtn.textContent = 'Start Chat';
                    }
                }catch(err){ console.error(err); result.innerHTML = '<div class="note">Network error. Please try again.</div>'; startBtn.disabled = false; startBtn.textContent = 'Start Chat'; }
            });
        })();
    </script>
</body>
</html>
