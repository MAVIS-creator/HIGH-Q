/**
 * HIGH-Q Admin — Floating AI Chat Widget
 * admin/assets/js/ai-chat-widget.js
 *
 * - Permission-gated: only renders when window.HQ_AI_WIDGET_ENABLED === true
 * - Multi-turn conversation with history (in-memory)
 * - Proposal cards for write-intent suggestions
 * - Tour restart button (admin/sub-admin only)
 * - All API calls use CSRF tokens from window.HQ_CSRF
 */
(function () {
  'use strict';

  /* ── Guard: only mount if backend enabled this ── */
  if (!window.HQ_AI_WIDGET_ENABLED) return;

  const adminBase = (window.HQ_ADMIN_PATH || '').replace(/\/$/, '');
  const apiBase   = adminBase ? adminBase + '/api' : '../api';
  const csrf      = () => (window.HQ_CSRF && window.HQ_CSRF.ai)   ? window.HQ_CSRF.ai   : '';
  const tourCsrf  = () => (window.HQ_CSRF && window.HQ_CSRF.tour) ? window.HQ_CSRF.tour : '';

  let isOpen    = false;
  let isBusy    = false;
  const history = []; // {role:'user'|'assistant', content:''}

  /* ─────────────────────────────────────────────
     Build DOM
  ───────────────────────────────────────────── */
  function buildWidget() {
    /* FAB */
    const fab = document.createElement('button');
    fab.id = 'hqAiChatFab';
    fab.title = 'AI Assistant';
    fab.setAttribute('aria-label', 'Open AI Assistant');
    fab.innerHTML = "<i class='bx bx-bot'></i>";

    /* Panel */
    const panel = document.createElement('div');
    panel.id = 'hqAiChatPanel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'AI Assistant Chat');
    panel.innerHTML = `
      <div class="hq-chat-header">
        <div class="hq-chat-header-icon"><i class='bx bx-bot'></i></div>
        <div class="hq-chat-header-info">
          <div class="hq-chat-header-title">AI Assistant</div>
          <div class="hq-chat-header-sub" id="hqChatProviderLabel">Role-aware · Secure</div>
        </div>
        <div class="hq-chat-header-actions">
          <button class="hq-chat-icon-btn" id="hqChatClearBtn" title="Clear conversation" aria-label="Clear conversation">
            <i class='bx bx-trash'></i>
          </button>
          <button class="hq-chat-icon-btn" id="hqChatCloseBtn" title="Close" aria-label="Close AI Assistant">
            <i class='bx bx-x'></i>
          </button>
        </div>
      </div>
      <div class="hq-chat-safety">
        <i class='bx bx-shield-quarter'></i>
        Write actions always require your confirmation. All queries are logged.
      </div>
      <div class="hq-chat-messages" id="hqChatMessages"></div>
      <div class="hq-chat-input-area">
        <textarea id="hqChatInput" placeholder="Ask about settings, users, payments…" rows="1" autocomplete="off"></textarea>
        <button id="hqChatSendBtn" title="Send" aria-label="Send message">
          <i class='bx bx-send'></i>
        </button>
      </div>
      ${window.HQ_AI_SHOW_TOUR_BTN ? `
      <button class="hq-chat-tour-btn" id="hqRestartTourBtn">
        <i class='bx bx-map-alt'></i> Restart onboarding tour
      </button>` : ''}
    `;

    document.body.appendChild(fab);
    document.body.appendChild(panel);

    /* Wire events */
    fab.addEventListener('click', togglePanel);
    panel.querySelector('#hqChatCloseBtn').addEventListener('click', closePanel);
    panel.querySelector('#hqChatClearBtn').addEventListener('click', clearChat);

    const input   = panel.querySelector('#hqChatInput');
    const sendBtn = panel.querySelector('#hqChatSendBtn');

    sendBtn.addEventListener('click', handleSend);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
      }
    });

    /* Auto-grow textarea */
    input.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    /* Tour restart */
    const tourBtn = panel.querySelector('#hqRestartTourBtn');
    if (tourBtn) {
      tourBtn.addEventListener('click', restartTour);
    }

    /* Welcome message */
    appendMessage('assistant',
      "Hi! I'm your role-aware AI assistant. I can explain admin pages, summarise logs, draft responses, and suggest safe automation tasks.\n\nWhat would you like help with?",
      null
    );
  }

  /* ─────────────────────────────────────────────
     Panel open/close
  ───────────────────────────────────────────── */
  function togglePanel() {
    isOpen ? closePanel() : openPanel();
  }

  function openPanel() {
    isOpen = true;
    document.getElementById('hqAiChatPanel').classList.add('hq-open');
    document.getElementById('hqAiChatFab').querySelector('i').className = 'bx bx-x';
    setTimeout(() => {
      const input = document.getElementById('hqChatInput');
      if (input) input.focus();
    }, 250);
  }

  function closePanel() {
    isOpen = false;
    document.getElementById('hqAiChatPanel').classList.remove('hq-open');
    document.getElementById('hqAiChatFab').querySelector('i').className = 'bx bx-bot';
  }

  /* ─────────────────────────────────────────────
     Message rendering
  ───────────────────────────────────────────── */
  function appendMessage(role, text, meta) {
    const container = document.getElementById('hqChatMessages');
    if (!container) return;

    const isUser = (role === 'user');
    const wrap = document.createElement('div');
    wrap.className = 'hq-msg hq-msg--' + (isUser ? 'user' : 'ai');

    const avatar = document.createElement('div');
    avatar.className = 'hq-msg-avatar';
    avatar.textContent = isUser ? 'U' : 'AI';

    const bubble = document.createElement('div');
    bubble.className = 'hq-msg-bubble';
    
    if (isUser) {
      bubble.style.whiteSpace = 'pre-wrap';
      bubble.textContent = text;
    } else {
      bubble.innerHTML = parseMarkdown(text);
    }

    wrap.appendChild(avatar);
    wrap.appendChild(bubble);

    container.appendChild(wrap);
    container.scrollTop = container.scrollHeight;

    // Remember history (assistant messages only stored after response)
    history.push({ role, content: text });

    return wrap;
  }

  function appendTyping() {
    const container = document.getElementById('hqChatMessages');
    const wrap = document.createElement('div');
    wrap.className = 'hq-msg hq-msg--ai hq-typing';
    wrap.id = 'hqTypingIndicator';
    wrap.innerHTML = `
      <div class="hq-msg-avatar">AI</div>
      <div class="hq-msg-bubble">
        <span class="hq-typing-dot"></span>
        <span class="hq-typing-dot"></span>
        <span class="hq-typing-dot"></span>
      </div>`;
    container.appendChild(wrap);
    container.scrollTop = container.scrollHeight;
  }

  function removeTyping() {
    const el = document.getElementById('hqTypingIndicator');
    if (el) el.remove();
  }

  function appendProposalCard(proposalText) {
    const container = document.getElementById('hqChatMessages');
    const card = document.createElement('div');
    card.className = 'hq-proposal-card';
    card.innerHTML = `
      <p><strong>Action required.</strong> Review and confirm to automate this task.</p>
      <button class="hq-proposal-btn hq-proposal-confirm" data-proposal="${escapeAttr(proposalText)}">
        <i class='bx bx-check-shield'></i> Authorise
      </button>
      <button class="hq-proposal-btn hq-proposal-dismiss">Dismiss</button>`;

    card.querySelector('.hq-proposal-confirm').addEventListener('click', function () {
      confirmProposal(this.dataset.proposal);
      card.remove();
    });
    card.querySelector('.hq-proposal-dismiss').addEventListener('click', () => card.remove());

    container.appendChild(card);
    container.scrollTop = container.scrollHeight;
  }

  function clearChat() {
    const container = document.getElementById('hqChatMessages');
    if (!container) return;
    container.innerHTML = '';
    history.length = 0;
    appendMessage('assistant', "Chat cleared. How can I help?", null);
  }

  /* ─────────────────────────────────────────────
     Send message
  ───────────────────────────────────────────── */
  async function handleSend() {
    if (isBusy) return;

    const input   = document.getElementById('hqChatInput');
    const sendBtn = document.getElementById('hqChatSendBtn');
    const question = (input.value || '').trim();
    if (!question) return;

    input.value = '';
    input.style.height = 'auto';

    appendMessage('user', question, null);
    appendTyping();
    isBusy = true;
    sendBtn.disabled = true;

    // Build context from last 4 assistant messages for continuity
    const contextLines = history
      .filter(m => m.role === 'assistant')
      .slice(-4)
      .map(m => m.content)
      .join('\n---\n');

    const fd = new FormData();
    fd.append('question', question);
    fd.append('context', contextLines);
    fd.append('_csrf', csrf());

    try {
      const res  = await fetch(apiBase + '/ai_assistant.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      removeTyping();

      if (!res.ok || data.status !== 'ok') {
        throw new Error(data.message || 'Request failed');
      }

      const answer = data.answer || 'No response.';

      appendMessage('assistant', answer, null);

      // Heuristic: only show the proposal card if the AI explicitly used a JSON block or specific action keywords 
      // indicating an intent to modify data (e.g. UPDATE, DELETE, INSERT, or JSON tool calls).
      const stringLower = answer.toLowerCase();
      if ((stringLower.includes('```json') && stringLower.includes('action')) || 
          stringLower.includes('please confirm if you would like me to proceed') ||
          stringLower.includes('confirm to execute')) {
        appendProposalCard(answer);
      }
    } catch (err) {
      removeTyping();
      appendMessage('assistant', '⚠ Unable to reach AI right now. Please try again shortly.\n(' + (err.message || 'Unknown error') + ')', null);
    } finally {
      isBusy = false;
      sendBtn.disabled = false;
      input.focus();
    }
  }

  /* ─────────────────────────────────────────────
     Confirm / queue proposal
  ───────────────────────────────────────────── */
  async function confirmProposal(proposalText) {
    const fd = new FormData();
    fd.append('proposal', proposalText);
    fd.append('action_type', 'manual_review');
    fd.append('confirmed', '1');
    fd.append('_csrf', window.HQ_CSRF && window.HQ_CSRF.action ? window.HQ_CSRF.action : csrf());

    try {
      const res  = await fetch(apiBase + '/ai_action.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (res.ok && data.status === 'ok') {
        appendMessage('assistant', `✅ Task has been queued securely (ID: ${data.queue_id ?? 'n/a'}). It will be executed pending review.`, null);
      } else {
        appendMessage('assistant', '⚠ Could not process the request: ' + (data.message || 'Unknown error'), null);
      }
    } catch (err) {
      appendMessage('assistant', '⚠ Queue request failed. Please use the AI Queue page.', null);
    }
  }

  /* ─────────────────────────────────────────────
     Restart tour
  ───────────────────────────────────────────── */
  async function restartTour() {
    const fd = new FormData();
    fd.append('action', 'restart');
    fd.append('_csrf', tourCsrf());

    try {
      await fetch(apiBase + '/tour.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      appendMessage('assistant', '✅ Tour reset. Reload the page and the onboarding tour will start again.', null);
    } catch (e) {
      appendMessage('assistant', '⚠ Could not reset tour. Please try again.', null);
    }
  }

  /* ─────────────────────────────────────────────
     Utility
  ───────────────────────────────────────────── */
  function escapeAttr(str) {
    return (str || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function parseMarkdown(text) {
    if (!text) return '';
    // Escape HTML first to prevent XSS
    let html = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    
    // Parse Markdown
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>'); // Bold
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>'); // Italic
    html = html.replace(/`(.*?)`/g, '<code style="background:rgba(0,0,0,0.06);padding:2px 4px;border-radius:4px;font-size:0.9em;">$1</code>'); // Inline code
    
    // Convert links
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" style="color:#0056b3;text-decoration:underline;">$1</a>');

    // Handle newlines as breaks
    html = html.replace(/\n\n/g, '</p><p style="margin: 8px 0;">');
    html = html.replace(/\n/g, '<br/>');

    // Clean up lists (optional simple approach)
    html = html.replace(/<br\/>- (.*?)(?=(<br\/>|$))/g, '<li style="margin-left: 15px;">$1</li>');
    html = html.replace(/<br\/>\* (.*?)(?=(<br\/>|$))/g, '<li style="margin-left: 15px;">$1</li>');
    html = html.replace(/<br\/>(\d+)\. (.*?)(?=(<br\/>|$))/g, '<li style="margin-left: 15px;"><strong>$1.</strong> $2</li>');

    return `<div style="line-height: 1.6; margin: 0;">${html}</div>`;
  }

  /* ─────────────────────────────────────────────
     Init
  ───────────────────────────────────────────── */
  function init() {
    buildWidget();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
