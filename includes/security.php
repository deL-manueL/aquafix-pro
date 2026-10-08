<?php
/**
 * Security helpers — CSRF, sanitization, rate limit, activity log.
 */

/* ---------- CSRF ---------- */

function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token. If mismatch, generate a fresh token,
 * store a friendly message, and redirect back to the form.
 * This avoids the "dead-end" 419 page.
 */
function verify_csrf(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $sent   = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    // Happy path: both exist and match
    if ($sent !== '' && $stored !== '' && hash_equals($stored, $sent)) {
        return;
    }

    // Mismatch: regenerate token + flash message, then bounce back
    unset($_SESSION['csrf_token']);
    csrf_token(); // regenerate for next attempt
    $_SESSION['csrf_error'] = 'Your session expired. Please try again.';

    $back = $_SERVER['HTTP_REFERER'] ?? '/';
    header('Location: ' . $back);
    exit;
}

/* ---------- Input cleaning ---------- */

function clean(string $value): string {
    return trim(str_replace("\0", '', $value));
}

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* ---------- Rate limit ---------- */

function rate_limit(string $key, int $max = 5, int $seconds = 60): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $now = time();
    $bucket = $_SESSION['_rl'][$key] ?? [];
    $bucket = array_filter($bucket, fn($t) => $t > $now - $seconds);
    if (count($bucket) >= $max) return false;
    $bucket[] = $now;
    $_SESSION['_rl'][$key] = $bucket;
    return true;
}

/* ---------- Activity log ---------- */

function log_activity(?int $userId, string $action, string $details = ''): void {
    try {
        db_run(
            "INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
             VALUES (?, ?, ?, ?, NOW())",
            [$userId, $action, $details, client_ip()]
        );
    } catch (Exception $e) {
        // Never break page for log failures
    }
}