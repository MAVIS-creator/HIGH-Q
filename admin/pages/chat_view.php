<?php
// admin/pages/chat_view.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
requirePermission('chat');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$threadId = intval($_GET['thread_id'] ?? 0);
if (!$threadId) { header('Location: ?pages=chat'); exit; }

// If unassigned, claim it for this admin
$claim = $pdo->prepare('UPDATE chat_threads SET assigned_admin_id = ? WHERE id = ? AND assigned_admin_id IS NULL');
$claim->execute([$_SESSION['user']['id'], $threadId]);
if ($claim->rowCount() > 0) {
  logAction($pdo, $_SESSION['user']['id'], 'chat_claimed', ['thread_id'=>$threadId]);
    notifyAdminChange($pdo, 'Chat Thread Claimed', ['Thread ID' => $threadId], (int)($_SESSION['user']['id'] ?? 0));
}

$thread = $pdo->prepare('SELECT ct.*, u.name as assigned_admin_name FROM chat_threads ct LEFT JOIN users u ON ct.assigned_admin_id = u.id WHERE ct.id = ? LIMIT 1');
$thread->execute([$threadId]); $thread = $thread->fetch(PDO::FETCH_ASSOC);
$messages = $pdo->prepare('SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at ASC'); $messages->execute([$threadId]); $msgs = $messages->fetchAll(PDO::FETCH_ASSOC);

// Helper function to sanitize message while allowing safe HTML (images, links, line breaks)
function sanitizeMessageHtml($message) {
    // Allow only specific safe tags
    $allowed = '<img><br><a><b><strong><i><em>';
    $clean = strip_tags($message, $allowed);
    return $clean;
}

