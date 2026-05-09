<?php
/**
 * Test Chat Widget - Chatbot Demo Page
 * Visit this page to test the floating chat widget with chatbot
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Widget Demo - HIGH-Q</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .demo-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header-section {
            text-align: center;
            color: #fff;
            margin-bottom: 60px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-section h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .header-section p {
            font-size: 18px;
            opacity: 0.95;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 60px;
        }
        .feature-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .feature-card i {
            font-size: 48px;
            color: #ffbf00;
            margin-bottom: 16px;
            display: block;
        }
        .feature-card h3 {
            font-size: 20px;
            color: #111;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .feature-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }
        .cta-section {
            text-align: center;
            background: #fff;
            border-radius: 16px;
            padding: 48px 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .cta-section h2 {
            font-size: 32px;
            color: #111;
            margin-bottom: 16px;
        }
        .cta-section p {
            font-size: 16px;
            color: #666;
            margin-bottom: 32px;
        }
        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #ffbf00, #d99a00);
            color: #111;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(255,191,0,0.3);
        }
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(255,191,0,0.4);
        }
        .instructions {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-top: 32px;
            border-left: 4px solid #ffbf00;
        }
        .instructions h3 {
            color: #111;
            margin-bottom: 16px;
        }
        .instructions ol {
            color: #666;
            line-height: 1.8;
            margin-left: 20px;
        }
        .instructions li {
            margin-bottom: 12px;
        }
        .badge-demo {
            display: inline-block;
            background: #ffbf00;
            color: #111;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="header-section">
            <h1>🤖 Chat Widget Demo</h1>
            <p>Experience our intelligent chatbot with FAQ support and live agent escalation</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <i class="bx bx-bot"></i>
                <h3>Smart Chatbot</h3>
                <p>AI-powered bot answers common questions instantly about registration, programs, payments, and more.</p>
            </div>
            <div class="feature-card">
                <i class="bx bx-message-dots"></i>
                <h3>FAQ Options</h3>
                <p>6 predefined FAQ categories help users get quick answers without waiting for an agent.</p>
            </div>
            <div class="feature-card">
                <i class="bx bx-headphone"></i>
                <h3>Live Agent</h3>
                <p>Seamlessly escalate to a live agent for personalized support and complex questions.</p>
            </div>
            <div class="feature-card">
                <i class="bx bx-save"></i>
                <h3>Thread Persistence</h3>
                <p>Chat history is saved using localStorage. Close and reopen the widget without losing conversation.</p>
            </div>
            <div class="feature-card">
                <i class="bx bx-mobile"></i>
                <h3>Mobile Optimized</h3>
                <p>Responsive design adapts perfectly to phones, tablets, and desktop screens.</p>
            </div>
            <div class="feature-card">
                <i class="bx bx-shield-alt"></i>
                <h3>Secure & Fast</h3>
                <p>All messages are encrypted, stored in database, and loaded instantly when reopening chat.</p>
            </div>
        </div>

        <div class="cta-section">
            <h2>Try It Now</h2>
            <p>Click the floating chat button in the bottom-right corner to start chatting</p>
            <button class="cta-button">
                <i class="bx bx-chat"></i>
                Click Me - Chat Now!
            </button>
            <div class="badge-demo">⬇️ Look for the yellow button in the bottom-right</div>

            <div class="instructions">
                <h3>📖 How to Use the Chat Widget</h3>
                <ol>
                    <li><strong>Click the yellow chat button</strong> in the bottom-right corner</li>
                    <li><strong>Choose an FAQ option:</strong>
                        <ul style="margin-top: 8px;">
                            <li>How to Register</li>
                            <li>Available Programs</li>
                            <li>Payment Options</li>
                            <li>Community Q&amp;A</li>
                            <li>Contact Us</li>
                            <li>Talk to Agent</li>
                        </ul>
                    </li>
                    <li><strong>Get instant answers</strong> from the bot (or escalate to agent)</li>
                    <li><strong>Start live chat</strong> with an agent if needed</li>
                    <li><strong>Message history is saved</strong> - close and reopen anytime</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Include the global chat widget -->
    <?php
        // Simple function to handle app_url if not loaded
        if (!function_exists('app_url')) {
            function app_url($path = '') {
                $base = 'http://' . $_SERVER['HTTP_HOST'] . '/HIGH-Q/public/';
                return $base . ltrim($path, '/');
            }
        }
    ?>
    
    <!-- Chat Widget Component -->
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
                        <button class="faq-option" data-question="Do you have a community forum?">
                            <i class="bx bx-group"></i>
                            <span>Community Q&amp;A</span>
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
            animation:slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
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
            height:100%; animation:fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
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
            display:flex; gap:10px; animation:messageSlide 0.3s ease;
        }
        @keyframes messageSlide { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
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
            animation:fadeIn 0.3s ease;
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
        .attachment-preview { display:none; }

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
            const startAgentFormEl = document.getElementById('startAgentForm');
            
            const faqData = {
                "How do I register for a program?": "To register, visit our <strong>Programs</strong> page, select your desired program, and click <strong>Find Your Path</strong>. Fill out the form and complete payment to secure your spot!",
                "What programs are available?": "We offer JAMB, WAEC, POST-UTME, and professional tutoring programs. Check out our Programs page to learn more.",
                "What are your payment options?": "We accept bank transfers, Paystack, and Stripe payments. You'll receive payment instructions after registration.",
                "Do you have a community forum?": "Yes! Join our Community Q&amp;A to ask questions, share tips, and get support from other students and tutors.",
                "What are your contact details?": "Email: <strong>highqsolidacademy@gmail.com</strong><br>Phone: <strong>+234 807 208 8794</strong><br>Or chat with us right here!"
            };
            
            let threadId = null;
            let chatMode = 'bot';
            
            function loadThreadId() {
                threadId = localStorage.getItem('hq_chat_thread_id');
                return threadId;
            }
            
            function saveThreadId(id) {
                threadId = id;
                localStorage.setItem('hq_chat_thread_id', id);
            }
            
            chatToggle.addEventListener('click', () => {
                chatPanel.classList.toggle('show');
            });
            
            chatClose.addEventListener('click', () => {
                chatPanel.classList.remove('show');
            });
            
            // FAQ option click
            document.querySelectorAll('.faq-option').forEach(btn => {
                btn.addEventListener('click', () => {
                    const question = btn.dataset.question;
                    
                    if (question === "Other question") {
                        showAgentForm();
                    } else {
                        showBotResponse(question);
                    }
                });
            });
            
            function showBotResponse(question) {
                chatBotLanding.style.display = 'none';
                chatMessages.style.display = 'flex';
                chatMode = 'bot';
                
                addMessage('user', 'You', question);
                
                setTimeout(() => {
                    const answer = faqData[question] || "I'm not sure about that. Would you like to talk to an agent?";
                    addMessage('bot', 'Bot', answer);
                    
                    setTimeout(() => {
                        const agentBtn = document.createElement('div');
                        agentBtn.className = 'chat-message bot';
                        agentBtn.innerHTML = `
                            <div class="message-avatar"><i class="bx bx-bot"></i></div>
                            <div class="message-content">
                                <p style="margin:0 0 12px 0;">Need more help?</p>
                                <button class="btn-submit" onclick="window.hqChatShowAgentForm()" style="font-size:13px; padding:8px 16px;">Talk to Agent</button>
                            </div>
                        `;
                        chatMessages.appendChild(agentBtn);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }, 800);
                }, 600);
            }
            
            function showAgentForm() {
                chatBotLanding.style.display = 'none';
                chatMessages.style.display = 'none';
                agentForm.style.display = 'flex';
                chatMode = 'agent';
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
            
            window.hqChatShowAgentForm = showAgentForm;
        })();
    </script>
</body>
</html>
