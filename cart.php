<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart_functions.php';
require_once __DIR__ . '/includes/product_functions.php';

/* ---- Ensure session is active ---- */
if (session_status() === PHP_SESSION_NONE) session_start();

/* ---- Init product cart session ---- */
if (!isset($_SESSION['product_cart']) || !is_array($_SESSION['product_cart'])) {
    $_SESSION['product_cart'] = [];
}

/* ---- Handle actions ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* Package actions */
    if ($action === 'update' && !empty($_POST['qty'])) {
        foreach ($_POST['qty'] as $pid => $q) cart_set_qty((int)$pid, (int)$q);
    }
    if ($action === 'remove' && !empty($_POST['package_id'])) {
        cart_remove((int)$_POST['package_id']);
    }
    if ($action === 'clear_packages') {
        cart_clear();
    }

    /* Product actions */
    if ($action === 'update_products' && !empty($_POST['prod_qty'])) {
        foreach ($_POST['prod_qty'] as $pid => $q) {
            $q = (int)$q;
            if ($q <= 0) unset($_SESSION['product_cart'][$pid]);
            else         $_SESSION['product_cart'][$pid] = $q;
        }
    }
    if ($action === 'remove_product' && !empty($_POST['product_id'])) {
        unset($_SESSION['product_cart'][(int)$_POST['product_id']]);
    }
    if ($action === 'clear_products') {
        $_SESSION['product_cart'] = [];
    }

    redirect('cart.php');
}

/* ---- Load packages in cart ---- */
$pkgItems = cart_items();
$pkgSub   = 0;
foreach ($pkgItems as $i) $pkgSub += (float)$i['line'];

