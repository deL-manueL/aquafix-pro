<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/chat_functions.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) {
    redirect('login.php');
}

$admin = [
    'id'    => $_SESSION['admin_id']    ?? 0,
    'name'  => $_SESSION['admin_name']  ?? 'Admin',
    'email' => $_SESSION['admin_email'] ?? '',
];

/* ---- Handle admin reply ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reply') {
    $convId  = (int)($_POST['conversation_id'] ?? 0);
    $body    = trim($_POST['body'] ?? '');
    if ($convId > 0 && $body !== '') {
        chat_send($convId, 'admin', (int)$admin['id'], $body);
    }
    redirect('chats.php?conversation=' . $convId);
}

/* ---- Handle close conversation ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'close') {
    $convId = (int)($_POST['conversation_id'] ?? 0);
    if ($convId > 0) {
        db_run("UPDATE chat_conversations SET is_closed = 1 WHERE id = ?", [$convId]);
    }
    redirect('chats.php');
}

/* ---- Which conversation is open ---- */
$openId = (int)($_GET['conversation'] ?? 0);

/* ---- List of all active conversations ---- */
$conversations = db_run(
    "SELECT c.*,
            (SELECT COUNT(*) FROM chat_messages m
             WHERE m.conversation_id = c.id AND m.sender_type = 'visitor' AND m.is_read = 0) AS unread,
            (SELECT body FROM chat_messages m
             WHERE m.conversation_id = c.id ORDER BY id DESC LIMIT 1) AS last_msg
     FROM chat_conversations c
     WHERE c.is_closed = 0
     ORDER BY c.last_message_at DESC
     LIMIT 100"
)->fetchAll();

/* ---- Auto-select first conversation ---- */
if (!$openId && $conversations) {
    $openId = (int)$conversations[0]['id'];
}

/* ---- Load messages of open conversation ---- */
$messages = [];
$openConv = null;
if ($openId) {
    $openConv = db_run("SELECT * FROM chat_conversations WHERE id = ? LIMIT 1", [$openId])->fetch();
    if ($openConv) {
        $messages = chat_messages($openId);
        chat_mark_admin_messages_read($openId);
    }
}

