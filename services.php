<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
$services = get_services();
$pageTitle = 'Our Services — ' . SITE_NAME;
$activePage = 'services';
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
  <div class="container">
    <h1>Our Plumbing Services</h1>
    <p>Comprehensive plumbing solutions for residential and commercial properties</p>
    <div class="breadcrumb"><a href="index.php">Home</a> <span class="sep">/</span> <span>Services</span></div>
  </div>
</section>

<!-- Filter Bar -->
<section class="section">
  <div class="container">
    <div class="filter-bar reveal">
      <button class="filter-btn active" data-filter="all">All Services</button>
      <button class="filter-btn" data-filter="leak-detection">Leak &amp; Drain</button>
      <button class="filter-btn" data-filter="water-heater">Water Heaters</button>
      <button class="filter-btn" data-filter="pipe-repair">Pipes &amp; Sewer</button>
      <button class="filter-btn" data-filter="fixture-install">Fixtures</button>
      <button class="filter-btn" data-filter="emergency">Emergency</button>
    </div>
    <div class="services-grid">
      <?php foreach ($services as $svc): ?>
        <?php
        /* -------- Get ratings for this service -------- */
        $stats = null;
        try {
            $stats = db_run(
                "SELECT ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(r.id) AS total
                 FROM reviews r
                 INNER JOIN bookings b ON b.id = r.booking_id
                 INNER JOIN services s ON s.id = b.service_id
                 WHERE s.slug = ?",
                [$svc['slug']]
            )->fetch();
        } catch (Exception $e) { $stats = null; }

        $avg   = $stats['avg_rating'] ?? 0;
        $total = (int)($stats['total'] ?? 0);
        ?>
        <div class="card service-card reveal" data-category="<?= e($svc['slug']) ?>">
          <div class="card__body">
            <div class="card__icon"><?= renderIcon($svc['icon']) ?></div>
            <h3><?= e($svc['title']) ?></h3>

            <?php if ($total > 0): ?>
              <div style="margin-bottom:10px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="color:#f59e0b;font-size:1rem;letter-spacing:1px;">
                  <?php
                    $fullStars  = (int)round($avg);
                    echo str_repeat('★', $fullStars);
                    echo str_repeat('☆', 5 - $fullStars);
                  ?>
                </span>
                <span style="font-size:0.85rem;color:var(--c-text-muted);">
                  <?= $avg ?> / 5
                  (<?= $total ?> review<?= $total !== 1 ? 's' : '' ?>)
                </span>
              </div>
            <?php else: ?>
              <div style="margin-bottom:10px;font-size:0.85rem;color:var(--c-text-muted);">
                No reviews yet — be the first!
              </div>
            <?php endif; ?>

            <p><?= e($svc['desc']) ?></p>
            <div class="service-card__price">
              <span class="price"><?= e(format_service_price($svc['price'])) ?></span>
              <a href="booking.php?service=<?= e($svc['slug']) ?>" class="btn btn--accent btn--sm">Book Now</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Why Choose Us -->
<section class="section section--alt">
  <div class="container">
    <div class="grid-2">
      <div class="reveal">
        <span class="section-tag">Our Guarantee</span>
        <h2>The AquaFix <span class="gradient-text">Difference</span></h2>
        <p style="margin: 16px 0 24px; font-size: 1.05rem;">We don't just fix pipes — we deliver peace of mind. Every job comes with our satisfaction guarantee and workmanship warranty.</p>
        <ul style="display:grid;gap:16px;">
          <li style="display:flex;gap:12px;align-items:flex-start;">
            <span style="color:var(--c-success);flex-shrink:0"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"/></svg></span>
            <span><strong>100% Satisfaction Guarantee</strong> — If you're not happy, we'll make it right.</span>
          </li>
          <li style="display:flex;gap:12px;align-items:flex-start;">
            <span style="color:var(--c-success);flex-shrink:0"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"/></svg></span>
            <span><strong>Upfront Flat-Rate Pricing</strong> — Know the cost before we begin. No surprises.</span>
          </li>
          <li style="display:flex;gap:12px;align-items:flex-start;">
            <span style="color:var(--c-success);flex-shrink:0"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"/></svg></span>
            <span><strong>Licensed Master Plumbers</strong> — Every technician is background-checked and certified.</span>
          </li>
          <li style="display:flex;gap:12px;align-items:flex-start;">
            <span style="color:var(--c-success);flex-shrink:0"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"/></svg></span>
            <span><strong>2-Year Workmanship Warranty</strong> — We stand behind every repair and installation.</span>
          </li>
        </ul>
        <a href="booking.php" class="btn btn--primary btn--lg mt-4">Book a Service</a>
      </div>
      <div class="reveal">
        <img src="images/5.jpg" alt="Professional plumber at work" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta-section reveal">
      <h2>Ready to Get Started?</h2>
      <p>Book your service online in under 2 minutes, or call us for immediate assistance.</p>
      <div class="flex gap-2 justify-center flex-wrap">
        <a href="booking.php" class="btn btn--primary btn--lg">Book Online</a>
        <a href="tel:<?= e(SITE_PHONE) ?>" class="btn btn--outline btn--lg">Call <?= e(SITE_PHONE) ?></a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>