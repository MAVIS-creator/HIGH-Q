<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

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
        $attachmentsTableExists = null;
        if (!empty($_FILES['attachments'])) {
            $files = $_FILES['attachments'];
            $uploadLog = __DIR__ . '/../storage/logs/chat_uploads.log';
            if (!is_dir(dirname($uploadLog))) @mkdir(dirname($uploadLog), 0755, true);
            try { @file_put_contents($uploadLog, date('c') . ' incoming $_FILES: ' . json_encode($files) . "\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $uploadDir = __DIR__ . '/uploads/chat/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $maxBytes = 100 * 1024 * 1024; // 100 MB
            for ($i = 0; $i < count($files['name']); $i++) {
                try { @file_put_contents($uploadLog, date('c') . " loop idx={$i} name={$files['name'][$i]} type_raw={$files['type'][$i]} size={$files['size'][$i]}\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
                if (($files['error'][$i] ?? 1) !== UPLOAD_ERR_OK) continue;
                $size = $files['size'][$i] ?? 0;
                if ($size > $maxBytes) continue; // too large
                $type = trim($files['type'][$i] ?? '');
                if (!in_array($type, $allowed, true)) { try { @file_put_contents($uploadLog, date('c') . " skip disallowed type {$type} for {$files['name'][$i]}\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {} continue; }
                $tmp = $files['tmp_name'][$i] ?? null;
                if (!$tmp || !is_uploaded_file($tmp)) { try { @file_put_contents($uploadLog, date('c') . " not an uploaded file: {$files['name'][$i]} tmp={$tmp}\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {} continue; }

                // Basic magic-byte checks
                $fh = fopen($tmp, 'rb');
                $magic = $fh ? fread($fh, 8) : '';
                if ($fh) fclose($fh);

                $valid = false;
                if (strpos($type, 'image/') === 0) {
                    // validate image by getimagesize
                    if (@getimagesize($tmp) !== false) {
                        $valid = true;
                    } else {
                        try { @file_put_contents($uploadLog, date('c') . " getimagesize failed for {$files['name'][$i]}\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
                    }
                } elseif ($type === 'application/pdf') {
                    if (substr($magic, 0, 4) === '%PDF') $valid = true;
                } elseif ($type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    // DOCX are zip packages; check for PK.. signature
                    if (substr($magic, 0, 2) === "PK") $valid = true;
                }
                if (!$valid) { try { @file_put_contents($uploadLog, date('c') . " invalid file {$files['name'][$i]} type={$type}\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {} continue; }

                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $nameSafe = bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = $uploadDir . $nameSafe;
                if (move_uploaded_file($tmp, $dest)) {
                    try { @file_put_contents($uploadLog, date('c') . " saved {$files['name'][$i]} -> {$dest}\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
                    $rel = 'uploads/chat/' . $nameSafe;
                    $url = function_exists('app_url') ? app_url($rel) : $rel;
                    $savedAttachUrls[] = ['url' => $url, 'orig' => $files['name'][$i], 'mime' => $type];
                    // for images, embed a preview in message; for other files, append as a link
                    if (strpos($type, 'image/') === 0) {
                        $messageHtml .= '<br><img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="max-width:100%;border-radius:8px">';
                    } else {
                        $dl = function_exists('app_url') ? app_url('download_attachment.php?file=' . urlencode($nameSafe)) : ('download_attachment.php?file=' . urlencode($nameSafe));
                        $messageHtml .= '<br><a href="' . htmlspecialchars($dl, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . htmlspecialchars($files['name'][$i]) . '</a>';
                    }
                } else {
                    try { @file_put_contents($uploadLog, date('c') . " move_uploaded_file failed for {$files['name'][$i]} tmp={$tmp}\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
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
                    $attachmentsTableExists = true;
                } catch (Throwable $_) {
                    // ignore if table/columns missing
                    $attachmentsTableExists = false;
                }
            }
        }

        $u = $pdo->prepare('UPDATE chat_threads SET last_activity = NOW() WHERE id=:id');
        $u->execute([':id' => $thread_id]);

        jsonResponse(['status' => 'ok', 'thread_id' => $thread_id, 'attachments' => $savedAttachUrls, 'attachments_table' => $attachmentsTableExists]);
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

        $attachmentsTableExists = null;
        // Enrich each message with attachments from chat_attachments (if table exists)
        try {
            $attStmt = $pdo->prepare('SELECT id, file_url, original_name, mime_type, created_at FROM chat_attachments WHERE message_id = ?');
            foreach ($msgs as &$m) {
                $attStmt->execute([$m['id']]);
                $atts = $attStmt->fetchAll(PDO::FETCH_ASSOC);
                $m['attachments'] = array_map(function($a){
                    return [
                        'url' => $a['file_url'],
                        'name' => $a['original_name'],
                        'type' => $a['mime_type'],
                        'created_at' => $a['created_at']
                    ];
                }, $atts);
            }
            unset($m);
            $attachmentsTableExists = true;
        } catch (Throwable $_) { 
            // If table missing, ensure attachments key exists as empty array for clients
            foreach ($msgs as &$m) { $m['attachments'] = []; }
            unset($m);
            $attachmentsTableExists = false;
        }

        $t = $pdo->prepare('SELECT status FROM chat_threads WHERE id=? LIMIT 1');
        $t->execute([$thread_id]);
        $threadStatus = $t->fetchColumn() ?: 'open';
        jsonResponse(['status' => 'ok', 'messages' => $msgs, 'thread_status' => $threadStatus, 'attachments_table' => $attachmentsTableExists]);
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
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --hq-yellow: #ffbf00;
            --hq-yellow-dark: #d99a00;
            --hq-red: #ff4b2b;
            --hq-dark: #171716;
            --hq-muted: #f4f4f6;
            --hq-gray: #536387;
        }

        html, body { height: 100%; margin: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: transparent;
            display: flex;
            box-sizing: border-box;
            overflow: hidden;
        }

        .chat-card {
            width: 100%;
            height: 100%;
            background: #fff;
            border-radius: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-sizing: border-box;
        }

        .landing-actions { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; }
        .landing-actions .btn-ghost { border:1px solid #e2e8f0; background:#fff; color:#111; padding:10px 14px; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer; transition:all 0.15s ease; box-shadow:0 4px 10px rgba(0,0,0,0.04); }
        .landing-actions .btn-ghost:hover { border-color:var(--hq-yellow-dark); box-shadow:0 6px 16px rgba(0,0,0,0.08); transform:translateY(-1px); }
        .landing-actions .btn-solid { border:none; background:linear-gradient(135deg, var(--hq-yellow) 0%, var(--hq-yellow-dark) 100%); color:#111; padding:10px 16px; border-radius:10px; font-weight:800; font-size:13px; cursor:pointer; box-shadow:0 6px 16px rgba(0,0,0,0.16); transition:all 0.15s ease; }
        .landing-actions .btn-solid:hover { filter:brightness(0.97); transform:translateY(-1px); }
        .landing-links { display:flex; gap:12px; font-size:12px; color:#444; align-items:center; justify-content:center; margin-top:6px; }
        .landing-links a { color:var(--hq-yellow-dark); font-weight:700; text-decoration:none; }
        .landing-links a:hover { text-decoration:underline; }

        .chat-header {
            padding: 18px 20px;
            background: linear-gradient(135deg, var(--hq-yellow) 0%, var(--hq-yellow-dark) 100%);
            color: var(--hq-dark);
            font-weight: 600;
            font-size: 17px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-header i { font-size: 20px; }

        /* Start Form Styling */
        .chat-start {
            flex: 1;
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: #fff;
            justify-content: center;
            animation: fadeIn 0.3s ease-in;
        }

        .chat-start h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            color: var(--hq-dark);
            font-weight: 600;
        }

        .chat-start p {
            margin: 0 0 20px 0;
            color: var(--hq-gray);
            font-size: 14px;
            line-height: 1.5;
        }

        .chat-start input[type="text"],
        .chat-start input[type="email"],
        .chat-start textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .chat-start input:focus,
        .chat-start textarea:focus {
            outline: none;
            border-color: var(--hq-yellow);
            box-shadow: 0 0 0 4px rgba(255, 191, 0, 0.1);
        }

        .chat-start textarea {
            resize: vertical;
            min-height: 80px;
        }

        .chat-start .start-btn {
            background: linear-gradient(135deg, var(--hq-yellow) 0%, var(--hq-yellow-dark) 100%);
            color: var(--hq-dark);
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 12px rgba(255, 191, 0, 0.3);
        }

        .chat-start .start-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 191, 0, 0.4);
        }

        .chat-start .start-btn:active {
            transform: translateY(0);
        }

        /* Chat Body */
        .chat-body {
            flex: 1;
            padding: 16px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            gap: 12px;
            background: var(--hq-muted);
        }

        .chat-message {
            padding: 12px 16px;
            border-radius: 16px;
            max-width: 75%;
            word-wrap: break-word;
            white-space: pre-wrap;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .chat-message.new-message {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .chat-message.visitor {
            margin-left: auto;
            background: linear-gradient(135deg, var(--hq-yellow) 0%, #ffce33 100%);
            color: var(--hq-dark);
            text-align: left;
            font-weight: 500;
        }

        .chat-message.staff {
            margin-right: auto;
            background: #ffffff;
            color: var(--hq-dark);
            border: 1px solid #e8e8e8;
            text-align: left;
        }

        .chat-message.system {
            margin: 0 auto;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            text-align: center;
            font-size: 13px;
            max-width: 90%;
            font-style: italic;
        }

        .chat-message img.chat-image {
            max-width: 180px;
            border-radius: 8px;
            margin-top: 6px;
        }

        /* Chat Footer */
        .chat-footer {
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-top: 2px solid #e8e8e8;
            background: #fafafa;
            animation: fadeIn 0.3s ease-in;
            position: relative;
            flex-wrap: wrap;
        }

        .chat-footer input[type=text],
        .chat-footer textarea {
            flex: 1;
            padding: 10px 14px;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            outline: none;
            resize: none;
            font-family: inherit;
            transition: border-color 0.2s ease;
        }

        .chat-footer input:focus,
        .chat-footer textarea:focus {
            border-color: var(--hq-yellow);
        }

        .chat-footer button {
            background: linear-gradient(135deg, var(--hq-yellow) 0%, var(--hq-yellow-dark) 100%);
            color: var(--hq-dark);
            border: none;
            padding: 10px 18px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease;
        }

        .chat-footer button:hover {
            transform: scale(1.05);
        }

        .btn-attachment {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: var(--hq-gray);
            transition: color 0.2s ease;
        }

        .btn-attachment:hover {
            color: var(--hq-yellow);
        }

        .attachment-preview {
            position: absolute;
            bottom: 100%;
            left: 12px;
            right: 12px;
            display: none;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
            background: #fff;
            padding: 8px;
            border-radius: 8px;
            box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .attachment-preview:not(:empty) {
            display: flex;
        }

        .attachment-preview img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }

        .attachment-preview .file-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 12px;
            color: #333;
        }

        .attachment-preview .file-item i {
            font-size: 16px;
            color: var(--hq-yellow-dark);
        }

        .attachment-preview .remove-file {
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            font-size: 14px;
            padding: 2px;
            margin-left: 4px;
        }

        .attachment-preview .remove-file:hover {
            color: #d63031;
        }

        .chat-alert {
            padding: 8px 12px;
            border-radius: 8px;
            background: #ffe6e6;
            color: #d63031;
            font-size: 13px;
            margin: 8px 12px;
            display: none;
            text-align: center;
        }

        /* Utility */
        .hidden { display: none !important; }
    </style>
</head>

<body>
    <div class="chat-card">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; background: linear-gradient(135deg, var(--hq-yellow) 0%, var(--hq-yellow-dark) 100%); color: var(--hq-dark); font-weight: 600; font-size: 17px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bx bx-chat" style="font-size:24px"></i>
                <span class="chat-header"><i class='bx bx-message-dots'></i> Live Chat Support</span>
            </div>
            <button id="newChatBtn" style="display: none; background: none; border: none; color: var(--hq-dark); cursor: pointer; font-size: 12px; font-weight: 700; padding: 4px 8px; text-decoration: underline; opacity: 0.8;" title="Start a new conversation">NEW CHAT</button>
        </div>
        
        <!-- Landing panel: choose to resume or start new chat -->
        <div id="chatLanding" style="display:flex;flex-direction:column;gap:12px;padding:18px 20px;align-items:center;justify-content:center;border-bottom:1px solid rgba(0,0,0,0.06)">
            <div style="font-weight:700;color:#333;display:flex;align-items:center;gap:8px"><i class="bx bx-chat"></i> Welcome to Support</div>
            <div id="landingHint" style="font-size:13px;color:#555;text-align:center">Start a new chat or resume the previous conversation.</div>
            <div class="landing-actions">
                <button id="resumeChatBtn" class="btn-ghost">Resume Previous</button>
                <button id="startNewBtn" class="btn-solid">Start New Chat</button>
            </div>
            <div class="landing-links">
                <i class="bx bx-book-open"></i> <a href="contact.php#faq" target="_blank" rel="noopener">FAQs</a>
                <span style="color:#ccc">|</span>
                <i class="bx bx-group"></i> <a href="community.php" target="_blank" rel="noopener">Community</a>
            </div>
        </div>

        <!-- Start form: visitor enters name/email/initial message first -->
        <div class="chat-start" id="chatStart">
            <h3><i class="bx bx-hand"></i> Hi there!</h3>
            <p>We're here to help. Please fill out the form below to start chatting with our support team.</p>
            
            <input type="text" id="start_name" placeholder="Your name *" required />
            <input type="email" id="start_email" placeholder="Your email (optional)" />
            <textarea id="start_message" placeholder="How can we help you today?" rows="3" required></textarea>
            
            <button id="startBtn" class="start-btn">Start Conversation</button>
            <p style="font-size: 12px; color: var(--hq-gray); text-align: center; margin-top: 8px;">
                Typically replies within a few minutes
            </p>
        </div>

        <!-- Chat body: shows after form submission -->
        <div class="chat-body" id="chatMessages" style="display:none;"></div>

        <!-- Chat footer: shows after thread created -->
        <div class="chat-footer" id="chatFooter" style="display:none;">
            <button type="button" class="btn-attachment" id="attachBtn" title="Attach files"><i class='bx bx-paperclip'></i></button>
            <input type="file" id="attachment" style="display:none;" multiple accept="image/*,.pdf,.docx">
            <input type="text" id="c_name" placeholder="Your Name" style="display:none;">
            <textarea id="c_message" rows="1" placeholder="Type your message..."></textarea>
            <button id="sendBtn">Send</button>
            <div class="attachment-preview" id="attachmentPreview"></div>
        </div>
        
        <div class="chat-alert" id="chatAlert"></div>
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
            const chatAlertEl = document.getElementById('chatAlert');
            const chatStartEl = document.getElementById('chatStart');
            const chatFooterEl = document.getElementById('chatFooter');
            const startBtn = document.getElementById('startBtn');
            const startName = document.getElementById('start_name');
            const startEmail = document.getElementById('start_email');
            const startMsg = document.getElementById('start_message');
            const newChatBtn = document.getElementById('newChatBtn');
            const landingEl = document.getElementById('chatLanding');
            const resumeChatBtn = document.getElementById('resumeChatBtn');
            const startNewBtn = document.getElementById('startNewBtn');
            const landingHint = document.getElementById('landingHint');

            function getThreadId() { return localStorage.getItem('hq_thread_id') || null; }
            function setThreadId(id) { localStorage.setItem('hq_thread_id', id); }
            
            // Clear thread ID for new chat (can be called from parent page)
            function clearThreadId() { localStorage.removeItem('hq_thread_id'); }
            
            // Handle new chat button
            newChatBtn.addEventListener('click', function() {
                clearThreadId();
                chatStartEl.style.display = 'flex';
                chatDiv.style.display = 'none';
                chatFooterEl.style.display = 'none';
                newChatBtn.style.display = 'none';
                chatDiv.innerHTML = '';
                startName.value = '';
                startEmail.value = '';
                startMsg.value = '';
            });
            
            // Listen for clear messages from parent page
            window.addEventListener('message', function(ev) {
                try {
                    if(ev.data && ev.data.hq_chat_action === 'clear_thread') {
                        clearThreadId();
                        location.reload();
                    }
                } catch(e) {}
            });

            // Initialize chat - auto-resume if there's an active thread
            async function initChat() {
                const tid = getThreadId();
                
                if (!tid) {
                    // No previous thread, show landing
                    showLanding(false);
                    return;
                }
                
                // Check if thread is still active
                try {
                    const res = await fetch('?action=get_messages&thread_id=' + encodeURIComponent(tid));
                    const j = await res.json();
                    
                    if (j.status === 'ok') {
                        // Check if thread is closed
                        if (j.thread_status === 'closed') {
                            // Thread is closed, show landing with option to start new
                            showLanding(true, true); // hasHistory=true, isClosed=true
                        } else {
                            // Thread is open, auto-resume!
                            autoResumeChat();
                        }
                    } else {
                        // Thread not found, clear and show landing
                        clearThreadId();
                        showLanding(false);
                    }
                } catch (e) {
                    console.error('initChat error:', e);
                    showLanding(!!tid);
                }
            }
            
            // Auto-resume an existing active chat
            function autoResumeChat() {
                landingEl.style.display = 'none';
                chatStartEl.style.display = 'none';
                chatDiv.style.display = 'flex';
                chatFooterEl.style.display = 'flex';
                newChatBtn.style.display = 'inline-block';
                getMessages();
            }

            // Landing logic: show choices
            function showLanding(hasHistory = false, isClosed = false) {
                if (!landingEl) return;
                landingEl.style.display = 'flex';
                chatStartEl.style.display = 'none';
                chatDiv.style.display = 'none';
                chatFooterEl.style.display = 'none';
                newChatBtn.style.display = 'none';

                if (hasHistory && !isClosed) {
                    resumeChatBtn.disabled = false;
                    resumeChatBtn.style.display = 'inline-block';
                    resumeChatBtn.title = 'Resume your conversation';
                    landingHint.textContent = 'You have an ongoing chat. Resume or start a new one.';
                } else if (hasHistory && isClosed) {
                    resumeChatBtn.disabled = true;
                    resumeChatBtn.style.display = 'none';
                    landingHint.innerHTML = '<i class="bx bx-check-circle" style="color:#22c55e"></i> Your previous chat was closed. Start a new conversation below.';
                    // Clear the closed thread so next time they start fresh
                    clearThreadId();
                } else {
                    resumeChatBtn.disabled = true;
                    resumeChatBtn.style.display = 'none';
                    landingHint.textContent = 'Start a new chat to get support.';
                }
            }

            if (resumeChatBtn) {
                resumeChatBtn.addEventListener('click', () => {
                    const tid = getThreadId();
                    if (!tid) return;
                    landingEl.style.display = 'none';
                    chatStartEl.style.display = 'none';
                    chatDiv.style.display = 'flex';
                    chatFooterEl.style.display = 'flex';
                    newChatBtn.style.display = 'inline-block';
                    getMessages();
                });
            }

            if (startNewBtn) {
                startNewBtn.addEventListener('click', () => {
                    clearThreadId();
                    landingEl.style.display = 'none';
                    chatStartEl.style.display = 'flex';
                    chatDiv.style.display = 'none';
                    chatFooterEl.style.display = 'none';
                    newChatBtn.style.display = 'none';
                });
            }

            function appendMessage(sender, msg, is_staff = false, is_system = false, attachments = [], isNew = false) {
                const div = document.createElement('div');
                let className = is_system ? 'chat-message system' : (is_staff ? 'chat-message staff' : 'chat-message visitor');
                if (isNew) className += ' new-message';
                div.className = className;
                
                if (is_system) {
                    div.innerHTML = msg;
                } else {
                    div.innerHTML = '<strong>' + sender + ':</strong> ' + msg;
                }
                
                attachments.forEach(a => {
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

            // Attachment handling
            attachBtn.addEventListener('click', () => { attachmentInput.click(); });
            
            attachmentInput.addEventListener('change', async () => {
                attachmentPreview.innerHTML = '';
                chatAlertEl.style.display = 'none';
                chatAlertEl.textContent = '';
                
                const maxBytes = 100 * 1024 * 1024;
                const allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                
                for (const file of Array.from(attachmentInput.files)) {
                    if (file.size > maxBytes) {
                        chatAlertEl.textContent = 'File too large: ' + file.name;
                        chatAlertEl.style.display = 'block';
                        continue;
                    }
                    if (!allowed.includes(file.type)) {
                        chatAlertEl.textContent = 'File type not allowed: ' + file.name;
                        chatAlertEl.style.display = 'block';
                        continue;
                    }
                    
                    // Magic-bytes validation for PDF/DOCX
                    if (file.type === 'application/pdf' || file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                        try {
                            const buf = await file.slice(0, 8).arrayBuffer();
                            const bytes = new Uint8Array(buf);
                            const sig = String.fromCharCode.apply(null, Array.from(bytes));
                            if (file.type === 'application/pdf' && !sig.startsWith('%PDF')) {
                                chatAlertEl.textContent = 'Invalid PDF: ' + file.name;
                                chatAlertEl.style.display = 'block';
                                continue;
                            }
                            if (file.type.indexOf('wordprocessingml.document') !== -1 && !(bytes[0] === 0x50 && bytes[1] === 0x4B)) {
                                chatAlertEl.textContent = 'Invalid DOCX: ' + file.name;
                                chatAlertEl.style.display = 'block';
                                continue;
                            }
                        } catch (e) {
                            chatAlertEl.textContent = 'Unable to validate: ' + file.name;
                            chatAlertEl.style.display = 'block';
                            continue;
                        }
                    }
                    
                    // Preview images
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = e => {
                            const wrapper = document.createElement('div');
                            wrapper.className = 'file-item';
                            wrapper.dataset.filename = file.name;
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.width = '50px';
                            img.style.height = '50px';
                            img.style.objectFit = 'cover';
                            img.style.borderRadius = '4px';
                            wrapper.appendChild(img);
                            
                            const nameSpan = document.createElement('span');
                            nameSpan.textContent = file.name.length > 15 ? file.name.substring(0, 12) + '...' : file.name;
                            wrapper.appendChild(nameSpan);
                            
                            const removeBtn = document.createElement('button');
                            removeBtn.className = 'remove-file';
                            removeBtn.innerHTML = '<i class="bx bx-x"></i>';
                            removeBtn.title = 'Remove';
                            removeBtn.onclick = function() {
                                wrapper.remove();
                            };
                            wrapper.appendChild(removeBtn);
                            
                            attachmentPreview.appendChild(wrapper);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'file-item';
                        wrapper.dataset.filename = file.name;
                        
                        const icon = document.createElement('i');
                        icon.className = file.type.includes('pdf') ? 'bx bxs-file-pdf' : 'bx bxs-file-doc';
                        wrapper.appendChild(icon);
                        
                        const nameSpan = document.createElement('span');
                        nameSpan.textContent = file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name;
                        nameSpan.title = file.name + ' (' + Math.round(file.size/1024) + ' KB)';
                        wrapper.appendChild(nameSpan);
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-file';
                        removeBtn.innerHTML = '<i class="bx bx-x"></i>';
                        removeBtn.title = 'Remove';
                        removeBtn.onclick = function() {
                            wrapper.remove();
                        };
                        wrapper.appendChild(removeBtn);
                        
                        attachmentPreview.appendChild(wrapper);
                    }
                }
            });

            // Auto-resize textarea
            msgInput.addEventListener('input', () => {
                msgInput.style.height = 'auto';
                msgInput.style.height = (msgInput.scrollHeight) + 'px';
            });

            async function sendMessage() {
                const message = msgInput.value.trim();
                if (!message) return;
                
                const fd = new FormData();
                fd.append('name', nameInput.value || startName.value || 'Guest');
                fd.append('message', message);
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
                        const attachedFiles = Array.from(attachmentInput.files).map(f => ({
                            url: URL.createObjectURL(f),
                            name: f.name,
                            type: f.type
                        }));
                        appendMessage(nameInput.value || startName.value || 'Guest', message, false, false, attachedFiles, true);
                        msgInput.value = '';
                        msgInput.style.height = 'auto';
                        attachmentInput.value = '';
                        attachmentPreview.innerHTML = '';
                    } else {
                        chatAlertEl.textContent = 'Failed to send message';
                        chatAlertEl.style.display = 'block';
                    }
                } catch (e) {
                    console.error(e);
                    chatAlertEl.textContent = 'Network error. Please try again.';
                    chatAlertEl.style.display = 'block';
                }
            }

            // Start conversation flow
            startBtn.addEventListener('click', async function() {
                const name = startName.value.trim();
                const email = startEmail.value.trim();
                const message = startMsg.value.trim();
                
                if (!name || !message) {
                    chatAlertEl.textContent = 'Please fill in your name and message';
                    chatAlertEl.style.display = 'block';
                    return;
                }
                
                startBtn.disabled = true;
                startBtn.textContent = 'Starting...';
                
                const fd = new FormData();
                fd.append('name', name);
                fd.append('email', email);
                fd.append('message', message);
                
                try {
                    const res = await fetch('?action=send_message', { method: 'POST', body: fd });
                    const j = await res.json();
                    
                    if (j.status === 'ok') {
                        setThreadId(j.thread_id);
                        nameInput.value = name;
                        
                        // Hide start form, show chat area
                        chatStartEl.style.display = 'none';
                        chatDiv.style.display = 'flex';
                        chatFooterEl.style.display = 'flex';
                        newChatBtn.style.display = 'inline-block';
                        
                        // Show user's first message
                        appendMessage(name, message, false, false, [], true);
                        
                        // Show "waiting for agent" system message
                        appendMessage('', '<i class="bx bx-loader-alt bx-spin"></i> Please wait while we connect you with an agent. Feel free to add more details while you wait.', false, true, [], false);
                        
                        // Start polling for messages
                        getMessages();
                    } else {
                        chatAlertEl.textContent = 'Failed to start conversation. Please try again.';
                        chatAlertEl.style.display = 'block';
                        startBtn.disabled = false;
                        startBtn.textContent = 'Start Conversation';
                    }
                } catch (e) {
                    console.error(e);
                    chatAlertEl.textContent = 'Network error. Please try again.';
                    chatAlertEl.style.display = 'block';
                    startBtn.disabled = false;
                    startBtn.textContent = 'Start Conversation';
                }
            });

            sendBtn.addEventListener('click', sendMessage);
            msgInput.addEventListener('keypress', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Show landing first; user can resume or start new
            showLanding();

            async function getMessages() {
                const tid = getThreadId();
                if (!tid) return;
                
                try {
                    const res = await fetch('?action=get_messages&thread_id=' + encodeURIComponent(tid));
                    const j = await res.json();
                    
                    console.log('getMessages response:', j); // Debug
                    
                    if (j.status !== 'ok') return;
                    
                    chatDiv.innerHTML = '';
                    j.messages.forEach(m => {
                        // Handle is_from_staff as string "1" or integer 1
                        const isStaff = m.is_from_staff === 1 || m.is_from_staff === '1' || m.is_from_staff === true;
                        console.log('Message:', m.sender_name, 'isStaff:', isStaff, 'raw is_from_staff:', m.is_from_staff); // Debug
                        appendMessage(m.sender_name, m.message, isStaff, false, (m.attachments || []));
                    });
                    
                    // If thread is closed, disable inputs
                    if (j.thread_status && j.thread_status === 'closed') {
                        chatFooterEl.style.display = 'none';
                        appendMessage('', '<i class="bx bx-check-circle"></i> This conversation has been closed. Thank you for contacting us!', false, true);
                    }
                } catch (e) {
                    console.error('getMessages error:', e);
                    console.error(e);
                }
            }
            
            // Poll every 2 seconds
            setInterval(getMessages, 2000);
        })();
    </script>
</body>

</html>