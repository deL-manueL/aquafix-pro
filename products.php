<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/product_functions.php';
require_once __DIR__ . '/includes/cart_functions.php';

$category = $_GET['cat'] ?? null;
$products = get_all_products($category);
$categories = product_categories();

$pageTitle  = 'Products — ' . SITE_NAME;
$activePage = 'products';
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Our Products</h1>
    <p>Quality plumbing &amp; water products delivered to your door</p>
    <div class="breadcrumb"><a href="index.php">Home</a> <span class="sep">/</span> <span>Products</span></div>
  </div>
</section>

<section class="section">
  <div class="container">

    <!-- Category filter -->
    <div class="filter-bar reveal" style="margin-bottom:32px;">
      <a href="products.php" class="filter-btn <?= !$category ? 'active' : '' ?>">All Products</a>
      <?php foreach ($categories as $key => $label): ?>
        <a href="products.php?cat=<?= urlencode($key) ?>" class="filter-btn <?= $category === $key ? 'active' : '' ?>">
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (!$products): ?>
      <div class="alert alert--info">No products in this category yet.</div>
    <?php else: ?>
      <div class="services-grid">
        <?php foreach ($products as $p): ?>
          <div class="card service-card reveal" style="position:relative;display:flex;flex-direction:column;">

            <?php if (!empty($p['badge'])): ?>
              <div style="position:absolute;top:16px;right:16px;background:linear-gradient(135deg,#1e3a8a,#0ea5e9);color:#fff;padding:5px 12px;border-radius:999px;font-size:0.7rem;font-weight:700;letter-spacing:1px;z-index:2;">
                <?= e($p['badge']) ?>
              </div>
            <?php endif; ?>

            <a href="product-view.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none;color:inherit;">
              <div style="height:220px;background:#f1f5f9;overflow:hidden;border-radius:14px 14px 0 0;">
                <img src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>"
                     style="width:100%;height:100%;object-fit:cover;"
                     onerror="this.style.display='none';this.parentNode.innerHTML='<div style=\'display:grid;place-items:center;height:100%;font-size:3rem;\'>🔧</div>'">
              </div>
            </a>

            <div class="card__body" style="display:flex;flex-direction:column;height:100%;">
              <div style="font-size:0.75rem;color:var(--c-accent);font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:6px;">
                <?= e(product_category_label($p['category'])) ?>
              </div>
              <h3 style="font-size:1.05rem;margin-bottom:6px;"><?= e($p['title']) ?></h3>
              <p style="font-size:0.85rem;margin-bottom:14px;"><?= e($p['short_desc']) ?></p>

              <div style="margin-top:auto;">
                <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:12px;">
                  <?php if (product_has_sale($p)): ?>
                    <span style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--c-primary);">
                      <?= money(product_effective_price($p)) ?>
                    </span>
                    <span style="font-size:0.9rem;text-decoration:line-through;color:var(--c-text-muted);">
                      <?= money((float)$p['price']) ?>
                    </span>
                  <?php else: ?>
                    <span style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--c-primary);">
                      <?= money((float)$p['price']) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;">
                  <a href="product-view.php?id=<?= (int)$p['id'] ?>" class="btn btn--outline btn--sm" style="flex:1;">View</a>
                  <button type="button" class="btn btn--primary btn--sm js-add-product" data-product-id="<?= (int)$p['id'] ?>" style="flex:1;">
                    🛒 Add
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<div id="cart-toast" style="position:fixed;bottom:24px;right:24px;background:#10b981;color:#fff;padding:14px 20px;border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,0.15);font-weight:600;opacity:0;transition:opacity .25s, transform .25s;transform:translateY(20px);z-index:999;"></div>

<script>
document.querySelectorAll('.js-add-product').forEach(btn => {
  btn.addEventListener('click', async function(e) {
    e.preventDefault();
    const id = this.dataset.productId;
    const original = this.innerHTML;
    this.disabled = true; this.innerHTML = '...';

    try {
      const res = await fetch('api/cart_add_product.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(id) + '&qty=1'
      });
      const data = await res.json();
      if (data.ok) {
        showToast('✅ ' + data.title + ' added');
        updateCartBadge(data.count);   /* ⚡ NEW — instant badge update */
        this.innerHTML = '✓ Added';
        setTimeout(() => { this.innerHTML = original; this.disabled = false; }, 1200);
      } else {
        showToast('❌ ' + (data.error || 'Could not add'));
        this.innerHTML = original; this.disabled = false;
      }
    } catch (err) {
      showToast('❌ Network error');
      this.innerHTML = original; this.disabled = false;
    }
  });
});

function updateCartBadge(newCount) {
  const badge = document.getElementById('cartBadge');
  if (!badge) return;
  badge.textContent = newCount;
  badge.style.display = newCount > 0 ? 'inline-block' : 'none';
  /* pop animation */
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