$pageTitle = 'Chat Thread #' . $threadId;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Tailwind-inspired modern chat UI using root HQ colors -->
<style>
/* Use existing root variables from admin.css */
:root {
    --chat-primary: var(--hq-yellow, #ffd600);
    --chat-primary-dark: #d4b000;
    --chat-dark: var(--hq-black, #0a0a0a);
    --chat-gray: var(--hq-gray, #f3f4f6);
    --chat-danger: var(--hq-red, #ff4b2b);
    --chat-success: #10b981;
    --chat-white: #ffffff;
    --chat-muted: #94a3b8;
}

/* Main container */
.chat-container {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
    min-height: calc(100vh - 200px);
}

@media (max-width: 1100px) {
    .chat-container {
        grid-template-columns: 1fr;
    }
    .chat-sidebar {
        display: none;
    }
}

/* Chat main panel */
.chat-main {
    display: flex;
    flex-direction: column;
    background: var(--chat-white);
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    overflow: hidden;
    height: calc(100vh - 160px);
    min-height: 500px;
}

/* Chat header */
.chat-header {
    background: linear-gradient(135deg, var(--chat-dark) 0%, #1e293b 100%);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 3px solid var(--chat-primary);
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.chat-header-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--chat-primary), var(--chat-primary-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--chat-dark);
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(255,214,0,0.3);
}

.chat-header-details h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--chat-white);
}

.chat-header-details .subtitle {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.7);
    margin-top: 2px;
}

.chat-header-meta {
    display: flex;
    gap: 16px;
    align-items: center;
}

.chat-status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.chat-status-badge.open {
    background: rgba(16,185,129,0.15);
    color: #10b981;
    border: 1px solid rgba(16,185,129,0.3);
}

.chat-status-badge.closed {
    background: rgba(239,68,68,0.15);
    color: #ef4444;
    border: 1px solid rgba(239,68,68,0.3);
}

/* Messages area */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

/* Message bubbles */
.message-wrapper {
    display: flex;
    gap: 12px;
    max-width: 85%;
    /* Removed slide/fade animation to avoid flicker on refresh */
    animation: none;
}

.message-wrapper.visitor {
    align-self: flex-start;
}

.message-wrapper.staff {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.message-wrapper.visitor .message-avatar {
    background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
    color: #475569;
}

.message-wrapper.staff .message-avatar {
    background: linear-gradient(135deg, var(--chat-primary), var(--chat-primary-dark));
    color: var(--chat-dark);
}

.message-content {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.message-meta {
    font-size: 0.75rem;
    color: var(--chat-muted);
    padding: 0 4px;
}

.message-wrapper.staff .message-meta {
    text-align: right;
}

.message-bubble {
    padding: 14px 18px;
    border-radius: 18px;
    line-height: 1.5;
    font-size: 0.95rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    word-wrap: break-word;
}

.message-wrapper.visitor .message-bubble {
    background: var(--chat-white);
    color: var(--chat-dark);
    border: 1px solid #e2e8f0;
    border-top-left-radius: 4px;
}

.message-wrapper.staff .message-bubble {
    background: linear-gradient(135deg, var(--chat-primary), #ffe066);
    color: var(--chat-dark);
    border-top-right-radius: 4px;
}

/* Image rendering in messages */
.message-bubble img {
    max-width: 100%;
    border-radius: 12px;
    margin-top: 10px;
    cursor: pointer;
    transition: transform 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.message-bubble img:hover {
    transform: scale(1.02);
}

/* Attachments section */
.message-attachments {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

.attachment-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--chat-white);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    min-width: 240px;
    transition: all 0.2s ease;
}

.attachment-card:hover {
    border-color: var(--chat-primary);
    box-shadow: 0 4px 12px rgba(255,214,0,0.15);
}

.attachment-thumb {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    object-fit: cover;
    background: #f1f5f9;
}

.attachment-thumb-icon {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: var(--chat-muted);
}

.attachment-info {
    flex: 1;
    overflow: hidden;
}

.attachment-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--chat-dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attachment-meta {
    font-size: 0.8rem;
    color: var(--chat-muted);
    margin-top: 2px;
}

.attachment-actions {
    display: flex;
    gap: 8px;
}

.attachment-btn {
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    gap: 4px;
}

.attachment-btn.download {
    background: var(--chat-dark);
    color: var(--chat-white);
}

.attachment-btn.download:hover {
    background: #1e293b;
}

.attachment-btn.delete {
    background: rgba(239,68,68,0.1);
    color: #ef4444;
}

.attachment-btn.delete:hover {
    background: #ef4444;
    color: var(--chat-white);
}

/* Chat input footer */
.chat-footer {
    padding: 20px 24px;
    background: var(--chat-white);
    border-top: 1px solid #e2e8f0;
}

.chat-input-wrapper {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.chat-input-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.chat-textarea {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    font-size: 0.95rem;
    font-family: inherit;
    resize: none;
    min-height: 56px;
    max-height: 150px;
    transition: all 0.2s ease;
    background: #f8fafc;
}

.chat-textarea:focus {
    outline: none;
    border-color: var(--chat-primary);
    background: var(--chat-white);
    box-shadow: 0 0 0 4px rgba(255,214,0,0.15);
}

.chat-file-input {
    display: flex;
    align-items: center;
    gap: 8px;
}

.chat-file-label {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #f1f5f9;
    border-radius: 10px;
    font-size: 0.85rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
}

.chat-file-label:hover {
    background: #e2e8f0;
    color: var(--chat-dark);
}

.chat-file-label i {
    font-size: 18px;
}

.chat-send-btn {
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--chat-primary), var(--chat-primary-dark));
    color: var(--chat-dark);
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(255,214,0,0.3);
}

.chat-send-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255,214,0,0.4);
}

.chat-send-btn:active {
    transform: translateY(0);
}

/* Chat sidebar */
.chat-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.sidebar-card {
    background: var(--chat-white);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}

.sidebar-card h4 {
    margin: 0 0 16px 0;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--chat-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sidebar-info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-info-item:last-child {
    border-bottom: none;
}

.sidebar-info-label {
    font-size: 0.85rem;
    color: var(--chat-muted);
}

.sidebar-info-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--chat-dark);
}

