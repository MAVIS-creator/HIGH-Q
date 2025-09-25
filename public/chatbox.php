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

        // Handle attachment
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
                    $messageHtml .= '<br><img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="max-width:100%;border-radius:8px">';
                }
            }
        }

        $stmt = $pdo->prepare('INSERT INTO chat_messages (thread_id, sender_name, message, is_from_staff, created_at) VALUES (:thread_id, :sender_name, :message, 0, NOW())');
        $stmt->execute([':thread_id' => $thread_id, ':sender_name' => $name, ':message' => $messageHtml]);

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
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Live Chat</title>
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --hq-yellow: #f5b904;
            --hq-yellow-2: #d99a00;
            --hq-dark: #171716;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            background: transparent !important;
        }

        body {
            font-family: 'Raleway', system-ui;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .card {
            width: 350px;
            max-width: 94%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .12);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .card-header {
            padding: 18px 20px;
            background: linear-gradient(90deg, var(--hq-yellow), var(--hq-yellow-2));
            color: var(--hq-dark);
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .card-body {
            padding: 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        #chatMessages {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 8px;
            padding: 6px;
        }

        #chatMessages .visitor {
            color: #111;
            margin-bottom: 6px;
            text-align: left;
        }

        #chatMessages .staff {
            color: #d99a00;
            margin-bottom: 6px;
            text-align: right;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        input[type=text],
        textarea {
            padding: 10px;
            border-radius: 20px;
            border: 1px solid #ccc;
            width: 100%;
            outline: none;
        }

        textarea {
            resize: none;
        }

        input[type=file] {
            margin-top: 4px;
        }

        button {
            padding: 10px;
            border: none;
            border-radius: 20px;
            background: linear-gradient(90deg, var(--hq-yellow), var(--hq-yellow-2));
            color: #111;
            font-weight: 600;
            cursor: pointer;
        }

        .emoji-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-bottom: 4px;
        }

        .emoji-picker span {
            cursor: pointer;
            font-size: 18px;
        }

        @media (max-width:420px) {
            .card {
                width: 92%;
            }
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="card-header">
            <h3>Live Chat — Online</h3>
        </div>
        <div class="card-body">
            <div id="chatMessages"></div>
            <form id="startChatForm">
                <input type="text" id="c_name" name="name" placeholder="Your Name" required>
                <textarea id="c_message" name="message" placeholder="Type a message..." required></textarea>
                <input type="file" name="attachment" id="attachment">
                <div class="emoji-picker" id="emojiPicker">
                    <span>😀</span><span>😂</span><span>😍</span><span>👍</span><span>🙌</span><span>😎</span><span>🤔</span><span>🥳</span>
                </div>
                <button type="submit" id="startBtn">Send</button>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const form = document.getElementById('startChatForm');
            const startBtn = document.getElementById('startBtn');
            const chatDiv = document.getElementById('chatMessages');
            const emojiPicker = document.getElementById('emojiPicker');

            function setThreadId(id) {
                try {
                    localStorage.setItem('hq_thread_id', id);
                } catch (e) {}
            }

            function getThreadId() {
                return localStorage.getItem('hq_thread_id') || null;
            }

            function appendMessage(sender, msg, is_staff = false) {
                const div = document.createElement('div');
                div.className = is_staff ? 'staff' : 'visitor';
                div.innerHTML = `<strong>${sender}:</strong> ${msg}`;
                chatDiv.appendChild(div);
                chatDiv.scrollTop = chatDiv.scrollHeight;
            }

            // Click emoji to insert
            emojiPicker.querySelectorAll('span').forEach(e => {
                e.addEventListener('click', () => {
                    form.c_message.value += e.textContent;
                });
            });

            async function getMessages() {
                const tid = getThreadId();
                if (!tid) return;
                try {
                    const res = await fetch('?action=get_messages&thread_id=' + encodeURIComponent(tid));
                    if (!res.ok) return;
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
            setInterval(getMessages, 3000);

            form.addEventListener('submit', async e => {
                e.preventDefault();
                startBtn.disabled = true;
                startBtn.textContent = 'Sending...';

                const fd = new FormData(form);
                const tid = getThreadId();
                if (tid) fd.append('thread_id', tid);

                try {
                    const res = await fetch('?action=send_message', {
                        method: 'POST',
                        body: fd
                    });
                    const j = await res.json();
                    if (j.status === 'ok') {
                        setThreadId(j.thread_id);
                        appendMessage(fd.get('name'), fd.get('message'), false);
                        form.c_message.value = '';
                    } else {
                        alert('Error: ' + (j.message || 'Unknown'));
                    }
                } catch (err) {
                    console.error(err);
                }
                startBtn.disabled = false;
                startBtn.textContent = 'Send';
            });
        })();
    </script>
</body>

</html>