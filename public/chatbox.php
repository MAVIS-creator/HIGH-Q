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
            for ($i = 0; $i < count($files['name']); $i++) {
                if (($files['error'][$i] ?? 1) !== UPLOAD_ERR_OK) continue;
                $type = $files['type'][$i] ?? '';
                if (!in_array($type, $allowed, true)) continue;
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $nameSafe = bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = $uploadDir . $nameSafe;
                if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
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
    <style>
        :root {
            --hq-yellow: #f5b904;
            --hq-yellow-2: #d99a00;
            --hq-dark: #171716;
            --hq-muted: #f4f4f6;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: transparent;
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

            function getThreadId() {
                return localStorage.getItem('hq_thread_id') || null;
            }

            function setThreadId(id) {
                localStorage.setItem('hq_thread_id', id);
            }

            function appendMessage(sender, msg, is_staff = false, attachments = []) {
                const div = document.createElement('div');
                div.className = 'chat-message ' + (is_staff ? 'staff' : 'visitor');
                div.innerHTML = '<strong>' + sender + ':</strong> ' + msg;
                attachments.forEach(a => {
                    const img = document.createElement('img');
                    img.src = a;
                    img.className = 'chat-image';
                    div.appendChild(img);
                });
                chatDiv.appendChild(div);
                chatDiv.scrollTop = chatDiv.scrollHeight;
            }

            // Attachment handling with preview
            attachBtn.addEventListener('click', () => {
                attachmentInput.click();
            });
            attachmentInput.addEventListener('change', () => {
                attachmentPreview.innerHTML = '';
                Array.from(attachmentInput.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        attachmentPreview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
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
                        const attachedFiles = Array.from(attachmentInput.files).map(f => URL.createObjectURL(f));
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

            sendBtn.addEventListener('click', sendMessage);
            msgInput.addEventListener('keypress', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendMessage();
                }
            });

            async function getMessages() {
                const tid = getThreadId();
                if (!tid) return;
                try {
                    const res = await fetch('?action=get_messages&thread_id=' + encodeURIComponent(tid));
                    const j = await res.json();
                    if (j.status !== 'ok') return;
                    chatDiv.innerHTML = '';
                    j.messages.forEach(m => {
                        appendMessage(m.sender_name, m.message, m.is_from_staff == 1);
                    });
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