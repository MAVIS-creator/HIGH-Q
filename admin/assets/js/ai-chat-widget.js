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
          <div class="hq-chat-header-sub" id="hqChatProviderLabel">Role-aware · Confirm before write</div>
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
    bubble.style.whiteSpace = 'pre-wrap';
    bubble.textContent = text;

    wrap.appendChild(avatar);
    wrap.appendChild(document.createElement('div')); // spacer column
    wrap.lastChild.appendChild(bubble);

    if (meta) {
      const metaEl = document.createElement('div');
      metaEl.className = 'hq-msg-meta';
      metaEl.textContent = meta;
      wrap.lastChild.appendChild(metaEl);
    }

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
      <div><div class="hq-msg-bubble">
        <span class="hq-typing-dot"></span>
        <span class="hq-typing-dot"></span>
        <span class="hq-typing-dot"></span>
      </div></div>`;
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
      <p><strong>Safe automation proposal detected.</strong> Review and confirm to queue it for human review.</p>
      <button class="hq-proposal-btn hq-proposal-confirm" data-proposal="${escapeAttr(proposalText)}">
        <i class='bx bx-check-shield'></i> Queue for Review
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
      const meta   = (data.provider && data.model)
        ? `via ${data.provider} / ${data.model}`
        : null;

      appendMessage('assistant', answer, meta);

      if (meta) {
        const label = document.getElementById('hqChatProviderLabel');
        if (label) label.textContent = meta;
      }

      // Heuristic: if response mentions "queue" or "review" or "proposal", show card
      if (/\b(queue|proposal|suggest|action|automat)/i.test(answer)) {
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
        appendMessage('assistant', `✅ Proposal queued for review (ID: ${data.queue_id ?? 'n/a'}). An admin will evaluate it before any changes are applied.`, null);
      } else {
        appendMessage('assistant', '⚠ Could not queue proposal: ' + (data.message || 'Unknown error'), null);
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
