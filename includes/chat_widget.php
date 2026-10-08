<?php
/**
 * Floating chat widget — include this at the end of <body>.
 * Only loaded for visitors / customers (NOT admin pages).
 */

require_once __DIR__ . '/chat_functions.php';

$visitor = chat_visitor();
$isGuest = $visitor['type'] === 'guest';
$base    = $baseHref ?? '';
?>

<!-- Chat Widget -->
<div id="chatWidget">
  <!-- Chat toggle bubble -->
  <button id="chatToggle" title="Chat with us" aria-label="Open chat">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
    <span id="chatBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:0.7rem;padding:2px 7px;border-radius:999px;font-weight:700;">0</span>
  </button>

  <!-- Chat panel -->
  <div id="chatPanel" style="display:none;position:fixed;bottom:96px;right:24px;width:360px;max-width:calc(100vw - 48px);height:520px;max-height:calc(100vh - 120px);background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,0.25);overflow:hidden;z-index:9998;display:none;flex-direction:column;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1e3a8a,#0ea5e9);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,0.2);display:grid;place-items:center;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
        </div>
        <div>
          <div style="font-weight:700;font-size:0.95rem;">AquaFix Support</div>
          <div style="font-size:0.75rem;opacity:0.85;">Usually replies in minutes</div>
        </div>
      </div>
      <button id="chatClose" style="background:none;border:none;color:#fff;cursor:pointer;font-size:22px;line-height:1;">×</button>
    </div>

    <!-- Messages -->
    <div id="chatMessages" style="flex:1;overflow-y:auto;padding:16px;background:#f8fafc;font-size:0.9rem;">
      <!-- messages appended here -->
    </div>

    <!-- Guest identity (shown until name+email entered) -->
    <div id="chatIdentity" style="padding:16px;background:#fff;border-top:1px solid #e2e8f0;<?= $isGuest && empty($visitor['name']) ? '' : 'display:none;' ?>">
      <div style="font-size:0.85rem;color:#6b7280;margin-bottom:10px;">Before we start, what's your name and email?</div>
      <input type="text" id="chatName" placeholder="Your name" class="form-control" style="margin-bottom:8px;width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;" value="<?= htmlspecialchars($visitor['name']) ?>">
      <input type="email" id="chatEmail" placeholder="Email (optional)" class="form-control" style="margin-bottom:10px;width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;" value="<?= htmlspecialchars($visitor['email']) ?>">
      <button id="chatStart" class="btn btn--primary btn--sm" style="width:100%;">Start Chatting</button>
    </div>

    <!-- Composer -->
    <div id="chatComposer" style="padding:12px;background:#fff;border-top:1px solid #e2e8f0;display:none;gap:8px;">
      <input type="text" id="chatInput" placeholder="Type your message..." maxlength="2000"
             style="flex:1;padding:10px 14px;border:1px solid #e2e8f0;border-radius:22px;outline:none;font-size:0.9rem;">
      <button id="chatSend" style="width:40px;height:40px;border-radius:50%;background:#0ea5e9;color:#fff;border:none;cursor:pointer;display:grid;place-items:center;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9"/></svg>
      </button>
    </div>
  </div>
</div>

<style>
  #chatToggle {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
    color: #fff;
    border: none;
    cursor: pointer;
    display: grid;
    place-items: center;
    box-shadow: 0 12px 32px rgba(30,58,138,0.35);
    transition: transform 0.2s, box-shadow 0.2s;
    z-index: 9999;
  }
  #chatToggle:hover { transform: scale(1.08); box-shadow: 0 16px 40px rgba(30,58,138,0.45); }

  .chat-msg {
    max-width: 82%;
    padding: 10px 14px;
    border-radius: 14px;
    margin-bottom: 8px;
    line-height: 1.4;
    word-wrap: break-word;
  }
  .chat-msg--visitor {
    background: #0ea5e9;
    color: #fff;
    margin-left: auto;
    border-bottom-right-radius: 4px;
  }
  .chat-msg--admin {
    background: #fff;
    color: #1f2937;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
  }
  .chat-msg__time {
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 4px;
  }
  .chat-welcome {
    text-align: center;
    color: #6b7280;
    font-size: 0.82rem;
    padding: 20px 12px;
  }
