<?php
/**
 * Product catalog helpers.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/* ---------- Categories ---------- */

function product_categories(): array {
    return [
        'water_storage' => 'Water Storage & Supply',
        'pipes'         => 'Pipes & Plumbing',
        'fixtures'      => 'Bathroom & Kitchen Fixtures',
        'filtration'    => 'Water Filtration & Purification',
        'drainage'      => 'Drainage & Sewer',
        'roofing'       => 'Roofing & Rainwater',
        'heaters'       => 'Water Heaters',
        'backflow'      => 'Backflow & Water Protection',
        'gas_safety'    => 'Gas & Safety',
        'whole_home'    => 'Whole-Home Systems',
        'sump_pump'     => 'Sump Pump & Flood Protection',
    ];
}

function product_category_label(string $key): string {
    $cats = product_categories();
    return $cats[$key] ?? 'Other';
}

/* ---------- Queries ---------- */

function get_all_products(?string $category = null): array {
    if ($category && isset(product_categories()[$category])) {
        return db_run(
            "SELECT * FROM products WHERE is_active = 1 AND category = ? ORDER BY created_at DESC",
            [$category]
        )->fetchAll();
    }
    return db_run("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC")->fetchAll();
}

function get_product(int $id): ?array {
    $row = db_run("SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1", [$id])->fetch();
    return $row ?: null;
}

function get_product_by_slug(string $slug): ?array {
    $row = db_run("SELECT * FROM products WHERE slug = ? AND is_active = 1 LIMIT 1", [$slug])->fetch();
    return $row ?: null;
}

/* ---------- Price helpers ---------- */

function product_effective_price(array $product): float {
    if (!empty($product['sale_price']) && (float)$product['sale_price'] > 0) {
        return (float)$product['sale_price'];
    }
    return (float)$product['price'];
}

function product_has_sale(array $product): bool {
    return !empty($product['sale_price']) && (float)$product['sale_price'] > 0;
}