/* ---- Sidebar unread total ---- */
$totalUnreadChats = (int) db_run(
    "SELECT COUNT(DISTINCT conversation_id) c FROM chat_messages
     WHERE sender_type = 'visitor' AND is_read = 0"
)->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Chats — <?= e(SITE_NAME) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .chat-layout { display:grid; grid-template-columns:340px 1fr; gap:20px; height:calc(100vh - 160px); }
    @media (max-width:900px) { .chat-layout { grid-template-columns:1fr; height:auto; } }

    .conv-list { background:#fff; border:1px solid var(--c-border); border-radius:14px; overflow-y:auto; }
    .conv-item { padding:14px 16px; border-bottom:1px solid var(--c-border); cursor:pointer; text-decoration:none; display:block; color:inherit; transition: background 0.15s; }
    .conv-item:hover { background:#f8fafc; }
    .conv-item.active { background:#e0f2fe; border-left:4px solid #0ea5e9; }
    .conv-item__name { font-weight:600; display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:4px; }
    .conv-item__badge { background:#ef4444; color:#fff; font-size:0.7rem; padding:2px 8px; border-radius:999px; font-weight:700; }
    .conv-item__preview { font-size:0.82rem; color:var(--c-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .conv-item__time { font-size:0.7rem; color:var(--c-text-muted); margin-top:4px; }

    .chat-box { background:#fff; border:1px solid var(--c-border); border-radius:14px; display:flex; flex-direction:column; overflow:hidden; }
    .chat-box__header { padding:14px 20px; border-bottom:1px solid var(--c-border); display:flex; justify-content:space-between; align-items:center; background:#f8fafc; }
    .chat-box__messages { flex:1; overflow-y:auto; padding:20px; background:#f8fafc; }
    .chat-box__composer { padding:14px; border-top:1px solid var(--c-border); display:flex; gap:10px; background:#fff; }

    .bubble { max-width:75%; padding:10px 14px; border-radius:14px; margin-bottom:10px; line-height:1.45; font-size:0.9rem; word-wrap:break-word; }
    .bubble--visitor { background:#e0f2fe; color:#1e3a8a; border-bottom-left-radius:4px; }
    .bubble--admin   { background:#1e3a8a; color:#fff; margin-left:auto; border-bottom-right-radius:4px; }
    .bubble--ai      { background:#dbeafe; color:#1e3a8a; margin-left:auto; border-bottom-right-radius:4px; border:1px dashed #60a5fa; }
    .bubble__time { font-size:0.7rem; opacity:0.7; margin-top:4px; }
    .bubble__badge { font-size:0.7rem; font-weight:700; background:#60a5fa; color:#fff; padding:1px 6px; border-radius:6px; margin-right:6px; }

    .empty-chat { display:flex; align-items:center; justify-content:center; height:100%; color:var(--c-text-muted); font-size:0.95rem; text-align:center; padding:40px; }
  </style>
</head>
<body>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="logo">
      <span class="logo__icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
      </span>
      AquaFix<span style="color:var(--c-accent)">Pro</span>
    </div>
    <nav class="admin-nav">
      <a href="index.php?tab=overview">Overview</a>
      <a href="index.php?tab=bookings">Bookings</a>
      <a href="index.php?tab=users">Users</a>
      <a href="index.php?tab=messages">Messages</a>
      <a href="index.php?tab=reviews">Reviews</a>
      <a href="packages.php">Packages</a>
      <a href="chats.php" class="active">
        Live Chats
        <?php if ($totalUnreadChats > 0): ?>
          <span style="background:#ef4444;color:#fff;padding:2px 8px;border-radius:999px;font-size:0.7rem;margin-left:6px;"><?= $totalUnreadChats ?></span>
        <?php endif; ?>
      </a>
      <a href="index.php?tab=newsletter">Newsletter</a>
      <a href="index.php?tab=activity">Activity Log</a>
      <a href="../index.php">View Website</a>
      <a href="logout.php" style="color:#f87171;">Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h2 style="margin-bottom:4px;">Live Chats</h2>
        <p style="margin:0;font-size:0.9rem;">
          <?= count($conversations) ?> active conversation(s)
          &nbsp;•&nbsp; 🤖 = AI, 👤 = human agent
        </p>
      </div>
    </div>

    <div class="chat-layout">

      <!-- Conversation list -->
      <div class="conv-list">
        <?php if (!$conversations): ?>
          <div class="empty-chat">No conversations yet.</div>
        <?php else: ?>
          <?php foreach ($conversations as $c): ?>
            <a href="chats.php?conversation=<?= (int)$c['id'] ?>" class="conv-item <?= $openId == $c['id'] ? 'active' : '' ?>">
              <div class="conv-item__name">
                <span><?= e($c['visitor_name'] ?: 'Guest') ?></span>
                <?php if ((int)$c['unread'] > 0): ?>
                  <span class="conv-item__badge"><?= (int)$c['unread'] ?></span>
                <?php endif; ?>
              </div>
              <div class="conv-item__preview"><?= e($c['last_msg'] ?? 'No messages yet') ?></div>
              <div class="conv-item__time"><?= e(date('M j, H:i', strtotime($c['last_message_at']))) ?></div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Chat box -->
      <div class="chat-box">
        <?php if (!$openConv): ?>
          <div class="empty-chat">Select a conversation to view messages.</div>
        <?php else: ?>
          <div class="chat-box__header">
            <div>
              <strong><?= e($openConv['visitor_name'] ?: 'Guest') ?></strong>
              <?php if (!empty($openConv['visitor_email'])): ?>
                <div style="font-size:0.82rem;color:var(--c-text-muted);"><?= e($openConv['visitor_email']) ?></div>
              <?php endif; ?>
              <?php if (!empty($openConv['user_id'])): ?>
                <div style="font-size:0.75rem;color:var(--c-success);font-weight:600;">✓ Registered customer</div>
              <?php endif; ?>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
              <span style="font-size:0.78rem;color:var(--c-text-muted);">
                Started <?= e(date('M j, Y', strtotime($openConv['created_at']))) ?>
              </span>
              <form method="POST" onsubmit="return confirm('Close this conversation?');">
                <input type="hidden" name="action" value="close">
                <input type="hidden" name="conversation_id" value="<?= (int)$openConv['id'] ?>">
                <button class="btn btn--ghost btn--sm" style="color:var(--c-error);">Close chat</button>
              </form>
            </div>
          </div>

          <div class="chat-box__messages" id="chatMessages">
            <?php foreach ($messages as $m): ?>
              <?php
              $isAI = ($m['sender_type'] === 'admin' && empty($m['sender_id']));
              $cls  = $m['sender_type'] === 'visitor' ? 'visitor' : ($isAI ? 'ai' : 'admin');
              ?>
              <div class="bubble bubble--<?= $cls ?>">
                <?php if ($isAI): ?><span class="bubble__badge">🤖 AI</span><?php endif; ?>
                <?php if ($m['sender_type'] === 'admin' && !empty($m['sender_id'])): ?><span class="bubble__badge" style="background:#10b981;">👤 Admin</span><?php endif; ?>
                <?= nl2br(e($m['body'])) ?>
                <div class="bubble__time"><?= e(date('H:i', strtotime($m['created_at']))) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <form method="POST" class="chat-box__composer" id="replyForm">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="conversation_id" value="<?= (int)$openConv['id'] ?>">
            <input type="text" name="body" id="replyInput" placeholder="Type your reply as a human agent..."
                   style="flex:1;padding:12px 16px;border:1px solid var(--c-border);border-radius:24px;outline:none;font-size:0.92rem;"
                   required autocomplete="off" maxlength="2000">
            <button type="submit" class="btn btn--primary" style="padding:12px 22px;">Send as Human</button>
          </form>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<script>
const box = document.getElementById('chatMessages');
if (box) box.scrollTop = box.scrollHeight;

/* Auto-refresh messages every 5 seconds */
<?php if ($openConv): ?>
setInterval(() => {
  fetch('chats.php?conversation=<?= (int)$openConv['id'] ?>')
    .then(r => r.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newBox = doc.getElementById('chatMessages');
      if (newBox) {
        document.getElementById('chatMessages').innerHTML = newBox.innerHTML;
        document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
      }
    })
    .catch(() => {});
}, 5000);
<?php endif; ?>
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>