</style>

<script>
(function() {
  const BASE       = '<?= $base ?>';
  const toggle     = document.getElementById('chatToggle');
  const panel      = document.getElementById('chatPanel');
  const closeBtn   = document.getElementById('chatClose');
  const messages   = document.getElementById('chatMessages');
  const input      = document.getElementById('chatInput');
  const sendBtn    = document.getElementById('chatSend');
  const identity   = document.getElementById('chatIdentity');
  const composer   = document.getElementById('chatComposer');
  const badge      = document.getElementById('chatBadge');
  const startBtn   = document.getElementById('chatStart');
  const nameInput  = document.getElementById('chatName');
  const emailInput = document.getElementById('chatEmail');

  let lastId = 0;
  let polling = null;
  let opened = false;
  const isGuest = <?= $isGuest ? 'true' : 'false' ?>;
  const guestHasIdentity = <?= ($isGuest && !empty($visitor['name'])) ? 'true' : 'false' ?>;

  /* Toggle chat */
  toggle.addEventListener('click', () => {
    opened = !opened;
    panel.style.display = opened ? 'flex' : 'none';
    if (opened) {
      hideBadge();
      scrollToBottom();
      if (!polling) startPolling();
      input.focus();
    }
  });
  closeBtn.addEventListener('click', () => { opened = false; panel.style.display = 'none'; });

  /* Guest identity flow */
  if (startBtn) {
    startBtn.addEventListener('click', () => {
      const n = nameInput.value.trim();
      if (!n) { nameInput.focus(); return; }
      identity.style.display = 'none';
      composer.style.display = 'flex';
      input.focus();
    });
  }

  if (!isGuest || guestHasIdentity) {
    composer.style.display = 'flex';
  }

  /* Send message */
  async function send() {
    const body = input.value.trim();
    if (!body) return;

    const params = new URLSearchParams({ body });
    if (isGuest) {
      if (nameInput) params.append('name', nameInput.value.trim());
      if (emailInput) params.append('email', emailInput.value.trim());
    }

    input.value = '';
    appendMessage({ sender: 'visitor', body, created_at: new Date().toISOString() });

    try {
      const res = await fetch(BASE + 'api/chat_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
      });
      const data = await res.json();
      if (data.ok) lastId = data.message_id;
    } catch (err) {
      appendMessage({ sender: 'admin', body: '⚠️ Could not send. Try again.', created_at: new Date().toISOString() });
    }
  }

  sendBtn.addEventListener('click', send);
  input.addEventListener('keydown', e => { if (e.key === 'Enter') send(); });

  /* Poll for new messages every 3 seconds */
  async function poll() {
    try {
      const res = await fetch(BASE + 'api/chat_fetch.php?after=' + lastId);
      const data = await res.json();
      if (!data.ok) return;

      data.messages.forEach(m => {
        appendMessage(m);
        lastId = m.id;
        if (!opened && m.sender === 'admin') showBadge();
      });
    } catch (err) {}
  }
  function startPolling() {
    poll();
    polling = setInterval(poll, 3000);
  }

  /* Initial load — when opening the widget, pull any existing history */
  window.addEventListener('load', () => {
    setTimeout(() => {
      /* Load history quietly */
      fetch(BASE + 'api/chat_fetch.php?after=0')
        .then(r => r.json())
        .then(data => {
          if (data.ok && data.messages.length) {
            data.messages.forEach(m => { appendMessage(m); lastId = m.id; });
            scrollToBottom();
          }
        })
        .catch(() => {});
    }, 800);
  });

  /* UI helpers */
  function appendMessage(m) {
    const div = document.createElement('div');
    div.className = 'chat-msg chat-msg--' + (m.sender === 'admin' ? 'admin' : 'visitor');

    const time = new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    div.innerHTML = escapeHtml(m.body) + '<div class="chat-msg__time">' + time + '</div>';
    messages.appendChild(div);
    scrollToBottom();
  }

  function scrollToBottom() {
    messages.scrollTop = messages.scrollHeight;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));
  }

  function showBadge() { badge.style.display = 'inline-block'; badge.textContent = '1'; }
  function hideBadge() { badge.style.display = 'none'; }
})();
</script>