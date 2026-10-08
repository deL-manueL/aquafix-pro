<?php
/**
 * Simple session-based cart + money formatter with currency conversion.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/* ---------- Basics ---------- */

function cart_init(): void {
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function cart_add(int $packageId, int $qty = 1): void {
    cart_init();
    if ($packageId <= 0 || $qty <= 0) return;
    $current = $_SESSION['cart'][$packageId] ?? 0;
    $_SESSION['cart'][$packageId] = $current + $qty;
}

function cart_remove(int $packageId): void {
    cart_init();
    unset($_SESSION['cart'][$packageId]);
}

function cart_set_qty(int $packageId, int $qty): void {
    cart_init();
    if ($qty <= 0) { unset($_SESSION['cart'][$packageId]); return; }
    $_SESSION['cart'][$packageId] = $qty;
}

function cart_clear(): void {
    $_SESSION['cart'] = [];
}

function cart_count(): int {
    cart_init();
    return (int) array_sum($_SESSION['cart']);
}

function cart_is_empty(): bool {
    return cart_count() === 0;
}

/* ---------- Full cart with package data from DB ---------- */

function cart_items(): array {
    cart_init();
    if (empty($_SESSION['cart'])) return [];

    $ids = array_map('intval', array_keys($_SESSION['cart']));
    if (!$ids) return [];

    $place = implode(',', array_fill(0, count($ids), '?'));
    $rows  = db_run("SELECT * FROM packages WHERE id IN ($place) AND is_active = 1", $ids)->fetchAll();

    $items = [];
    foreach ($rows as $r) {
        $qty = (int)($_SESSION['cart'][$r['id']] ?? 0);
        if ($qty <= 0) continue;
        $r['qty']  = $qty;
        $r['line'] = (float)$r['price'] * $qty;
        $items[] = $r;
    }
    return $items;
}

function cart_subtotal(): float {
    $sum = 0;
    foreach (cart_items() as $i) $sum += (float)$i['line'];
    return $sum;
}

function cart_tax(): float {
    return round(cart_subtotal() * 0.10, 2);
}

function cart_total(): float {
    return cart_subtotal() + cart_tax();
}

/* ---------- Money formatter (uses currency conversion) ---------- */

function money(float $n): string {
    /* Make sure the currency helper is loaded */
    if (!function_exists('format_price')) {
        require_once __DIR__ . '/../config/currency.php';
    }
    return format_price($n);
}