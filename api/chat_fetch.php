<?php
/**
 * AJAX endpoint: fetch new chat messages.
 * GET: after (message id) — only returns messages newer than this
 * Returns: { ok, conversation_id, messages[] }
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/chat_functions.php';

header('Content-Type: application/json');

$after = (int)($_GET['after'] ?? 0);

$conv = chat_current_conversation(true);
if (!$conv) {
    echo json_encode(['ok' => false, 'error' => 'No conversation']);
    exit;
}

$messages = chat_messages((int)$conv['id'], $after);

/* Mark admin messages as read */
chat_mark_visitor_messages_read((int)$conv['id']);

$out = [];
foreach ($messages as $m) {
    $out[] = [
        'id'         => (int)$m['id'],
        'sender'     => $m['sender_type'],   // 'visitor' or 'admin'
        'body'       => $m['body'],
        'created_at' => $m['created_at'],
    ];
}

echo json_encode([
    'ok'              => true,
    'conversation_id' => (int)$conv['id'],
    'messages'        => $out,
]);