.sidebar-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.sidebar-btn {
    width: 100%;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.sidebar-btn.danger {
    background: rgba(239,68,68,0.1);
    color: #ef4444;
}

.sidebar-btn.danger:hover {
    background: #ef4444;
    color: var(--chat-white);
}

.sidebar-btn.secondary {
    background: #f1f5f9;
    color: var(--chat-dark);
}

.sidebar-btn.secondary:hover {
    background: #e2e8f0;
}

/* Page wrapper */
.chat-page-wrapper {
    padding: 24px;
}

.chat-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.chat-breadcrumb a {
    color: var(--chat-muted);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: color 0.2s ease;
}

.chat-breadcrumb a:hover {
    color: var(--chat-primary);
}

.chat-breadcrumb span {
    color: var(--chat-dark);
    font-weight: 600;
}
</style>

<div class="chat-page-wrapper">
    <!-- Breadcrumb -->
    <div class="chat-breadcrumb">
        <a href="?pages=chat"><i class='bx bx-chevron-left'></i> Back to Chats</a>
        <span>Thread #<?= htmlspecialchars($threadId) ?></span>
    </div>

    <div class="chat-container">
        <!-- Main chat panel -->
        <div class="chat-main">
            <!-- Header -->
            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="chat-header-avatar">
                        <?= strtoupper(substr($thread['visitor_name'] ?? 'G', 0, 1)) ?>
                    </div>
                    <div class="chat-header-details">
                        <h2><?= htmlspecialchars($thread['visitor_name'] ?: 'Guest Visitor') ?></h2>
                        <div class="subtitle"><?= htmlspecialchars($thread['visitor_email'] ?? 'No email provided') ?></div>
                    </div>
                </div>
                <div class="chat-header-meta">
                    <span class="chat-status-badge <?= ($thread['status'] ?? 'open') === 'closed' ? 'closed' : 'open' ?>">
                        <?= htmlspecialchars($thread['status'] ?? 'open') ?>
                    </span>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="messagesBox">
                <?php foreach($msgs as $m): ?>
                    <?php 
                    $isStaff = $m['is_from_staff']; 
                    $senderName = $isStaff ? ($m['sender_name'] ?: 'Staff') : ($m['sender_name'] ?: 'Visitor');
                    $initial = strtoupper(substr($senderName, 0, 1));
                    $time = date('M j, g:i A', strtotime($m['created_at']));
                    ?>
                    <div class="message-wrapper <?= $isStaff ? 'staff' : 'visitor' ?>">
                        <div class="message-avatar">
                            <i class='bx <?= $isStaff ? "bxs-user-badge" : "bxs-user" ?>'></i>
                        </div>
                        <div class="message-content">
                            <div class="message-meta">
                                <strong><?= htmlspecialchars($senderName) ?></strong> · <?= $time ?>
                            </div>
                            <div class="message-bubble">
                                <?= sanitizeMessageHtml($m['message']) ?>
                                
                                <?php
                                // Load attachments from chat_attachments table
                                try {
                                    $attStmt = $pdo->prepare('SELECT id, file_url, original_name, mime_type, created_at FROM chat_attachments WHERE message_id = ?');
                                    $attStmt->execute([$m['id']]);
                                    $atts = $attStmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (!empty($atts)): ?>
                                        <div class="message-attachments">
                                            <?php foreach ($atts as $att): 
                                                $a = $att['file_url'];
                                                $downloadUrl = app_url('public/download_attachment.php?file=' . urlencode(basename($a)));
                                                $origName = $att['original_name'] ?: basename($a);
                                                $mime = $att['mime_type'] ?: '';
                                                $created = $att['created_at'] ?? '';
                                                
                                                // Compute file size
                                                $fsPath = realpath(__DIR__ . '/../../public/' . $a);
                                                $sizeHuman = '';
                                                if ($fsPath && is_file($fsPath)) {
                                                    $sz = filesize($fsPath);
                                                    if ($sz >= 1024*1024) $sizeHuman = round($sz / (1024*1024), 2) . ' MB';
                                                    elseif ($sz >= 1024) $sizeHuman = round($sz / 1024, 2) . ' KB';
                                                    else $sizeHuman = $sz . ' B';
                                                }

                                                $ext = strtolower(pathinfo($a, PATHINFO_EXTENSION));
                                                $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                                            ?>
                                                <div class="attachment-card">
                                                    <?php if ($isImage): ?>
                                                        <img src="<?= htmlspecialchars(app_url($a)) ?>" class="attachment-thumb" alt="<?= htmlspecialchars($origName) ?>">
                                                    <?php else: ?>
                                                        <div class="attachment-thumb-icon">
                                                            <i class='bx <?= $ext === "pdf" ? "bxs-file-pdf" : "bxs-file-doc" ?>'></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="attachment-info">
                                                        <div class="attachment-name" title="<?= htmlspecialchars($origName) ?>"><?= htmlspecialchars($origName) ?></div>
                                                        <div class="attachment-meta"><?= htmlspecialchars($sizeHuman) ?></div>
                                                    </div>
                                                    <div class="attachment-actions">
                                                        <a class="attachment-btn download" href="<?= htmlspecialchars($downloadUrl) ?>" target="_blank">
                                                            <i class='bx bx-download'></i>
                                                        </a>
                                                        <button class="attachment-btn delete btn-delete-attachment" data-id="<?= intval($att['id']) ?>" title="Delete">
                                                            <i class='bx bx-trash'></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif;
                                } catch (Throwable $_) {
                                    // chat_attachments table may not exist
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Reply input -->
            <div class="chat-footer">
                <form id="replyForm" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(generateToken('chat_form')) ?>">
                    <div class="chat-input-wrapper">
                        <div class="chat-input-area">
                            <textarea name="message" class="chat-textarea" placeholder="Type your reply..." rows="1"></textarea>
                            <div class="chat-file-input">
                                <label class="chat-file-label">
                                    <i class='bx bx-paperclip'></i>
                                    <span>Attach files</span>
                                    <input type="file" name="attachments[]" multiple style="display:none;">
                                </label>
                                <span id="fileCount" style="font-size:0.85rem;color:#64748b;"></span>
                            </div>
                        </div>
                        <button type="submit" class="chat-send-btn">
                            <i class='bx bxs-send'></i>
                            Send
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="sidebar-card">
                <h4>Thread Details</h4>
                <div class="sidebar-info-item">
                    <span class="sidebar-info-label">Thread ID</span>
                    <span class="sidebar-info-value">#<?= htmlspecialchars($threadId) ?></span>
                </div>
                <div class="sidebar-info-item">
                    <span class="sidebar-info-label">Status</span>
                    <span class="sidebar-info-value"><?= htmlspecialchars(ucfirst($thread['status'] ?? 'open')) ?></span>
                </div>
                <div class="sidebar-info-item">
                    <span class="sidebar-info-label">Assigned To</span>
                    <span class="sidebar-info-value"><?= htmlspecialchars($thread['assigned_admin_name'] ?? 'You') ?></span>
                </div>
                <div class="sidebar-info-item">
                    <span class="sidebar-info-label">Created</span>
                    <span class="sidebar-info-value"><?= date('M j, Y', strtotime($thread['created_at'] ?? 'now')) ?></span>
                </div>
                <div class="sidebar-info-item">
                    <span class="sidebar-info-label">Last Activity</span>
                    <span class="sidebar-info-value"><?= date('M j, g:i A', strtotime($thread['last_activity'] ?? 'now')) ?></span>
                </div>
            </div>

            <div class="sidebar-card">
                <h4>Actions</h4>
                <div class="sidebar-actions">
                    <button id="closeThreadBtn" class="sidebar-btn danger">
                        <i class='bx bxs-lock'></i>
                        Close Thread
                    </button>
                    <a href="?pages=chat" class="sidebar-btn secondary">
                        <i class='bx bx-arrow-back'></i>
                        Back to All Chats
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Auto-resize textarea
document.querySelector('.chat-textarea').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 150) + 'px';
});

