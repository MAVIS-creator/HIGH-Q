<!-- Global Chat Widget -->
<div id="chatWidget" class="chat-widget-container">
    <button id="chatToggle" class="chat-toggle-btn" aria-label="Open chat">
        <i class="bx bx-chat"></i>
        <span class="chat-badge" id="chatBadge" style="display:none;">1</span>
    </button>
    
    <div id="chatPanel" class="chat-panel">
        <div class="chat-widget-header">
            <div class="chat-header-left">
                <i class="bx bx-chat"></i>
                <span>Live Support</span>
            </div>
            <button id="chatClose" class="chat-close-btn" aria-label="Close chat">
                <i class="bx bx-x"></i>
            </button>
        </div>
        
        <div class="chat-widget-body" id="chatWidgetBody">
            <!-- Landing: Bot or Agent -->
            <div id="chatBotLanding" class="chat-bot-landing">
                <div class="bot-welcome">
                    <div class="bot-avatar">
                        <i class="bx bx-bot"></i>
                    </div>
                    <h3>Hi there! 👋</h3>
                    <p>How can we help you today?</p>
                </div>
                
                <div class="faq-options">
                    <button class="faq-option" data-question="How do I register for a program?">
                        <i class="bx bx-user-plus"></i>
                        <span>How to Register</span>
                    </button>
                    <button class="faq-option" data-question="What programs are available?">
                        <i class="bx bx-book-open"></i>
                        <span>Available Programs</span>
                    </button>
                    <button class="faq-option" data-question="What are your payment options?">
                        <i class="bx bx-credit-card"></i>
                        <span>Payment Options</span>
                    </button>
                    <button class="faq-option" data-question="How do I check my admission status?">
                        <i class="bx bx-file-find"></i>
                        <span>Check Admission</span>
                    </button>
                    <button class="faq-option" data-question="What are your contact details?">
                        <i class="bx bx-phone"></i>
                        <span>Contact Us</span>
                    </button>
                    <button class="faq-option" data-question="Other question">
                        <i class="bx bx-message-dots"></i>
                        <span>Talk to Agent</span>
                    </button>
                </div>
            </div>
            
            <!-- Chat Messages -->
            <div id="chatMessages" class="chat-messages" style="display:none;"></div>
            
            <!-- Agent Form (for first contact) -->
            <div id="agentForm" class="agent-form" style="display:none;">
                <div class="form-header">
                    <i class="bx bx-user"></i>
                    <h4>Connect with an Agent</h4>
                    <p>Please provide your details to start chatting</p>
                </div>
                <form id="startAgentForm">
                    <input type="text" id="agentName" placeholder="Your Name" required>
                    <input type="email" id="agentEmail" placeholder="Your Email" required>
                    <textarea id="agentMessage" placeholder="How can we help you?" rows="3" required></textarea>
                    <button type="submit" class="btn-submit">Start Chat</button>
                </form>
            </div>
        </div>
        
        <!-- Chat Input Footer -->
        <div id="chatFooter" class="chat-widget-footer" style="display:none;">
            <button type="button" class="btn-attach" id="chatAttachBtn" title="Attach file">
                <i class="bx bx-paperclip"></i>
            </button>
            <input type="file" id="chatAttachment" style="display:none;" multiple accept="image/*,.pdf,.docx">
            <div class="chat-input-wrapper">
                <textarea id="chatInput" placeholder="Type your message..." rows="1"></textarea>
            </div>
            <button type="button" class="btn-send" id="chatSendBtn">
                <i class="bx bx-send"></i>
            </button>
            <div class="attachment-preview" id="chatAttachPreview"></div>
        </div>
    </div>
</div>

<style>
.chat-widget-container { position:fixed; bottom:20px; right:20px; z-index:9999; font-family:Inter,sans-serif; }

