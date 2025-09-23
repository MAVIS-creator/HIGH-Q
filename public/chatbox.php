<?php
// public/chatbox.php - lightweight chat API (send/get) and an embeddable widget when used without action params.
require_once __DIR__ . '/config/db.php';

// Utility to send JSON responses and exit
function jsonResponse($data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Handle API requests before emitting HTML/CSS/JS
$action = $_REQUEST['action'] ?? '';

if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? 'Guest');
    $email = trim($_POST['email'] ?? '');
    // public/chatbox.php - clean chat API + embeddable widget
    require_once __DIR__ . '/config/db.php';

    function jsonResponse($data)
    {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data);
            exit;
    }
<?php
// public/chatbox.php - single clean API + widget
require_once __DIR__ . '/config/db.php';

function jsonResponse(array $data)
{
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
}

$action = $_REQUEST['action'] ?? '';

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

                $u = $pdo->prepare('UPDATE chat_threads SET last_activity = NOW() WHERE id = :id');
                $u->execute([':id' => $thread_id]);

                jsonResponse(['status' => 'ok', 'thread_id' => $thread_id]);
        } catch (Throwable $e) {
                jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
        }
}

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

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Live Chat</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:0}
        #hqMessages{padding:12px;height:360px;overflow:auto}
        #chatInput{width:100%;min-height:60px}
        .msg{padding:6px 0}
        .bubble.user{background:#ffeaa7;padding:8px;border-radius:8px;text-align:right}
        .bubble.admin{background:#eef6ff;padding:8px;border-radius:8px;text-align:left}
    </style>
</head>
<body>
    <div style="width:360px;max-width:100%">
        <div id="hqMessages"><div class="msg">Welcome — please tell us how we can help.</div></div>
        <div style="display:flex;gap:8px;margin-top:6px">
            <input id="chatName" placeholder="Your name" style="flex:1;padding:8px" />
            <input id="chatEmail" placeholder="Email (optional)" style="flex:1;padding:8px" />
        </div>
        <textarea id="chatInput" placeholder="Type a message..." style="margin-top:6px;padding:8px"></textarea>
        <div style="display:flex;gap:8px;padding:8px">
            <input type="file" id="chatFile" accept="image/*" />
            <button id="chatSend">Send</button>
        </div>
    </div>

    <script>
    (function(){
        function qs(id){return document.getElementById(id)}
        var send = qs('chatSend'), input = qs('chatInput'), nameEl = qs('chatName'), emailEl = qs('chatEmail'), fileEl = qs('chatFile'), msgs = qs('hqMessages');

        async function sendMessage(){ var name = nameEl.value.trim()||'Guest'; var email = emailEl.value.trim()||''; var message = input.value.trim(); if(!message && (!fileEl.files || !fileEl.files[0])){ alert('Enter a message or attach a file'); return }
            var fd = new FormData(); fd.append('name', name); fd.append('email', email); fd.append('message', message); if(fileEl.files[0]) fd.append('attachment', fileEl.files[0]);
            var res = await fetch('?action=send_message', {method:'POST', body:fd}); var j = await res.json(); if(j.status==='ok'){ localStorage.setItem('hq_thread_id', j.thread_id); input.value=''; fileEl.value=''; startPolling(j.thread_id) } else { alert('Send failed') }
        }

        send.addEventListener('click', sendMessage);

        var poll=null;
        async function fetchMessages(threadId){ try{ var r = await fetch('?action=get_messages&thread_id='+encodeURIComponent(threadId)); var j = await r.json(); if(j.status==='ok'){ msgs.innerHTML=''; j.messages.forEach(function(m){ var d=document.createElement('div'); d.className = (m.is_from_staff==1? 'bubble admin' : 'bubble user'); d.innerHTML = '<span style="font-weight:600">'+m.sender_name+'</span>: ' + m.message + '<div style="font-size:11px;color:#666">'+m.created_at+'</div>'; msgs.appendChild(d)}); msgs.scrollTop = msgs.scrollHeight } }catch(e){} }

        function startPolling(threadId){ if(poll) return; fetchMessages(threadId); poll = setInterval(function(){ fetchMessages(threadId) }, 3000) }

        var existing = localStorage.getItem('hq_thread_id'); if(existing) startPolling(existing);
    })();
    </script>
</body>
</html>

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
    <title>Live Chat</title>
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600&display=swap" rel="stylesheet">
    <style>
        :root{ --hq-yellow:#f5b904; --hq-dark:#171716; --hq-gray:#818181; }
        *{box-sizing:border-box}
        body{font-family: 'Raleway', system-ui; margin:0; background:transparent}
        .chat-box{ width:360px; max-width:100%; height:520px; border-radius:12px; background:#fff; box-shadow:0 18px 50px rgba(11,37,64,0.12); overflow:hidden; display:flex; flex-direction:column }
        .chat-box-header{ padding:12px 14px; background:linear-gradient(90deg,var(--hq-yellow),#d99a00); color:var(--hq-dark); display:flex; justify-content:space-between; align-items:center }
        .chat-header-left{ display:flex; gap:10px; align-items:center }
        .chat-avatar{ width:40px; height:40px; border-radius:50%; background:linear-gradient(90deg,#ffdd66,#ffc107); }
        .chat-box-body{ flex:1; padding:12px; background:linear-gradient(180deg,#fff,#fbfbfb); overflow:auto }
        .hq-messages{ display:flex; flex-direction:column; gap:10px }
        .bubble{ max-width:78%; padding:10px 12px; border-radius:14px; font-size:14px; line-height:1.4 }
        .bubble.user{ background:linear-gradient(90deg,var(--hq-yellow),#d99a00); color:#111; align-self:flex-end; border-bottom-right-radius:6px }
        .bubble.admin{ background:#f1f6ff; color:#08204a; align-self:flex-start; border-bottom-left-radius:6px }
        .bubble .time{ display:block; font-size:11px; color:var(--hq-gray); margin-top:6px }
        .chat-attachment img{ max-width:200px; border-radius:8px; display:block; margin-top:8px }
        .chat-box-footer{ padding:10px; display:flex; gap:8px; align-items:flex-end; border-top:1px solid rgba(0,0,0,0.06) }
        #chatInput{ flex:1; padding:10px; border-radius:10px; border:1px solid #eee; resize:vertical; min-height:44px }
        .btn-primary{ background:var(--hq-dark); color:#fff; border:none; padding:10px 14px; border-radius:10px; cursor:pointer }
        .btn-ghost{ background:#fff; border:1px solid rgba(0,0,0,0.06); padding:8px 10px; border-radius:8px; cursor:pointer }
        .small{ font-size:12px; color:var(--hq-gray) }
        @media (max-width:420px){ .chat-box{ width:100%; height:70vh; border-radius:0 } }
    </style>
</head>
<body>

<div class="chat-box" id="hqChatBox" role="dialog" aria-label="Live chat">
    <div class="chat-box-header">
        <div class="chat-header-left">
            <div class="chat-avatar" aria-hidden="true"></div>
            <div>
                <div style="font-weight:600">Chat with Support</div>
                <div class="small">Typically replies within a few minutes</div>
            </div>
        </div>
        <div><button id="closeChat" aria-label="Close chat" class="btn-ghost">✕</button></div>
    </div>
    <div class="chat-box-body">
        <div class="hq-messages" id="hqMessages">
            <div class="bubble admin">Welcome! Please tell us your name and how we can help. <span class="time">just now</span></div>
        </div>
    </div>
    <div class="chat-box-footer">
        <input id="chatName" placeholder="Your name" class="chat-meta" />
        <input id="chatEmail" placeholder="Email (optional)" class="chat-meta" />
    </div>
    <div style="padding:10px;border-top:1px solid #f1f1f1;display:flex;gap:8px;align-items:flex-end">
        <textarea id="chatInput" placeholder="Type a message..."></textarea>
        <input type="file" id="chatFile" accept="image/*" class="hidden" />
        <button id="attachBtn" class="btn-ghost" title="Attach image">📎</button>
        <button id="emojiBtn" class="btn-ghost" title="Emoji">😊</button>
        <button id="chatSend" class="btn-primary">Send</button>
    </div>
</div>

<script>
    (function(){
        function setCookie(name,value,days){ var d=new Date(); d.setTime(d.getTime()+(days*24*60*60*1000)); document.cookie = name+"="+encodeURIComponent(value)+";path=/;expires="+d.toUTCString(); }
        function getCookie(name){ var m=document.cookie.match(new RegExp('(^| )'+name+'=([^;]+)')); return m? decodeURIComponent(m[2]) : null; }

        var closeBtn = document.getElementById('closeChat');
        var sendBtn = document.getElementById('chatSend');
        var attachBtn = document.getElementById('attachBtn');
        var fileInput = document.getElementById('chatFile');
        var emojiBtn = document.getElementById('emojiBtn');
        var messagesEl = document.getElementById('hqMessages');
        var input = document.getElementById('chatInput');
        var nameInput = document.getElementById('chatName');
        var emailInput = document.getElementById('chatEmail');
        var pollTimer = null;

        closeBtn.addEventListener('click', function(){ if(window.top !== window.self && parent && parent.postMessage){ parent.postMessage({hq_chat_action:'close'}, '*'); } else { window.close && window.close(); } });

        attachBtn.addEventListener('click', function(){ fileInput.click(); });
        fileInput.addEventListener('change', function(){ if(fileInput.files && fileInput.files[0]){ var p = document.createElement('div'); p.className='bubble user'; p.textContent = 'Attachment ready: ' + fileInput.files[0].name; messagesEl.appendChild(p); messagesEl.scrollTop = messagesEl.scrollHeight; } });
        emojiBtn.addEventListener('click', function(){ input.value = input.value + ' 😊'; input.focus(); });

        async function sendMessage(){ var name = nameInput.value.trim() || 'Guest'; var email = emailInput.value.trim() || ''; var msg = input.value.trim(); if(!msg && !(fileInput.files && fileInput.files[0])){ alert('Please enter a message or attach an image'); return; }
            var fd = new FormData(); fd.append('name', name); fd.append('email', email); fd.append('message', msg);
            if(fileInput.files && fileInput.files[0]) fd.append('attachment', fileInput.files[0]);
            try{ var res = await fetch('?action=send_message', { method: 'POST', body: fd }); var j = await res.json(); if(j.status==='ok'){ setCookie('hq_thread_id', j.thread_id, 7); renderUserMessage(msg, fileInput.files && fileInput.files[0]); input.value=''; fileInput.value=''; if(!pollTimer) startPolling(j.thread_id); } else { alert('Failed to send'); } } catch(e){ console.error(e); alert('Failed to send'); }
        }

        sendBtn.addEventListener('click', sendMessage);

        function renderUserMessage(text, hasFile){ var b = document.createElement('div'); b.className='bubble user'; if(text) b.innerHTML = text; if(hasFile){ var hint = document.createElement('div'); hint.className='chat-attachment'; hint.textContent = 'Image attached'; b.appendChild(hint); } messagesEl.appendChild(b); messagesEl.scrollTop = messagesEl.scrollHeight; }


        function renderMessages(list){ messagesEl.innerHTML = ''; list.forEach(function(m){ var d = document.createElement('div'); d.className = 'bubble ' + (parseInt(m.is_from_staff) === 1 ? 'admin' : 'user'); d.innerHTML = m.message + '<span class="time">' + m.created_at + '</span>'; messagesEl.appendChild(d); }); messagesEl.scrollTop = messagesEl.scrollHeight; }


        async function fetchMessages(threadId){ try{ var r = await fetch('?action=get_messages&thread_id='+encodeURIComponent(threadId)); var j = await r.json(); if(j.status==='ok'){ renderMessages(j.messages); } }catch(e){} }

        function startPolling(threadId){ if(pollTimer) return; pollTimer = setInterval(function(){ fetchMessages(threadId); }, 2500); fetchMessages(threadId); }

        var existing = getCookie('hq_thread_id'); if(existing){ startPolling(existing); }

        // if embedded in an iframe, listen for close message
        window.addEventListener('message', function(ev){ try{ if(ev.data && ev.data.hq_chat_action === 'focus'){ input.focus(); } }catch(e){} });

    })();
</script>

</body>
</html>