// File count display
document.querySelector('input[name="attachments[]"]').addEventListener('change', function() {
    const count = this.files.length;
    document.getElementById('fileCount').textContent = count > 0 ? count + ' file(s) selected' : '';
});

// Reply form submission
document.getElementById('replyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fd.append('action', 'reply');
    fd.append('thread_id', '<?= $threadId ?>');

    var btn = this.querySelector('.chat-send-btn');
    var originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Sending...';
    btn.disabled = true;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?pages=chat', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        try {
            var r = JSON.parse(xhr.responseText);
        } catch(e) {
            Swal.fire({icon:'error', title:'Error', text:'Invalid server response.', customClass:{popup:'hq-swal'}});
            btn.innerHTML = originalText;
            btn.disabled = false;
            return;
        }
        if (r.status === 'ok') {
            Swal.fire({icon:'success', title:'Sent!', text:'Your reply has been posted.', timer:1500, showConfirmButton:false, customClass:{popup:'hq-swal'}})
                .then(() => location.reload());
        } else {
            Swal.fire({icon:'error', title:'Failed', text:'Could not send your reply.', customClass:{popup:'hq-swal'}});
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };
    xhr.send(fd);
});

// Poll messages every 5 seconds
setInterval(function() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?pages=chat_view&thread_id=<?= $threadId ?>&ajax=1&_=' + Date.now(), true);
    xhr.onload = function() {
        if (xhr.status !== 200) return;
        try {
            var parser = new DOMParser();
            var doc = parser.parseFromString(xhr.responseText, 'text/html');
            var messagesContainer = doc.querySelector('#messagesBox');
            if (messagesContainer) {
                var target = document.querySelector('#messagesBox');
                if (target) {
                    target.innerHTML = messagesContainer.innerHTML;
                    target.scrollTop = target.scrollHeight;
                }
            }
        } catch(e) {}
    };
    xhr.send();
}, 5000);