/* ---- Load products in cart ---- */
$prodItems = [];
$prodSub   = 0;
if (!empty($_SESSION['product_cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['product_cart']));
    $ids = array_filter($ids, fn($i) => $i > 0);
    if ($ids) {
        $place = implode(',', array_fill(0, count($ids), '?'));
        $rows  = db_run("SELECT * FROM products WHERE id IN ($place) AND is_active = 1", array_values($ids))->fetchAll();
        foreach ($rows as $r) {
            $qty = (int)($_SESSION['product_cart'][$r['id']] ?? 0);
            if ($qty <= 0) continue;
            $price = product_effective_price($r);
            $r['qty']   = $qty;
            $r['unit']  = $price;
            $r['line']  = $price * $qty;
            $prodItems[] = $r;
            $prodSub += $r['line'];
        }
    }
}

$subtotal = $pkgSub + $prodSub;
$tax      = round($subtotal * 0.10, 2);
$total    = $subtotal + $tax;

$totalItems = 0;
foreach ($pkgItems as $i)  $totalItems += (int)$i['qty'];
foreach ($prodItems as $i) $totalItems += (int)$i['qty'];

$pageTitle  = 'Your Cart — ' . SITE_NAME;
$activePage = 'shop';
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Your Cart</h1>
    <p><?= $totalItems ?> item<?= $totalItems !== 1 ? 's' : '' ?></p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:1000px;">

    <?php if (!$pkgItems && !$prodItems): ?>
      <div class="card card__body text-center" style="padding:64px;">
        <div style="font-size:3rem;margin-bottom:16px;">🛒</div>
        <h3>Your cart is empty</h3>
        <p style="margin:16px 0 24px;">Browse our products and packages to get started.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a href="products.php" class="btn btn--primary btn--lg">Browse Products</a>
          <a href="shop.php" class="btn btn--outline btn--lg">Browse Packages</a>
        </div>
      </div>
    <?php else: ?>

      <!-- PRODUCTS -->
      <?php if ($prodItems): ?>
        <h3 style="margin-bottom:16px;">📦 Products</h3>
        <form method="POST">
          <div class="card" style="overflow:hidden;margin-bottom:24px;">
            <table class="admin-table" style="width:100%;">
              <thead>
                <tr><th>Product</th><th>Unit Price</th><th>Qty</th><th>Subtotal</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($prodItems as $it): ?>
                  <tr>
                    <td>
                      <div style="display:flex;gap:12px;align-items:center;">
                        <img src="<?= e($it['image']) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;" onerror="this.style.display='none'">
                        <div>
                          <strong><?= e($it['title']) ?></strong>
                          <div style="font-size:0.78rem;color:var(--c-text-muted);"><?= e(product_category_label($it['category'])) ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= money($it['unit']) ?></td>
                    <td>
                      <input type="number" name="prod_qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>"
                             min="1" max="99" style="width:70px;padding:6px 10px;border:1px solid var(--c-border);border-radius:8px;">
                    </td>
                    <td><strong><?= money($it['line']) ?></strong></td>
                    <td>
                      <button type="submit" name="action" value="remove_product" formnovalidate
                              onclick="document.getElementById('rmp_<?= (int)$it['id'] ?>').value=<?= (int)$it['id'] ?>;"
                              class="btn btn--ghost btn--sm" style="color:var(--c-error);">Remove</button>
                      <input type="hidden" id="rmp_<?= (int)$it['id'] ?>" name="product_id" value="">
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <div style="padding:14px 20px;background:var(--c-bg-alt);display:flex;gap:12px;flex-wrap:wrap;">
              <button type="submit" name="action" value="update_products" class="btn btn--outline btn--sm">Update Products</button>
              <button type="submit" name="action" value="clear_products" class="btn btn--ghost btn--sm" onclick="return confirm('Clear all products?');">Clear Products</button>
            </div>
          </div>
        </form>
      <?php endif; ?>

      <!-- PACKAGES -->
      <?php if ($pkgItems): ?>
        <h3 style="margin-bottom:16px;">🎁 Packages</h3>
        <form method="POST">
          <div class="card" style="overflow:hidden;margin-bottom:24px;">
            <table class="admin-table" style="width:100%;">
              <thead>
                <tr><th>Package</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($pkgItems as $it): ?>
                  <tr>
                    <td>
                      <strong><?= e($it['title']) ?></strong>
                      <?php if (!empty($it['subtitle'])): ?>
                        <div style="font-size:0.82rem;color:var(--c-text-muted);"><?= e($it['subtitle']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?= money((float)$it['price']) ?><?= e($it['price_suffix'] ?? '') ?></td>
                    <td>
                      <input type="number" name="qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>"
                             min="1" max="99" style="width:70px;padding:6px 10px;border:1px solid var(--c-border);border-radius:8px;">
                    </td>
                    <td><strong><?= money((float)$it['line']) ?></strong></td>
                    <td>
                      <button type="submit" name="action" value="remove" formnovalidate
                              onclick="document.getElementById('rm_<?= (int)$it['id'] ?>').value=<?= (int)$it['id'] ?>;"
                              class="btn btn--ghost btn--sm" style="color:var(--c-error);">Remove</button>
                      <input type="hidden" id="rm_<?= (int)$it['id'] ?>" name="package_id" value="">
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <div style="padding:14px 20px;background:var(--c-bg-alt);display:flex;gap:12px;flex-wrap:wrap;">
              <button type="submit" name="action" value="update" class="btn btn--outline btn--sm">Update Packages</button>
              <button type="submit" name="action" value="clear_packages" class="btn btn--ghost btn--sm" onclick="return confirm('Clear all packages?');">Clear Packages</button>
            </div>
          </div>
        </form>
      <?php endif; ?>

      <!-- TOTALS -->
      <div class="card card__body" style="max-width:400px;margin-left:auto;">
        <h3 style="margin-bottom:16px;">Order Summary</h3>
        <div style="display:flex;justify-content:space-between;padding:8px 0;">
          <span>Subtotal</span><strong><?= money($subtotal) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;">
          <span>Tax (10%)</span><strong><?= money($tax) ?></strong>
        </div>
        <hr style="border:none;border-top:1px solid var(--c-border);margin:12px 0;">
        <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:1.2rem;">
          <span><strong>Total</strong></span><strong style="color:var(--c-primary);"><?= money($total) ?></strong>
        </div>
        <a href="checkout.php" class="btn btn--accent btn--block btn--lg" style="margin-top:16px;">Proceed to Checkout →</a>
        <a href="products.php" class="btn btn--ghost btn--block btn--sm" style="margin-top:8px;">← Continue Shopping</a>
      </div>

    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>