<?php
require_once __DIR__ . '/config/db.php';

function jsonResponse(array $data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// Send message
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

        // Handle attachments[] (allow images + pdf/docx)
        $savedAttachUrls = [];
        if (!empty($_FILES['attachments'])) {
            $files = $_FILES['attachments'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $uploadDir = __DIR__ . '/uploads/chat/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $maxBytes = 100 * 1024 * 1024; // 100 MB
            for ($i = 0; $i < count($files['name']); $i++) {
                if (($files['error'][$i] ?? 1) !== UPLOAD_ERR_OK) continue;
                $size = $files['size'][$i] ?? 0;
                if ($size > $maxBytes) continue; // too large
                $type = $files['type'][$i] ?? '';
                if (!in_array($type, $allowed, true)) continue;
                $tmp = $files['tmp_name'][$i] ?? null;
                if (!$tmp || !is_uploaded_file($tmp)) continue;

                // Basic magic-byte checks
                $fh = fopen($tmp, 'rb');
                $magic = $fh ? fread($fh, 8) : '';
                if ($fh) fclose($fh);

                $valid = false;
                if (strpos($type, 'image/') === 0) {
                    // validate image by getimagesize
                    if (@getimagesize($tmp) !== false) $valid = true;
                } elseif ($type === 'application/pdf') {
                    if (substr($magic, 0, 4) === '%PDF') $valid = true;
                } elseif ($type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    // DOCX are zip packages; check for PK.. signature
                    if (substr($magic, 0, 2) === "PK") $valid = true;
                }
                if (!$valid) continue;

                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $nameSafe = bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = $uploadDir . $nameSafe;
                if (move_uploaded_file($tmp, $dest)) {
                    $url = 'uploads/chat/' . $nameSafe;
                    $savedAttachUrls[] = ['url' => $url, 'orig' => $files['name'][$i], 'mime' => $type];
                    // for images, embed a preview in message; for other files, append as a link
                    if (strpos($type, 'image/') === 0) {
                        $messageHtml .= '<br><img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="max-width:100%;border-radius:8px">';
                    } else {
                        $messageHtml .= '<br><a href="/HIGH-Q/public/download_attachment.php?file=' . urlencode($nameSafe) . '" target="_blank">' . htmlspecialchars($files['name'][$i]) . '</a>';
                    }
                }
            }
        }

        $stmt = $pdo->prepare('INSERT INTO chat_messages (thread_id, sender_name, message, is_from_staff, created_at) VALUES (:thread_id, :sender_name, :message, 0, NOW())');
        $stmt->execute([':thread_id' => $thread_id, ':sender_name' => $name, ':message' => $messageHtml]);
        $messageId = $pdo->lastInsertId();
        // Persist attachments into chat_attachments table if present
        if (!empty($savedAttachUrls)) {
            foreach ($savedAttachUrls as $attach) {
                try {
                    $ins = $pdo->prepare('INSERT INTO chat_attachments (message_id, file_url, original_name, mime_type, created_at) VALUES (?, ?, ?, ?, NOW())');
                    $ins->execute([$messageId, $attach['url'], $attach['orig'], $attach['mime']]);
                } catch (Throwable $_) {
                    // ignore if table/columns missing
                }
            }
        }

        $u = $pdo->prepare('UPDATE chat_threads SET last_activity = NOW() WHERE id=:id');
        $u->execute([':id' => $thread_id]);

        jsonResponse(['status' => 'ok', 'thread_id' => $thread_id]);
    } catch (Throwable $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Get messages
if ($action === 'get_messages' && isset($_GET['thread_id'])) {
    $thread_id = (int)$_GET['thread_id'];
    try {
        $stmt = $pdo->prepare('SELECT id, sender_name, message, is_from_staff, created_at FROM chat_messages WHERE thread_id=:tid ORDER BY created_at ASC');
        $stmt->execute([':tid' => $thread_id]);
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $t = $pdo->prepare('SELECT status FROM chat_threads WHERE id=? LIMIT 1');
        $t->execute([$thread_id]);
        $threadStatus = $t->fetchColumn() ?: 'open';
        jsonResponse(['status' => 'ok', 'messages' => $msgs, 'thread_status' => $threadStatus]);
    } catch (Throwable $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/responsive-utils.css">
    <style>
        :root {
            --hq-yellow: #f5b904;
            --hq-yellow-2: #d99a00;
            --hq-dark: #171716;
            --hq-muted: #f4f4f6;
        }

        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: transparent;
            display:flex;
            align-items:center;
            justify-content:center;
            padding: 18px;
            box-sizing: border-box;
        }

        .chat-card {
            width: 420px;
            max-width: 95%;
            height: 580px;
            background: #fff;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin: 0 auto; /* ensure centered */
            box-sizing: border-box;
        }

        .chat-header {
            padding: 15px;
            background: var(--hq-yellow);
            color: #fff;
            font-weight: 600;
            font-size: 16px;
        }

        .chat-body {
            flex: 1;
            padding: 10px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            gap: 10px;
            background: var(--hq-muted);
        }

        .chat-message {
            padding: 10px 14px;
            border-radius: 16px;
            max-width: 78%;
            word-wrap: break-word;
            white-space: pre-wrap;
            display: inline-block;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }

        .chat-message.visitor {
            margin-left: auto;
            background: #ffd966; /* brighter yellow for good contrast */
            color: #111;
            text-align: left;
        }

        .chat-message.staff {
            margin-right: auto;
            background: #ffffff;
            color: #111;
            border: 1px solid #eee;
            text-align: left;
        }

        .chat-message img.chat-image {
            max-width: 150px;
            border-radius: 8px;
            margin-top: 4px;
        }

        .chat-footer {
            padding: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-top: 1px solid #ddd;
            position: relative;
        }

        .chat-footer input[type=text],
        .chat-footer textarea {
            flex: 1;
            padding: 10px 14px;
            border-radius: 18px;
            border: 1px solid #ccc;
            outline: none;
            resize: none;
        }

        .chat-footer button {
            background: var(--hq-yellow);
            color: #111;
            border: none;
            padding: 8px 12px;
            border-radius: 18px;
            cursor: pointer;
        }
            .chat-start {
                padding: 12px;
                display: flex;
                flex-direction: column;
                gap: 8px;
                background: #fff;
                padding-bottom: 12px;
            }
            .chat-start input[type="text"], .chat-start input[type="email"], .chat-start textarea {
                width: 100%;
                box-sizing: border-box;
                padding: 8px 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
            }
            .chat-start .btn { padding: 8px 12px; border-radius: 8px; }
            .chat-alert { padding:6px 10px;border-radius:8px;background:#ffecec;color:#cc0000;font-size:13px;margin-top:6px;display:none }

        .btn-attachment {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
        }

        /* emoji panel removed - attachments only */

        .attachment-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 4px;
        }

        .attachment-preview img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .options-container {
            margin-top: 4px;
            display: flex;
            gap: 4px;
        }

        .options-container button {
            padding: 4px 8px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            background: var(--hq-yellow-2);
            color: var(--hq-dark);
        }
    </style>
</head>

<body>
        <div class="chat-card">
        <div class="chat-header">Live Chat — Online</div>
        <div class="chat-body" id="chatMessages"></div>

        <!-- Start form: visitor enters name/email/initial message first -->
        <div class="chat-start" id="chatStart">
            <input type="text" id="start_name" placeholder="Your name" />
            <input type="email" id="start_email" placeholder="Your email (optional)" />
            <textarea id="start_message" placeholder="Briefly tell us how we can help..." rows="3"></textarea>
            <div style="display:flex;gap:8px;align-items:center;margin-top:6px">
                <button id="startBtn" class="btn">Start Chat</button>
                <div style="font-size:0.9rem;color:#666;margin-left:auto">We will connect you to an agent shortly</div>
            </div>
        </div>

        <div class="chat-footer" id="chatFooter" style="display:none;">
            <button type="button" class="btn-attachment" id="attachBtn"><i class="fas fa-paperclip"></i></button>
            <input type="file" id="attachment" style="display:none;" multiple>
            <input type="text" id="c_name" placeholder="Your Name">
            <textarea id="c_message" rows="1" placeholder="Type a message..."></textarea>
            <button id="sendBtn">Send</button>
            <div class="attachment-preview" id="attachmentPreview"></div>
        </div>
    </div>

    <script>
        (function() {
            const sendBtn = document.getElementById('sendBtn');
            const msgInput = document.getElementById('c_message');
            const nameInput = document.getElementById('c_name');
            const chatDiv = document.getElementById('chatMessages');
            const attachmentInput = document.getElementById('attachment');
            const attachBtn = document.getElementById('attachBtn');
            const attachmentPreview = document.getElementById('attachmentPreview');
            // emoji removed

            function getThreadId() { return localStorage.getItem('hq_thread_id') || null; }
            function setThreadId(id) { localStorage.setItem('hq_thread_id', id); }

            function appendMessage(sender, msg, is_staff = false, attachments = []) {
                const div = document.createElement('div');
                div.className = 'chat-message ' + (is_staff ? 'staff' : 'visitor');
                div.innerHTML = '<strong>' + sender + ':</strong> ' + msg;
                attachments.forEach(a => {
                    // if it's an object with type, render properly
                    if (typeof a === 'object' && a.type && a.type.startsWith('image')) {
                        const img = document.createElement('img');
                        img.src = a.url;
                        img.className = 'chat-image';
                        div.appendChild(img);
                    } else if (typeof a === 'object') {
                        const link = document.createElement('a');
                        link.href = a.url;
                        link.target = '_blank';
                        link.textContent = a.name || 'Attachment';
                        div.appendChild(document.createElement('br'));
                        div.appendChild(link);
                    } else {
                        const img = document.createElement('img');
                        img.src = a;
                        img.className = 'chat-image';
                        div.appendChild(img);
                    }
                });
                chatDiv.appendChild(div);
                chatDiv.scrollTop = chatDiv.scrollHeight;
            }

            // Attachment handling with client-side validation
            attachBtn.addEventListener('click', () => { attachmentInput.click(); });
            // create alert element if missing
            let chatAlertEl = document.getElementById('chatAlert');
            if (!chatAlertEl) {
                chatAlertEl = document.createElement('div'); chatAlertEl.id = 'chatAlert'; chatAlertEl.className = 'chat-alert'; document.querySelector('.chat-card').appendChild(chatAlertEl);
            }
            attachmentInput.addEventListener('change', async () => {
                attachmentPreview.innerHTML = '';
                chatAlertEl.style.display = 'none'; chatAlertEl.textContent = '';
                const maxBytes = 100 * 1024 * 1024;
                const allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                for (const file of Array.from(attachmentInput.files)) {
                    if (file.size > maxBytes) { chatAlertEl.textContent = 'File too large: ' + file.name; chatAlertEl.style.display = 'block'; continue; }
                    if (!allowed.includes(file.type)) { chatAlertEl.textContent = 'Not allowed file type: ' + file.name; chatAlertEl.style.display = 'block'; continue; }
                    // magic-bytes checks for PDF/DOCX
                    if (file.type === 'application/pdf' || file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                        try {
                            const buf = await file.slice(0, 8).arrayBuffer();
                            const bytes = new Uint8Array(buf);
                            const sig = String.fromCharCode.apply(null, Array.from(bytes));
                            if (file.type === 'application/pdf' && !sig.startsWith('%PDF')) { chatAlertEl.textContent = 'Invalid PDF: ' + file.name; chatAlertEl.style.display = 'block'; continue; }
                            if (file.type.indexOf('wordprocessingml.document') !== -1 && !(bytes[0] === 0x50 && bytes[1] === 0x4B)) { chatAlertEl.textContent = 'Invalid DOCX: ' + file.name; chatAlertEl.style.display = 'block'; continue; }
                        } catch (e) { chatAlertEl.textContent = 'Unable to validate file: ' + file.name; chatAlertEl.style.display = 'block'; continue; }
                    }
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = e => { const img = document.createElement('img'); img.src = e.target.result; attachmentPreview.appendChild(img); };
                        reader.readAsDataURL(file);
                    } else {
                        const div = document.createElement('div'); div.textContent = file.name + ' (' + Math.round(file.size/1024) + ' KB)'; div.style.padding = '6px 8px'; div.style.borderRadius = '6px'; div.style.background = '#fff'; div.style.marginTop = '4px'; attachmentPreview.appendChild(div);
                    }
                }
            });

            // Auto-resize textarea
            msgInput.addEventListener('input', () => {
                msgInput.style.height = 'auto';
                msgInput.style.height = (msgInput.scrollHeight) + 'px';
            });

            async function sendMessage() {
                const fd = new FormData();
                fd.append('name', nameInput.value);
                fd.append('message', msgInput.value);
                const tid = getThreadId();
                if (tid) fd.append('thread_id', tid);
                Array.from(attachmentInput.files).forEach((f, idx) => fd.append('attachments[]', f));

                try {
                    const res = await fetch('?action=send_message', {
                        method: 'POST',
                        body: fd
                    });
                    const j = await res.json();
                    if (j.status === 'ok') {
                        setThreadId(j.thread_id);
                        const attachedFiles = Array.from(attachmentInput.files).map(f => ({ url: URL.createObjectURL(f), name: f.name, type: f.type }));
                        appendMessage(nameInput.value, msgInput.value, false, attachedFiles);
                        msgInput.value = '';
                        msgInput.style.height = 'auto';
                        attachmentInput.value = '';
                        attachmentPreview.innerHTML = '';
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            // Start flow: show start form, then show footer after starting
            const chatStartEl = document.getElementById('chatStart');
            const chatFooterEl = document.getElementById('chatFooter');
            const startBtn = document.getElementById('startBtn');
            const startName = document.getElementById('start_name');
            const startEmail = document.getElementById('start_email');
            const startMsg = document.getElementById('start_message');

            startBtn.addEventListener('click', async function(){
                const name = startName.value.trim() || 'Guest';
                const email = startEmail.value.trim() || '';
                const message = startMsg.value.trim() || 'Hi, I need help.';
                // set visitor name/email into footer inputs
                document.getElementById('c_name').value = name;
                // post initial message via send_message flow to create thread and add message
                const fd = new FormData(); fd.append('name', name); fd.append('email', email); fd.append('message', message);
                try {
                    const res = await fetch('?action=send_message', { method: 'POST', body: fd });
                    const j = await res.json();
                    if (j.status === 'ok') {
                        setThreadId(j.thread_id);
                        // hide start form and show footer
                        chatStartEl.style.display = 'none';
                        chatFooterEl.style.display = 'flex';
                        // show system message
                        appendMessage('SYSTEM', 'An agent will be with you shortly. Meanwhile you can continue typing.', true);
                        // trigger immediate refresh
                        getMessages();
                    }
                } catch (e) { console.error(e); }
            });

            sendBtn.addEventListener('click', sendMessage);
            msgInput.addEventListener('keypress', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendMessage();
                }
            });

                // If a thread already exists in localStorage, skip start form and show footer
                if (getThreadId()) {
                    if (chatStartEl) chatStartEl.style.display = 'none';
                    if (chatFooterEl) chatFooterEl.style.display = 'flex';
                    // populate messages immediately
                    getMessages();
                }

            async function getMessages() {
                const tid = getThreadId();
                if (!tid) return;
                try {
                    const res = await fetch('?action=get_messages&thread_id=' + encodeURIComponent(tid));
                    const j = await res.json();
                    if (j.status !== 'ok') return;
                    chatDiv.innerHTML = '';
                    j.messages.forEach(m => {
                        // handle attachments in message: server may embed <img> or <a> tags; we'll display message HTML safely
                        appendMessage(m.sender_name, m.message, m.is_from_staff == 1);
                    });
                    // if thread is closed, disable inputs
                    if (j.thread_status && j.thread_status === 'closed') {
                        chatFooterEl.style.display = 'none';
                        if (chatStartEl) chatStartEl.style.display = 'none';
                        appendMessage('SYSTEM', 'This conversation has been closed by an agent.', true);
                    }
                } catch (e) {
                    console.error(e);
                }
            }
            setInterval(getMessages, 2000);

            // Option buttons
            document.querySelectorAll('.options-container button').forEach(btn => {
                btn.addEventListener('click', () => {
                    msgInput.value += btn.dataset.option + ' ';
                    msgInput.dispatchEvent(new Event('input'));
                });
            });
        })();
    </script>
</body>

</html>