// Close thread
document.getElementById('closeThreadBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Close this thread?',
        text: "The conversation will be marked as closed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffd600',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, close it',
        customClass: {popup: 'hq-swal'}
    }).then((result) => {
        if (!result.isConfirmed) return;

        var fd = new FormData();
        fd.append('action', 'close');
        fd.append('thread_id', '<?= $threadId ?>');
        fd.append('_csrf', document.querySelector('input[name="_csrf"]').value);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?pages=chat', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            try { var r = JSON.parse(xhr.responseText); }
            catch(e) {
                Swal.fire({icon:'error', title:'Error', text:'Invalid server response.', customClass:{popup:'hq-swal'}});
                return;
            }
            if (r.status === 'ok') {
                Swal.fire({icon:'success', title:'Closed!', text:'The thread has been closed.', customClass:{popup:'hq-swal'}})
                    .then(() => window.location.href = 'index.php?pages=chat');
            } else {
                Swal.fire({icon:'error', title:'Failed', text:'Could not close the thread.', customClass:{popup:'hq-swal'}});
            }
        };
        xhr.send(fd);
    });
});

// Scroll to bottom on load
document.addEventListener('DOMContentLoaded', function() {
    var box = document.getElementById('messagesBox');
    if (box) box.scrollTop = box.scrollHeight;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php';

// Attachment delete handler
echo "<script>
document.addEventListener('click', function(e) {
    if (!e.target.closest('.btn-delete-attachment')) return;
    var btn = e.target.closest('.btn-delete-attachment');
    var id = btn.dataset.id;
    if (!id) return;
    
    Swal.fire({
        title: 'Delete Attachment?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffd600',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it',
        customClass: {popup: 'hq-swal'}
    }).then((result) => {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('id', id);
            fd.append('_csrf', '".generateToken('chat_form')."');
            
            fetch((window.HQ_ADMIN_BASE || '') + '/api/delete_attachment.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'ok') {
                    Swal.fire({icon:'success', title:'Deleted!', timer:1500, showConfirmButton:false, customClass:{popup:'hq-swal'}})
                    .then(() => location.reload());
                } else {
                    Swal.fire({icon:'error', title:'Error', text:j.message || 'Delete failed', customClass:{popup:'hq-swal'}});
                }
            })
            .catch(e => {
                Swal.fire({icon:'error', title:'Error', text:'Delete failed', customClass:{popup:'hq-swal'}});
            });
        }
    });
}, false);
</script>";
