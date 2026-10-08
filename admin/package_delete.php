<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) redirect('login.php');

$id   = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!$csrf || !hash_equals(csrf_token(), $csrf)) {
    $_SESSION['pkg_flash'] = ['type' => 'error', 'msg' => 'Security check failed.'];
    redirect('packages.php');
}

if ($id <= 0) {
    $_SESSION['pkg_flash'] = ['type' => 'error', 'msg' => 'Invalid package.'];
    redirect('packages.php');
}

$pkg = db_run("SELECT * FROM packages WHERE id = ? LIMIT 1", [$id])->fetch();
if (!$pkg) {
    $_SESSION['pkg_flash'] = ['type' => 'error', 'msg' => 'Package not found.'];
    redirect('packages.php');
}

$usedInOrders = (int) db_run("SELECT COUNT(*) c FROM order_items WHERE package_id = ?", [$id])->fetch()['c'];

if ($usedInOrders > 0) {
    db_run("UPDATE packages SET is_active = 0 WHERE id = ?", [$id]);
    $_SESSION['pkg_flash'] = [
        'type' => 'info',
        'msg'  => "\"{$pkg['title']}\" is in $usedInOrders order(s). It was hidden instead of deleted."
    ];
} else {
    db_run("DELETE FROM packages WHERE id = ?", [$id]);
    $_SESSION['pkg_flash'] = ['type' => 'success', 'msg' => "\"{$pkg['title']}\" deleted."];
}

log_activity((int)($_SESSION['admin_id'] ?? 0), 'package_delete', "Removed package #$id");
redirect('packages.php');