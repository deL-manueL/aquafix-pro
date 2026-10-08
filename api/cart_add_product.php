<?php
/**
 * AJAX: add a product to cart.
 * POST: product_id, qty
 * Returns total count of products+packages in cart.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/product_functions.php';
require_once __DIR__ . '/../includes/cart_functions.php';

header('Content-Type: application/json');

/* Make sure session is started */
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['qty'] ?? 1));

if ($productId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid product']);
    exit;
}

$product = get_product($productId);
if (!$product) {
    echo json_encode(['ok' => false, 'error' => 'Product not found']);
    exit;
}

/* Init product cart in session */
if (empty($_SESSION['product_cart']) || !is_array($_SESSION['product_cart'])) {
    $_SESSION['product_cart'] = [];
}

$current = $_SESSION['product_cart'][$productId] ?? 0;
$_SESSION['product_cart'][$productId] = $current + $qty;

/* ---- Return TOTAL count across products AND packages ---- */
$prodCount = (int) array_sum($_SESSION['product_cart']);
$pkgCount  = (int) cart_count();   /* from cart_functions.php */
$totalCount = $prodCount + $pkgCount;

echo json_encode([
    'ok'    => true,
    'title' => $product['title'],
    'count' => $totalCount,
]);