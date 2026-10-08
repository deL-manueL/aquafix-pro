<?php
/**
 * AJAX endpoint: add a package to cart.
 * POST: package_id, qty (optional, default 1)
 * Returns total count across products + packages.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cart_functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$packageId = (int)($_POST['package_id'] ?? 0);
$qty       = max(1, (int)($_POST['qty'] ?? 1));

if ($packageId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid package']);
    exit;
}

/* Verify the package exists */
$pkg = db_run("SELECT id, title FROM packages WHERE id = ? AND is_active = 1 LIMIT 1", [$packageId])->fetch();
if (!$pkg) {
    echo json_encode(['ok' => false, 'error' => 'Package not found']);
    exit;
}

cart_add($packageId, $qty);

/* ---- Return TOTAL count across packages AND products ---- */
$pkgCount  = (int) cart_count();
$prodCount = !empty($_SESSION['product_cart']) && is_array($_SESSION['product_cart'])
    ? (int) array_sum($_SESSION['product_cart'])
    : 0;
$totalCount = $pkgCount + $prodCount;

echo json_encode([
    'ok'       => true,
    'message'  => 'Added to cart',
    'title'    => $pkg['title'],
    'count'    => $totalCount,
    'subtotal' => money(cart_subtotal()),
]);