<?php
/**
 * Authentication helpers — login, logout, current user, role checks.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

start_session();

/* ---------- Current user ---------- */

function current_user(): ?array {
    static $cache = null;
    if ($cache !== null) return $cache ?: null;

    $id = $_SESSION['user_id'] ?? null;
    if (!$id) { $cache = false; return null; }

    try {
        $user = db_run("SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1", [$id])->fetch();
    } catch (Exception $e) { $user = null; }
    $cache = $user ?: false;
    return $user ?: null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function is_admin(): bool {
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_login(string $redirectTo = '../auth/login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['flash'] = 'Please log in to continue.';
        redirect($redirectTo);
    }
}

function require_role(string $role): void {
    require_login();
    $u = current_user();
    if (!$u || $u['role'] !== $role) {
        http_response_code(403);
        die('Access denied.');
    }
}

/* ---------- Login / logout ---------- */

function attempt_login(string $email, string $password): array {
    $email = strtolower(trim($email));

    $user = db_run("SELECT * FROM users WHERE email = ? LIMIT 1", [$email])->fetch();
    if (!$user)                       return [false, 'Invalid email or password.'];
    if (!$user['is_active'])          return [false, 'Your account has been disabled.'];
    if (!password_verify($password, $user['password_hash'])) {
        log_activity((int)$user['id'], 'login_failed', "Wrong password for $email");
        return [false, 'Invalid email or password.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];

    log_activity((int)$user['id'], 'login_success', "Logged in from " . client_ip());
    return [true, ''];
}

function do_logout(): void {
    $u = current_user();
    if ($u) log_activity((int)$u['id'], 'logout', '');

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- Registration ---------- */

function register_user(string $name, string $email, string $phone, string $password): array {
    $name  = clean($name);
    $email = strtolower(clean($email));
    $phone = clean($phone);

    if ($name === '' || $email === '' || $password === '') {
        return [false, 'Name, email and password are required.', null];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Please enter a valid email.', null];
    }
    if (strlen($password) < 8) {
        return [false, 'Password must be at least 8 characters.', null];
    }

    $exists = db_run("SELECT id FROM users WHERE email = ? LIMIT 1", [$email])->fetch();
    if ($exists) return [false, 'That email is already registered.', null];

    $hash = password_hash($password, PASSWORD_DEFAULT);
    db_run(
        "INSERT INTO users (name, email, phone, password_hash, role, email_verified, created_at)
         VALUES (?, ?, ?, ?, 'customer', 0, NOW())",
        [$name, $email, $phone, $hash]
    );
    $id = (int) db()->lastInsertId();

    log_activity($id, 'register', "New customer: $email");
    return [true, '', $id];
}

/* ---------- Flash messages ---------- */

function set_flash(string $msg, string $type = 'info'): void {
    $_SESSION['flash_msg']  = $msg;
    $_SESSION['flash_type'] = $type;
}

function get_flash(): ?array {
    if (empty($_SESSION['flash_msg'])) return null;
    $msg  = $_SESSION['flash_msg'];
    $type = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    return ['msg' => $msg, 'type' => $type];
}

/* ---------- Notifications helper ---------- */

function unread_notifications(int $userId): int {
    try {
        $row = db_run("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0", [$userId])->fetch();
        return (int)($row['c'] ?? 0);
    } catch (Exception $e) { return 0; }
}

function add_notification(int $userId, string $title, string $body, ?string $link = null): void {
    try {
        db_run("INSERT INTO notifications (user_id, title, body, link, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$userId, $title, $body, $link]);
    } catch (Exception $e) {}
}