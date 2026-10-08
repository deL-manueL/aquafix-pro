<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart_functions.php';

$packages = db_run("SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();

$pageTitle = 'Shop — ' . SITE_NAME;
$activePage = 'shop';
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>AquaFix Shop</h1>
    <p>Buy service packages, plans, and gift cards online</p>
    <div class="breadcrumb"><a href="index.php">Home</a> <span class="sep">/</span> <span>Shop</span></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (!$packages): ?>
      <div class="alert alert--info">No packages available yet. Check back soon!</div>
    <?php else: ?>
      <div class="services-grid">
        <?php foreach ($packages as $p): ?>
          <?php $features = json_decode($p['features'] ?? '[]', true) ?: []; ?>
          <div class="card service-card reveal" style="position:relative;display:flex;flex-direction:column;">
            <?php if (!empty($p['badge'])): ?>
              <div style="position:absolute;top:16px;right:16px;background:linear-gradient(135deg,#1e3a8a,#0ea5e9);color:#fff;padding:5px 12px;border-radius:999px;font-size:0.72rem;font-weight:700;letter-spacing:1px;">
                <?= e($p['badge']) ?>
              </div>
            <?php endif; ?>
            <div class="card__body" style="display:flex;flex-direction:column;height:100%;">
              <div class="card__icon"><?= renderIcon($p['icon'] ?: 'droplet') ?></div>
              <h3><?= e($p['title']) ?></h3>
              <p style="color:var(--c-accent);font-weight:600;font-size:0.9rem;margin-bottom:8px;"><?= e($p['subtitle']) ?></p>

              <div style="margin:14px 0 18px;">
                <span style="font-family:var(--font-display);font-size:2rem;font-weight:800;color:var(--c-primary);">
                  <?= money((float)$p['price']) ?>
                </span>
                <?php if (!empty($p['price_suffix'])): ?>
                  <span style="color:var(--c-text-muted);font-size:1rem;"><?= e($p['price_suffix']) ?></span>
                <?php endif; ?>
              </div>

              <p style="font-size:0.9rem;margin-bottom:16px;"><?= e($p['description']) ?></p>

              <?php if ($features): ?>
                <ul style="display:grid;gap:10px;margin-bottom:24px;">
                  <?php foreach ($features as $f): ?>
                    <li style="display:flex;gap:10px;align-items:flex-start;font-size:0.88rem;">
                      <span style="color:var(--c-success);flex-shrink:0;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20,6 9,17 4,12"/></svg></span>
                      <span><?= e($f) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              <div style="margin-top:auto;">
                <button type="button" class="btn btn--primary btn--block js-add-to-cart" data-package-id="<?= (int)$p['id'] ?>">
                  🛒 Add to Cart
                </button>
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
document.querySelectorAll('.js-add-to-cart').forEach(btn => {
  btn.addEventListener('click', async function() {
    const id = this.dataset.packageId;
    const original = this.innerHTML;
    this.disabled = true;
    this.innerHTML = 'Adding...';

    try {
      const res = await fetch('api/cart_add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'package_id=' + encodeURIComponent(id) + '&qty=1'
      });
      const data = await res.json();
      if (data.ok) {
        showToast('✅ ' + data.title + ' added to cart');
        updateCartBadge(data.count);   /* ⚡ NEW — instant badge update */
        this.innerHTML = '✓ Added!';
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
  badge.style.transition = 'transform 0.2s';
  badge.style.transform = 'scale(1.4)';
  setTimeout(() => { badge.style.transform = 'scale(1)'; }, 200);
}

function showToast(msg) {
  const t = document.getElementById('cart-toast');
  t.textContent = msg;
  t.style.opacity = 1;
  t.style.transform = 'translateY(0)';
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => {
    t.style.opacity = 0;
    t.style.transform = 'translateY(20px)';
  }, 2400);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>