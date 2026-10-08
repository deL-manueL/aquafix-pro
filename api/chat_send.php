<?php
/**
 * AJAX endpoint: send a chat message.
 * After saving the visitor message, triggers the AI bot for an instant reply.
 * Returns: { ok, message_id, ai_reply:{...}, matched }
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/chat_functions.php';
require_once __DIR__ . '/../includes/chat_bot.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = trim($_POST['body'] ?? '');

if ($body === '') {
    echo json_encode(['ok' => false, 'error' => 'Message is empty']);
    exit;
}
if (strlen($body) > 2000) {
    echo json_encode(['ok' => false, 'error' => 'Message too long']);
    exit;
}

/* ---------- 1. Identify the visitor ---------- */
$visitor = chat_visitor();
if ($visitor['type'] === 'guest') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name !== '')  $_SESSION['chat_guest_name']  = $name;
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['chat_guest_email'] = $email;
    }
}

/* ---------- 2. Find or create the conversation ---------- */
$conv = chat_current_conversation(true);
if (!$conv) {
    echo json_encode(['ok' => false, 'error' => 'Could not start conversation']);
    exit;
}
$convId = (int)$conv['id'];

/* ---------- 3. Save the visitor's message ---------- */
$msgId = chat_send($convId, 'visitor', null, $body);
if (!$msgId) {
    echo json_encode(['ok' => false, 'error' => 'Failed to save']);
    exit;
}

/* ---------- 4. AI BOT AUTO-REPLY ---------- */
$aiReplyPayload = null;
$matched        = false;

try {
    /* Only auto-reply if a human admin hasn't replied in the last 30 seconds.
       This prevents the bot from butting into a live conversation. */
    $lastAdmin = db_run(
        "SELECT created_at FROM chat_messages
         WHERE conversation_id = ? AND sender_type = 'admin'
         ORDER BY id DESC LIMIT 1",
        [$convId]
    )->fetch();

    $shouldAutoReply = true;
    if ($lastAdmin) {
        $secondsSince = time() - strtotime($lastAdmin['created_at']);
        if ($secondsSince < 30) $shouldAutoReply = false;
    }

    if ($shouldAutoReply) {
        $userId = $_SESSION['user_id'] ?? null;
        $ai     = chat_bot_reply($body, $userId);

        if (!empty($ai['reply'])) {
            $aiMsgId = chat_send($convId, 'admin', null, $ai['reply']);

            if ($aiMsgId) {
                $row = db_run("SELECT * FROM chat_messages WHERE id = ? LIMIT 1", [$aiMsgId])->fetch();
                $aiReplyPayload = [
                    'id'         => (int)$row['id'],
                    'sender'     => 'admin',
                    'body'       => $row['body'],
                    'created_at' => $row['created_at'],
                ];
                $matched = !empty($ai['matched']);
            }
        }
    }
} catch (Exception $e) {
    /* Bot failure should never break the visitor's message from saving */
    $aiReplyPayload = null;
}

/* ---------- 5. Return everything to the widget ---------- */
$msg = db_run("SELECT * FROM chat_messages WHERE id = ? LIMIT 1", [$msgId])->fetch();

echo json_encode([
    'ok'         => true,
    'message_id' => (int)$msgId,
    'body'       => $msg['body'],
    'created_at' => $msg['created_at'],
    'ai_reply'   => $aiReplyPayload,
    'matched'    => $matched,
]);