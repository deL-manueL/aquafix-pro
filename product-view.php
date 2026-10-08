<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/product_functions.php';
require_once __DIR__ . '/includes/cart_functions.php';

$id = (int)($_GET['id'] ?? 0);
$product = get_product($id);
if (!$product) { redirect('products.php'); }

$pageTitle  = $product['title'] . ' — ' . SITE_NAME;
$activePage = 'products';
include __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:40px;">
  <div class="container">

    <div class="breadcrumb" style="color:var(--c-text-muted);margin-bottom:24px;">
      <a href="index.php" style="color:var(--c-text-muted);">Home</a>
      <span class="sep">/</span>
      <a href="products.php" style="color:var(--c-text-muted);">Products</a>
      <span class="sep">/</span>
      <span><?= e($product['title']) ?></span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;" class="product-view-grid">
      <!-- Image -->
      <div>
        <div class="card" style="overflow:hidden;">
          <img src="<?= e($product['image']) ?>" alt="<?= e($product['title']) ?>"
               style="width:100%;height:auto;display:block;"
               onerror="this.parentNode.innerHTML='<div style=\'height:400px;display:grid;place-items:center;font-size:5rem;\'>🔧</div>'">
        </div>
      </div>

      <!-- Info -->
      <div>
        <div style="font-size:0.8rem;color:var(--c-accent);font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">
          <?= e(product_category_label($product['category'])) ?>
        </div>
        <h1 style="font-size:2rem;margin-bottom:16px;"><?= e($product['title']) ?></h1>

        <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:24px;">
          <?php if (product_has_sale($product)): ?>
            <span style="font-family:var(--font-display);font-size:2.5rem;font-weight:800;color:var(--c-primary);">
              <?= money(product_effective_price($product)) ?>
            </span>
            <span style="font-size:1.1rem;text-decoration:line-through;color:var(--c-text-muted);">
              <?= money((float)$product['price']) ?>
            </span>
            <span class="badge badge--done">SALE</span>
          <?php else: ?>
            <span style="font-family:var(--font-display);font-size:2.5rem;font-weight:800;color:var(--c-primary);">
              <?= money((float)$product['price']) ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($product['badge'])): ?>
          <div class="badge badge--done" style="margin-bottom:16px;"><?= e($product['badge']) ?></div>
        <?php endif; ?>

        <p style="font-size:1.05rem;line-height:1.7;margin-bottom:24px;"><?= e($product['description']) ?></p>

        <div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
          <span class="badge badge--pending">📦 Stock: <?= (int)$product['stock'] ?></span>
          <span class="badge badge--done">✓ In Stock</span>
          <span class="badge badge--done">🚚 Free delivery over $100</span>
        </div>

        <form id="addToCartForm" style="display:flex;gap:12px;align-items:center;margin-bottom:24px;">
          <input type="number" id="qty" value="1" min="1" max="99"
                 style="width:90px;padding:12px;border:1px solid var(--c-border);border-radius:8px;font-size:1rem;">
          <button type="submit" class="btn btn--primary btn--lg" style="flex:1;">🛒 Add to Cart</button>
        </form>

        <div style="font-size:0.9rem;color:var(--c-text-muted);line-height:1.8;">
          <div>✓ 30-day return policy</div>
          <div>✓ Genuine products only</div>
          <div>✓ Delivery in 2-5 business days</div>
        </div>
      </div>
    </div>

  </div>
</section>

<style>
@media (max-width:800px) { .product-view-grid { grid-template-columns:1fr !important; gap:24px !important; } }
</style>

<div id="cart-toast" style="position:fixed;bottom:24px;right:24px;background:#10b981;color:#fff;padding:14px 20px;border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,0.15);font-weight:600;opacity:0;transition:.25s;transform:translateY(20px);z-index:999;"></div>

<script>
document.getElementById('addToCartForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = <?= (int)$product['id'] ?>;
  const qty = document.getElementById('qty').value;

  const btn = e.target.querySelector('button');
  btn.disabled = true; btn.textContent = 'Adding...';

  try {
    const res = await fetch('api/cart_add_product.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'product_id=' + id + '&qty=' + qty
    });
    const data = await res.json();
    if (data.ok) {
      showToast('✅ Added to cart (' + data.count + ' items total)');
      updateCartBadge(data.count);   /* ⚡ NEW — instant badge update */
      btn.textContent = '✓ Added!';
      setTimeout(() => { btn.disabled = false; btn.textContent = '🛒 Add to Cart'; }, 1500);
    } else {
      showToast('❌ ' + (data.error || 'Error'));
      btn.disabled = false; btn.textContent = '🛒 Add to Cart';
    }
  } catch (err) {
    showToast('❌ Network error');
    btn.disabled = false; btn.textContent = '🛒 Add to Cart';
  }
});

function updateCartBadge(newCount) {
  const badge = document.getElementById('cartBadge');
  if (!badge) return;
  badge.textContent = newCount;
  badge.style.display = newCount > 0 ? 'inline-block' : 'none';
  badge.style.transition = 'transform 0.2s';
  badge.style.transform = 'scale(1.4)';
  setTimeout(() => { badge.style.transform = 'scale(1)'; }, 200);
}

function showToast(msg) {
  const t = document.getElementById('cart-toast');
  t.textContent = msg;
  t.style.opacity = 1; t.style.transform = 'translateY(0)';
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => {
    t.style.opacity = 0; t.style.transform = 'translateY(20px)';
  }, 2400);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>