<?php
/**
 * Live chat helpers — session-based identity, conversation lookup, message I/O.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/* ---------- Identity ---------- */

/** Get or create a guest token for this session. */
function chat_guest_token(): string {
    if (empty($_SESSION['chat_guest_token'])) {
        $_SESSION['chat_guest_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['chat_guest_token'];
}

/** Get current visitor info: [type, id, name, email] */
function chat_visitor(): array {
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        try {
            $u = db_run("SELECT id, name, email FROM users WHERE id = ? LIMIT 1", [$userId])->fetch();
            if ($u) {
                return [
                    'type'  => 'user',
                    'id'    => (int)$u['id'],
                    'name'  => $u['name'],
                    'email' => $u['email'],
                ];
            }
        } catch (Exception $e) {}
    }

    /* Guest fallback */
    return [
        'type'  => 'guest',
        'id'    => chat_guest_token(),
        'name'  => $_SESSION['chat_guest_name']  ?? '',
        'email' => $_SESSION['chat_guest_email'] ?? '',
    ];
}

/* ---------- Conversation lookup / creation ---------- */

/** Find the current visitor's conversation, or create one. */
function chat_current_conversation(bool $createIfMissing = true): ?array {
    $visitor = chat_visitor();

    if ($visitor['type'] === 'user') {
        $conv = db_run(
            "SELECT * FROM chat_conversations WHERE user_id = ? AND is_closed = 0 ORDER BY id DESC LIMIT 1",
            [$visitor['id']]
        )->fetch();
    } else {
        $conv = db_run(
            "SELECT * FROM chat_conversations WHERE guest_token = ? AND is_closed = 0 ORDER BY id DESC LIMIT 1",
            [$visitor['id']]
        )->fetch();
    }

    if ($conv) return $conv;
    if (!$createIfMissing) return null;

    /* Create new conversation */
    if ($visitor['type'] === 'user') {
        db_run(
            "INSERT INTO chat_conversations (user_id, visitor_name, visitor_email, last_message_at, created_at)
             VALUES (?, ?, ?, NOW(), NOW())",
            [$visitor['id'], $visitor['name'], $visitor['email']]
        );
    } else {
        db_run(
            "INSERT INTO chat_conversations (guest_token, visitor_name, visitor_email, last_message_at, created_at)
             VALUES (?, ?, ?, NOW(), NOW())",
            [$visitor['id'], $visitor['name'] ?: 'Guest', $visitor['email'] ?: null]
        );
    }
    return db_run("SELECT * FROM chat_conversations WHERE id = ? LIMIT 1", [(int) db()->lastInsertId()])->fetch();
}

/* ---------- Messages ---------- */

function chat_send(int $conversationId, string $senderType, ?int $senderId, string $body): int {
    $body = trim($body);
    if ($body === '' || strlen($body) > 2000) return 0;

    db_run(
        "INSERT INTO chat_messages (conversation_id, sender_type, sender_id, body, created_at)
         VALUES (?, ?, ?, ?, NOW())",
        [$conversationId, $senderType, $senderId, $body]
    );
    $msgId = (int) db()->lastInsertId();

    db_run("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?", [$conversationId]);

    return $msgId;
}

function chat_messages(int $conversationId, int $afterId = 0): array {
    if ($afterId > 0) {
        return db_run(
            "SELECT * FROM chat_messages WHERE conversation_id = ? AND id > ? ORDER BY id ASC",
            [$conversationId, $afterId]
        )->fetchAll();
    }
    return db_run(
        "SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY id ASC",
        [$conversationId]
    )->fetchAll();
}

/* ---------- Admin helpers ---------- */

function chat_unread_admin_count(): int {
    try {
        return (int) db_run(
            "SELECT COUNT(*) c FROM chat_messages WHERE sender_type = 'visitor' AND is_read = 0"
        )->fetch()['c'];
    } catch (Exception $e) { return 0; }
}

function chat_unread_visitor_count(int $conversationId): int {
    try {
        return (int) db_run(
            "SELECT COUNT(*) c FROM chat_messages WHERE conversation_id = ? AND sender_type = 'admin' AND is_read = 0",
            [$conversationId]
        )->fetch()['c'];
    } catch (Exception $e) { return 0; }
}

function chat_mark_visitor_messages_read(int $conversationId): void {
    try {
        db_run(
            "UPDATE chat_messages SET is_read = 1
             WHERE conversation_id = ? AND sender_type = 'admin'",
            [$conversationId]
        );
    } catch (Exception $e) {}
}

function chat_mark_admin_messages_read(int $conversationId): void {
    try {
        db_run(
            "UPDATE chat_messages SET is_read = 1
             WHERE conversation_id = ? AND sender_type = 'visitor'",
            [$conversationId]
        );
    } catch (Exception $e) {}
}