.chat-toggle-btn {
    width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, #ffbf00, #d99a00);
    border:none; box-shadow:0 8px 24px rgba(0,0,0,0.15); cursor:pointer; display:flex; align-items:center;
    justify-content:center; transition:all 0.3s ease; position:relative;
}
.chat-toggle-btn:hover { transform:scale(1.05); box-shadow:0 10px 30px rgba(0,0,0,0.2); }
.chat-toggle-btn i { font-size:28px; color:#111; }
.chat-badge {
    position:absolute; top:-4px; right:-4px; background:#ff4b2b; color:#fff; border-radius:50%;
    width:22px; height:22px; font-size:11px; font-weight:700; display:flex; align-items:center;
    justify-content:center; border:2px solid #fff;
}

.chat-panel {
    position:absolute; bottom:80px; right:0; width:380px; max-width:calc(100vw - 40px);
    height:600px; max-height:calc(100vh - 120px); background:#fff; border-radius:16px;
    box-shadow:0 12px 48px rgba(0,0,0,0.18); display:none; flex-direction:column; overflow:hidden;
}
.chat-panel.show { display:flex; }

.chat-widget-header {
    background:linear-gradient(135deg, #ffbf00, #d99a00); padding:16px 20px; display:flex;
    align-items:center; justify-content:space-between; color:#111; box-shadow:0 2px 10px rgba(0,0,0,0.08);
}
.chat-header-left { display:flex; align-items:center; gap:10px; font-weight:600; font-size:16px; }
.chat-header-left i { font-size:22px; }
.chat-close-btn {
    background:rgba(0,0,0,0.1); border:none; width:32px; height:32px; border-radius:50%;
    cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s ease;
}
.chat-close-btn:hover { background:rgba(0,0,0,0.2); transform:rotate(90deg); }
.chat-close-btn i { font-size:20px; color:#111; }

.chat-widget-body {
    flex:1; overflow-y:auto; background:#f9fafb; display:flex; flex-direction:column;
}

/* Bot Landing */
.chat-bot-landing {
    padding:24px; display:flex; flex-direction:column; gap:20px; align-items:center; justify-content:center;
    height:100%;
}
.bot-welcome { text-align:center; }
.bot-avatar {
    width:80px; height:80px; background:linear-gradient(135deg, #ffbf00, #d99a00); border-radius:50%;
    display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:0 8px 20px rgba(255,191,0,0.3);
}
.bot-avatar i { font-size:42px; color:#111; }
.bot-welcome h3 { margin:0 0 8px 0; font-size:20px; color:#111; font-weight:600; }
.bot-welcome p { margin:0; color:#666; font-size:14px; }

.faq-options {
    display:grid; grid-template-columns:1fr; gap:10px; width:100%; max-width:320px;
}
.faq-option {
    background:#fff; border:2px solid #e5e7eb; border-radius:12px; padding:14px 16px; cursor:pointer;
    display:flex; align-items:center; gap:12px; transition:all 0.2s ease; text-align:left; font-size:14px;
    font-weight:600; color:#374151;
}
.faq-option:hover { border-color:#ffbf00; background:#fffbf0; transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.06); }
.faq-option i { font-size:20px; color:#ffbf00; flex-shrink:0; }
.faq-option span { flex:1; }

/* Chat Messages */
.chat-messages {
    padding:16px; display:flex; flex-direction:column; gap:12px; overflow-y:auto; height:100%;
}
.chat-message {
    display:flex; gap:10px;
}
.chat-message.bot { align-self:flex-start; }
.chat-message.user { align-self:flex-end; flex-direction:row-reverse; }
.chat-message.agent { align-self:flex-start; }

.message-avatar {
    width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    flex-shrink:0; font-size:18px;
}
.bot .message-avatar { background:linear-gradient(135deg, #ffbf00, #d99a00); color:#111; }
.agent .message-avatar { background:#6366f1; color:#fff; }
.user .message-avatar { background:#10b981; color:#fff; }

.message-content {
    max-width:75%; background:#fff; padding:10px 14px; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.06);
    font-size:14px; line-height:1.5; color:#374151;
}
.user .message-content { background:#ffbf00; color:#111; }

/* Agent Form */
.agent-form {
    padding:24px; display:flex; flex-direction:column; gap:16px; height:100%; justify-content:center;
}
.form-header { text-align:center; margin-bottom:8px; }
.form-header i { font-size:48px; color:#ffbf00; margin-bottom:8px; }
.form-header h4 { margin:0 0 8px 0; font-size:18px; color:#111; font-weight:600; }
.form-header p { margin:0; font-size:13px; color:#666; }
.agent-form input, .agent-form textarea {
    width:100%; box-sizing:border-box; padding:12px 14px; border:2px solid #e5e7eb; border-radius:10px;
    font-size:14px; font-family:inherit; transition:all 0.2s ease;
}
.agent-form input:focus, .agent-form textarea:focus {
    outline:none; border-color:#ffbf00; box-shadow:0 0 0 4px rgba(255,191,0,0.1);
}
.agent-form textarea { resize:vertical; min-height:80px; }
.btn-submit {
    background:linear-gradient(135deg, #ffbf00, #d99a00); border:none; color:#111; padding:14px 20px;
    border-radius:10px; font-size:15px; font-weight:600; cursor:pointer; transition:all 0.2s ease;
    box-shadow:0 4px 12px rgba(255,191,0,0.3);
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(255,191,0,0.4); }
.btn-submit.btn-compact { font-size:13px; padding:8px 14px; }

/* Chat Footer */
.chat-widget-footer {
    background:#fff; border-top:2px solid #e5e7eb; padding:12px 16px; display:flex; align-items:center;
    gap:10px;
}
.btn-attach, .btn-send {
    background:transparent; border:none; cursor:pointer; font-size:20px; color:#6b7280;
    transition:all 0.2s ease; width:36px; height:36px; display:flex; align-items:center;
    justify-content:center; border-radius:8px;
}
.btn-attach:hover, .btn-send:hover { background:#f3f4f6; color:#111; }
.btn-send { background:#ffbf00; color:#111; }
.btn-send:hover { background:#d99a00; }
.chat-input-wrapper { flex:1; }
.chat-input-wrapper textarea {
    width:100%; box-sizing:border-box; border:2px solid #e5e7eb; border-radius:10px; padding:10px 12px;
    font-size:14px; font-family:inherit; resize:none; max-height:100px; transition:border-color 0.2s ease;
}
.chat-input-wrapper textarea:focus { outline:none; border-color:#ffbf00; }
.attachment-preview { display:none; /* implement later */ }

@media (max-width:480px) {
    .chat-panel { width:calc(100vw - 20px); bottom:70px; right:10px; }
    .chat-widget-container { bottom:10px; right:10px; }
}
</style>

<script>
(function() {
    const chatToggle = document.getElementById('chatToggle');
    const chatClose = document.getElementById('chatClose');
    const chatPanel = document.getElementById('chatPanel');
    const chatBotLanding = document.getElementById('chatBotLanding');
    const chatMessages = document.getElementById('chatMessages');
    const agentForm = document.getElementById('agentForm');
    const chatFooter = document.getElementById('chatFooter');
    const chatInput = document.getElementById('chatInput');
    const chatSendBtn = document.getElementById('chatSendBtn');
    const chatAttachBtn = document.getElementById('chatAttachBtn');
    const chatAttachment = document.getElementById('chatAttachment');
    const chatAttachPreview = document.getElementById('chatAttachPreview');
    const startAgentFormEl = document.getElementById('startAgentForm');
    
    const faqData = {
        "How do I register for a program?": "To register, visit our <strong>Programs</strong> page, select your desired program, and click <strong>Find Your Path</strong>. Fill out the form and complete payment to secure your spot!",
        "What programs are available?": "We offer JAMB, WAEC, POST-UTME, and professional tutoring programs. Visit our <a href='<?= app_url('programs.php') ?>' target='_parent'>Programs page</a> to learn more.",
        "What are your payment options?": "We accept bank transfers, Paystack, and Stripe payments. You'll receive payment instructions after registration.",
        "How do I check my admission status?": "Login to your dashboard and navigate to <strong>My Registrations</strong> to view your admission status and payment history.",
        "What are your contact details?": "Email: <strong>highqsolidacademy@gmail.com</strong><br>Phone: <strong>+234 XXX XXX XXXX</strong><br>Or chat with us right here!"
    };
    
    let threadId = null;
    let chatMode = 'bot'; // 'bot' or 'agent'
    let pollTimer = null;

    const STORAGE_THREAD = 'hq_chat_thread_id';
    const STORAGE_MODE = 'hq_chat_mode';
    const STORAGE_OPEN = 'hq_chat_open';

    function loadThreadId() {
        threadId = localStorage.getItem(STORAGE_THREAD);
        return threadId;
    }

    function saveThreadId(id) {
        threadId = id;
        localStorage.setItem(STORAGE_THREAD, id);
    }

    function clearThreadId() {
        threadId = null;
        localStorage.removeItem(STORAGE_THREAD);
    }

    function loadChatMode() {
        const saved = localStorage.getItem(STORAGE_MODE);
        if (saved === 'agent' || saved === 'bot') chatMode = saved;
        return chatMode;
    }

    function saveChatMode(mode) {
        chatMode = mode;
        localStorage.setItem(STORAGE_MODE, mode);
    }

    function setChatOpen(isOpen) {
        localStorage.setItem(STORAGE_OPEN, isOpen ? '1' : '0');
    }

    function wasChatOpen() {
        return localStorage.getItem(STORAGE_OPEN) === '1';
    }
    
    chatToggle.addEventListener('click', () => {
        chatPanel.classList.toggle('show');
        const isOpen = chatPanel.classList.contains('show');
        setChatOpen(isOpen);
        if (isOpen) {
            const tid = loadThreadId();
            loadChatMode();
            if (tid) {
                if (chatMode !== 'agent') saveChatMode('agent');
                showAgentChat();
                loadMessages();
                startPolling();
            }
        } else {
            stopPolling();
        }
    });
    
    chatClose.addEventListener('click', () => {
        chatPanel.classList.remove('show');
        setChatOpen(false);
        stopPolling();
    });
    
    // FAQ option click
    document.querySelectorAll('.faq-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const question = btn.dataset.question;
            
            if (question === "Other question") {
                // Show agent form
                showAgentForm();
            } else {
                // Show bot answer
                showBotResponse(question);
            }
        });
    });
    
    function showBotResponse(question) {
        chatBotLanding.style.display = 'none';
        chatMessages.style.display = 'flex';
        chatFooter.style.display = 'none';
        saveChatMode('bot');
        
        // Add user question
        addMessage('user', 'You', question);
        
        // Add bot answer
        setTimeout(() => {
            const answer = faqData[question] || "I'm not sure about that. Would you like to talk to an agent?";
            addMessage('bot', 'Bot', answer);
            
            // Show "Talk to Agent" button
            setTimeout(() => {
                const agentBtn = document.createElement('div');
                agentBtn.className = 'chat-message bot';
                agentBtn.innerHTML = `
                    <div class="message-avatar"><i class="bx bx-bot"></i></div>
                    <div class="message-content">
                        <p style="margin:0 0 12px 0;">Need more help?</p>
                        <button class="btn-submit btn-compact" onclick="window.hqChatShowAgentForm()">Talk to Agent</button>
                    </div>
                `;
                chatMessages.appendChild(agentBtn);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 800);
        }, 600);
    }

    function showBotLanding() {
        chatBotLanding.style.display = 'flex';
        chatMessages.style.display = 'none';
        agentForm.style.display = 'none';
        chatFooter.style.display = 'none';
        chatMessages.innerHTML = '';
        saveChatMode('bot');
    }

    function showAgentForm() {
        chatBotLanding.style.display = 'none';
        chatMessages.style.display = 'none';
        agentForm.style.display = 'flex';
        chatFooter.style.display = 'none';
        saveChatMode('agent');
    }
    
    function showAgentChat() {
        chatBotLanding.style.display = 'none';
        chatMessages.style.display = 'flex';
        agentForm.style.display = 'none';
        chatFooter.style.display = 'flex';
        saveChatMode('agent');
    }
    
    function addMessage(type, sender, message) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-message ${type}`;
        
        let icon = 'bx-user';
        if (type === 'bot') icon = 'bx-bot';
        else if (type === 'agent') icon = 'bx-headphone';
        
        msgDiv.innerHTML = `
            <div class="message-avatar"><i class="bx ${icon}"></i></div>
            <div class="message-content">${message}</div>
        `;
        
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function addBackToMainButton() {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-message bot';
        msgDiv.innerHTML = `
            <div class="message-avatar"><i class="bx bx-bot"></i></div>
            <div class="message-content">
                <p style="margin:0 0 12px 0;">Back to main?</p>
                <button type="button" class="btn-submit btn-compact">Back to Main</button>
            </div>
        `;
        const btn = msgDiv.querySelector('button');
        btn.addEventListener('click', showBotLanding);
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    const allowedMime = [
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/x-pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword'
    ];
    const allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'docx', 'doc'];

    function isSupportedFile(file) {
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (allowedExt.includes(ext)) return true;
        if (!file.type) return false;
        if (file.type.startsWith('image/')) return true;
        if (allowedMime.includes(file.type)) return true;
        if (file.type.includes('pdf')) return true;
        if (file.type.includes('word')) return true;
        return false;
    }

    function updateAttachmentPreview() {
        if (!chatAttachment || !chatAttachPreview) return;
        const allFiles = Array.from(chatAttachment.files || []);
        if (allFiles.length === 0) {
            chatAttachPreview.style.display = 'none';
            chatAttachPreview.innerHTML = '';
            return;
        }

        const valid = [];
        const invalid = [];
        allFiles.forEach(file => {
            if (isSupportedFile(file)) valid.push(file); else invalid.push(file.name);
        });

        if (invalid.length > 0) {
            chatAttachPreview.style.display = 'block';
            chatAttachPreview.innerHTML = `<span style="display:inline-block;margin-right:6px;font-size:12px;color:#ef4444;">Unsupported file(s): ${invalid.join(', ')}</span>`;
        } else {
            chatAttachPreview.style.display = 'block';
            chatAttachPreview.innerHTML = valid.map(f => `<span style="display:inline-block;margin-right:6px;font-size:12px;color:#6b7280;">${f.name}</span>`).join('');
        }

        if (invalid.length > 0) {
            const dt = new DataTransfer();
            valid.forEach(f => dt.items.add(f));
            chatAttachment.files = dt.files;
        }
    }

    if (chatAttachBtn && chatAttachment) {
        chatAttachBtn.addEventListener('click', () => {
            chatAttachment.click();
        });
        chatAttachment.addEventListener('change', updateAttachmentPreview);
    }
    
    // Agent form submit
    startAgentFormEl.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const name = document.getElementById('agentName').value.trim();
        const email = document.getElementById('agentEmail').value.trim();
        const message = document.getElementById('agentMessage').value.trim();
        
        if (!name || !email || !message) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('name', name);
            formData.append('email', email);
            formData.append('message', message);
            
            const resp = await fetch('<?= app_url('chatbox.php') ?>', {
                method: 'POST',
                body: formData
            });
            
            const data = await resp.json();
            if (data.status === 'ok' && data.thread_id) {
                saveThreadId(data.thread_id);
                showAgentChat();
                addMessage('user', name, message);
                addMessage('agent', 'Agent', 'Thanks for contacting us! An agent will respond shortly.');
                startAgentFormEl.reset();
                startPolling();
            }
        } catch (err) {
            console.error('Chat error:', err);
        }
    });
    
    // Send message in agent chat
    chatSendBtn.addEventListener('click', sendAgentMessage);
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendAgentMessage();
        }
    });
    
    async function sendAgentMessage() {
        const message = chatInput.value.trim();
        const files = Array.from(chatAttachment?.files || []);
        if ((!message && files.length === 0) || !threadId) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('thread_id', threadId);
            formData.append('message', message);
            
            if (files.length > 0) {
                files.forEach(file => formData.append('attachments[]', file));
            }

            const resp = await fetch('<?= app_url('chatbox.php') ?>', {
                method: 'POST',
                body: formData
            });
            
            const data = await resp.json();
            if (data.status === 'ok') {
                addMessage('user', 'You', message);
                chatInput.value = '';
                chatInput.style.height = 'auto';
                if (chatAttachment) chatAttachment.value = '';
                updateAttachmentPreview();
            }
        } catch (err) {
            console.error('Send error:', err);
        }
    }
    
    async function loadMessages() {
        if (!threadId) return;
        
        try {
            const resp = await fetch(`<?= app_url('chatbox.php') ?>?action=get_messages&thread_id=${threadId}`);
            const data = await resp.json();
            
            if (data.status === 'ok' && data.messages) {
                if (data.thread_status && data.thread_status === 'closed') {
                    clearThreadId();
                    saveChatMode('bot');
                    stopPolling();
                    chatFooter.style.display = 'none';
                    chatMessages.style.display = 'flex';
                    chatMessages.innerHTML = '';
                    addMessage('bot', 'Bot', 'This conversation has been closed by our support team. You can start a new chat anytime.');
                    addBackToMainButton();
                    return;
                }

                chatMessages.innerHTML = '';
                data.messages.forEach(msg => {
                    const isStaff = msg.is_from_staff === 1 || msg.is_from_staff === '1' || msg.is_from_staff === true;
                    const type = isStaff ? 'agent' : 'user';
                    addMessage(type, msg.sender_name, msg.message);
                });
            }
        } catch (err) {
            console.error('Load messages error:', err);
        }
    }

    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(() => {
            if (threadId) loadMessages();
        }, 4000);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }
    
    // Auto-resize textarea
    chatInput.addEventListener('input', () => {
        chatInput.style.height = 'auto';
        chatInput.style.height = Math.min(chatInput.scrollHeight, 100) + 'px';
    });
    
    // Restore previous state on load
    loadThreadId();
    loadChatMode();
    if (threadId) {
        if (chatMode !== 'agent') saveChatMode('agent');
        showAgentChat();
        loadMessages();
        startPolling();
    }
    if (wasChatOpen()) {
        chatPanel.classList.add('show');
    }

    // Global function for bot button
    window.hqChatShowAgentForm = showAgentForm;
})();
</script>
