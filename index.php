<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart_functions.php';
require_once __DIR__ . '/includes/product_functions.php';

$services = array_slice(get_services(), 0, 6);
$stats = get_stats();

/* ---- Featured products (4 newest active) ---- */
$featuredProducts = [];
try {
    $featuredProducts = db_run("SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC LIMIT 4")->fetchAll();
} catch (Exception $e) { $featuredProducts = []; }

/* ---- Featured packages (3 from DB) ---- */
$featuredPackages = [];
try {
    $featuredPackages = db_run("SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3")->fetchAll();
} catch (Exception $e) { $featuredPackages = []; }

/* ---- Real reviews for homepage testimonials ---- */
$testimonials = [];
try {
    $realReviews = db_run(
        "SELECT r.rating, r.comment, r.created_at,
                u.name AS user_name,
                s.title AS service_title
         FROM reviews r
         INNER JOIN users    u ON u.id = r.user_id
         INNER JOIN bookings b ON b.id = r.booking_id
         LEFT  JOIN services s ON s.id = b.service_id
         ORDER BY r.created_at DESC
         LIMIT 3"
    )->fetchAll();

    foreach ($realReviews as $r) {
        $testimonials[] = [
            'name'     => $r['user_name'],
            'location' => 'Verified Customer',
            'rating'   => (int)$r['rating'],
            'text'     => $r['comment'],
        ];
    }
} catch (Exception $e) {}

if (!$testimonials) {
    $testimonials = array_slice(get_testimonials(), 0, 3);
}

$pageTitle  = SITE_NAME . ' — Premium Plumbing Solutions';
$activePage = 'home';
include __DIR__ . '/includes/header.php';
?>

<!-- Emergency Banner -->
<div class="emergency-banner">
  <span class="emergency-pulse"></span>
  Plumbing Emergency? We're available 24/7 — <a href="tel:<?= e(SITE_PHONE) ?>">Call <?= e(SITE_PHONE) ?></a>
</div>

<!-- ============ HERO WITH WELCOME + SLIDESHOW ============ -->
<section class="hero" id="heroSlider">
  <div class="hero__slides" id="heroSlides">
    <div class="hero__slide active" data-slide="0" style="background-image: url('https://images.pexels.com/photos/8961065/pexels-photo-8961065.jpeg?auto=compress&cs=tinysrgb&w=1920');"></div>
    <div class="hero__slide" data-slide="1" style="background-image: url('https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=1920&q=80');"></div>
    <div class="hero__slide" data-slide="2" style="background-image: url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1920&q=80');"></div>
    <div class="hero__slide" data-slide="3" style="background-image: url('https://images.unsplash.com/photo-1556909212-d5b604d0c90d?auto=format&fit=crop&w=1920&q=80');"></div>
    <div class="hero__slide" data-slide="4" style="background-image: url('https://images.unsplash.com/photo-1620626011761-996317b8d101?auto=format&fit=crop&w=1920&q=80');"></div>
    <div class="hero__slide" data-slide="5" style="background-image: url('https://images.unsplash.com/photo-1581094271901-8022df4466f9?auto=format&fit=crop&w=1920&q=80');"></div>
    <div class="hero__slide" data-slide="6" style="background-image: url('https://images.unsplash.com/photo-1607472586893-edb57bdc0e39?auto=format&fit=crop&w=1920&q=80');"></div>
  </div>

  <div class="container">
    <div class="hero__content">
      <span class="hero__tag">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
        Licensed &amp; Insured Plumbing Experts
      </span>

      <h1>Welcome to <span style="color:var(--c-accent-light)">AquaFix Pro</span></h1>

      <p style="font-size:1.15rem;max-width:580px;">
        Springfield's trusted plumbing experts since 2009.<br>
        15+ years of service, 12,000+ happy customers, and 24/7 emergency response you can count on.
      </p>

      <div class="hero__cta">
        <a href="about.php" class="btn btn--accent btn--lg">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          Learn More About Us
        </a>
        <a href="booking.php" class="btn btn--outline btn--lg" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.3);color:#fff">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Book a Service
        </a>
      </div>

      <div class="hero__stats">
        <?php foreach ($stats as $s): ?>
          <div class="hero__stat">
            <div class="num"><?= $s['value'] . $s['suffix'] ?></div>
            <div class="lbl"><?= e($s['label']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Slideshow dots -->
  <div class="hero__dots">
    <button type="button" class="hero__dot active" data-slide="0" aria-label="Slide 1"></button>
    <button type="button" class="hero__dot" data-slide="1" aria-label="Slide 2"></button>
    <button type="button" class="hero__dot" data-slide="2" aria-label="Slide 3"></button>
    <button type="button" class="hero__dot" data-slide="3" aria-label="Slide 4"></button>
    <button type="button" class="hero__dot" data-slide="4" aria-label="Slide 5"></button>
    <button type="button" class="hero__dot" data-slide="5" aria-label="Slide 6"></button>
    <button type="button" class="hero__dot" data-slide="6" aria-label="Slide 7"></button>
  </div>
</section>

<!-- Why Choose Us -->
<section class="section">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">Why AquaFix</span>
      <h2>The Plumbing Professionals <span class="gradient-text">You Deserve</span></h2>
    </div>
    <div class="grid-3">
      <div class="card card__body reveal">
        <div class="card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg></div>
        <h3>Licensed &amp; Insured</h3>
        <p>Every technician is fully licensed, bonded, and insured. Your property is protected with every service call.</p>
      </div>
      <div class="card card__body reveal">
        <div class="card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg></div>
        <h3>On-Time Guarantee</h3>
        <p>We respect your time. If we're late, you get $25 off your service. That's our promise to you.</p>
      </div>
      <div class="card card__body reveal">
        <div class="card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg></div>
        <h3>Upfront Pricing</h3>
        <p>No surprises. You approve the price before we start. Flat-rate quotes with no hidden fees.</p>
      </div>
    </div>
  </div>
</section>

<!-- Featured Services -->
<section class="section section--alt">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">Our Services</span>
      <h2>Complete Plumbing <span class="gradient-text">Solutions</span></h2>
    </div>
    <div class="services-grid">
      <?php foreach ($services as $svc): ?>
        <div class="card service-card reveal">
          <div class="card__body">
            <div class="card__icon"><?= renderIcon($svc['icon']) ?></div>
            <h3><?= e($svc['title']) ?></h3>
            <p><?= e($svc['short']) ?></p>
            <div class="service-card__price">
              <span class="price"><?= e(format_service_price($svc['price'])) ?></span>
              <a href="booking.php?service=<?= e($svc['slug']) ?>" class="btn btn--ghost btn--sm">Book <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg></a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-6 reveal">
      <a href="services.php" class="btn btn--outline btn--lg">View All Services</a>
    </div>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<?php if ($featuredProducts): ?>
<section class="section">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">Shop</span>
      <h2>Shop Plumbing <span class="gradient-text">Products</span></h2>
      <p style="max-width:600px;margin:16px auto 0;">Quality products delivered to your door — water tanks, faucets, heaters, and more.</p>
    </div>
    <div class="services-grid">
      <?php foreach ($featuredProducts as $p): ?>
        <div class="card service-card reveal">
          <a href="product-view.php?id=<?= (int)$p['id'] ?>" style="text-decoration:none;color:inherit;">
            <div style="height:180px;background:#f1f5f9;overflow:hidden;">
              <img src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>"
                   style="width:100%;height:100%;object-fit:cover;"
                   onerror="this.style.display='none';this.parentNode.innerHTML='<div style=\'display:grid;place-items:center;height:100%;font-size:3rem;\'>🔧</div>'">
            </div>
          </a>
          <div class="card__body">
            <div style="font-size:0.72rem;color:var(--c-accent);font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:6px;">
              <?= e(ucfirst(str_replace('_', ' ', $p['category']))) ?>
            </div>
            <h3 style="font-size:1rem;margin-bottom:8px;"><?= e($p['title']) ?></h3>
            <div class="service-card__price">
              <span class="price"><?= money((float)$p['price']) ?></span>
              <a href="product-view.php?id=<?= (int)$p['id'] ?>" class="btn btn--ghost btn--sm">View <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg></a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-6 reveal">
      <a href="products.php" class="btn btn--outline btn--lg">View All Products</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FEATURED PACKAGES -->
<?php if ($featuredPackages): ?>
<section class="section section--alt">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">Save More</span>
      <h2>Service <span class="gradient-text">Packages &amp; Plans</span></h2>
      <p style="max-width:600px;margin:16px auto 0;">Bundle services and save — one-time deals and annual subscriptions.</p>
    </div>
    <div class="grid-3">
      <?php foreach ($featuredPackages as $pk): ?>
        <?php $features = json_decode($pk['features'] ?? '[]', true) ?: []; ?>
        <div class="card card__body reveal" style="position:relative;display:flex;flex-direction:column;">
          <?php if (!empty($pk['badge'])): ?>
            <div style="position:absolute;top:16px;right:16px;background:linear-gradient(135deg,#1e3a8a,#0ea5e9);color:#fff;padding:5px 12px;border-radius:999px;font-size:0.7rem;font-weight:700;letter-spacing:1px;">
              <?= e($pk['badge']) ?>
            </div>
          <?php endif; ?>
          <h3 style="margin-bottom:6px;"><?= e($pk['title']) ?></h3>
          <p style="color:var(--c-accent);font-weight:600;font-size:0.88rem;margin-bottom:12px;"><?= e($pk['subtitle']) ?></p>
          <div style="margin:8px 0 16px;">
            <span style="font-family:var(--font-display);font-size:1.8rem;font-weight:800;color:var(--c-primary);">
              <?= money((float)$pk['price']) ?>
            </span>
            <?php if (!empty($pk['price_suffix'])): ?>
              <span style="color:var(--c-text-muted);font-size:0.95rem;"><?= e($pk['price_suffix']) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($features): ?>
            <ul style="display:grid;gap:8px;margin-bottom:20px;">
              <?php foreach (array_slice($features, 0, 3) as $f): ?>
                <li style="display:flex;gap:8px;align-items:flex-start;font-size:0.85rem;">
                  <span style="color:var(--c-success);flex-shrink:0;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20,6 9,17 4,12"/></svg></span>
                  <span><?= e($f) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div style="margin-top:auto;">
            <a href="shop.php" class="btn btn--primary btn--block btn--sm">View Package</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-6 reveal">
      <a href="shop.php" class="btn btn--outline btn--lg">View All Packages</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Stats Counter -->
<section class="section">
  <div class="container">
    <div class="stats-grid">
      <?php foreach ($stats as $s): ?>
        <div class="stat-box reveal">
          <div class="num" data-count="<?= $s['value'] ?>" data-suffix="<?= e($s['suffix']) ?>">0</div>
          <div class="lbl"><?= e($s['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Process -->
<section class="section section--alt">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">How It Works</span>
      <h2>Simple 4-Step <span class="gradient-text">Process</span></h2>
    </div>
    <div class="process-grid">
      <div class="process-step reveal"><div class="process-step__num">1</div><h3>Book Online</h3><p>Schedule your service in under 2 minutes through our easy online booking system.</p></div>
      <div class="process-step reveal"><div class="process-step__num">2</div><h3>Get a Quote</h3><p>Our technician arrives on time and provides a transparent, upfront price quote.</p></div>
      <div class="process-step reveal"><div class="process-step__num">3</div><h3>We Fix It</h3><p>With your approval, we get to work using top-quality parts and expert techniques.</p></div>
      <div class="process-step reveal"><div class="process-step__num">4</div><h3>Enjoy Peace</h3><p>Rest easy with our workmanship guarantee and follow-up support.</p></div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="section">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">Reviews</span>
      <h2>What Our <span class="gradient-text">Customers Say</span></h2>
    </div>
    <div class="testimonials-grid">
      <?php foreach ($testimonials as $t): ?>
        <div class="card testimonial-card reveal">
          <div class="testimonial-card__stars"><?= str_repeat('&#9733;', (int)$t['rating']) ?></div>
          <p class="testimonial-card__text">"<?= e($t['text']) ?>"</p>
          <div class="testimonial-card__author">
            <div class="testimonial-card__avatar"><?= e(strtoupper(substr($t['name'], 0, 1))) ?></div>
            <div>
              <div class="testimonial-card__name"><?= e($t['name']) ?></div>
              <div class="testimonial-card__loc"><?= e($t['location']) ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CONTACT TEASER -->
<section class="section section--alt">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">Contact Us</span>
      <h2>Get In <span class="gradient-text">Touch</span></h2>
      <p style="max-width:600px;margin:16px auto 0;">Have a question or need a quote? We're here to help.</p>
    </div>
    <div class="grid-3">
      <div class="card card__body text-center reveal">
        <div class="card__icon" style="margin:0 auto 18px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </div>
        <h3>Call Us</h3>
        <p>24/7 emergency line</p>
        <a href="tel:<?= e(SITE_PHONE) ?>" class="btn btn--outline btn--sm"><?= e(SITE_PHONE) ?></a>
      </div>
      <div class="card card__body text-center reveal">
        <div class="card__icon" style="margin:0 auto 18px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <h3>Email Us</h3>
        <p>We reply within 2 hours</p>
        <a href="mailto:<?= e(SITE_EMAIL) ?>" class="btn btn--outline btn--sm"><?= e(SITE_EMAIL) ?></a>
      </div>
      <div class="card card__body text-center reveal">
        <div class="card__icon" style="margin:0 auto 18px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
        <h3>Visit Us</h3>
        <p><?= e(SITE_ADDRESS) ?></p>
        <a href="contact.php" class="btn btn--outline btn--sm">Get Directions</a>
      </div>
    </div>
    <div class="text-center mt-6 reveal">
      <a href="contact.php" class="btn btn--primary btn--lg">Send Us a Message</a>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta-section reveal">
      <h2>Have a Plumbing Problem?</h2>
      <p>Don't let leaks, clogs, or broken pipes ruin your day. Our expert team is standing by.</p>
      <div class="flex gap-2 justify-center flex-wrap">
        <a href="booking.php" class="btn btn--primary btn--lg">Book a Service</a>
        <a href="tel:<?= e(SITE_PHONE) ?>" class="btn btn--outline btn--lg">Call <?= e(SITE_PHONE) ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ============ SLIDESHOW SCRIPT ============ -->
<script>
(function() {
  function initSlider() {
    var slides = document.querySelectorAll('#heroSlider .hero__slide');
    var dots   = document.querySelectorAll('#heroSlider .hero__dot');

    console.log('[Slider] slides:', slides.length, 'dots:', dots.length);

    if (slides.length === 0) return;

    var current  = 0;
    var interval = null;
    var DELAY    = 4500;

    function showSlide(idx) {
      for (var i = 0; i < slides.length; i++) {
        if (i === idx) {
          slides[i].classList.add('active');
        } else {
          slides[i].classList.remove('active');
        }
      }
      for (var j = 0; j < dots.length; j++) {
        if (j === idx) {
          dots[j].classList.add('active');
        } else {
          dots[j].classList.remove('active');
        }
      }
      current = idx;
    }

    function nextSlide() {
      showSlide((current + 1) % slides.length);
    }

    function startAuto() {
      stopAuto();
      interval = setInterval(nextSlide, DELAY);
    }

    function stopAuto() {
      if (interval) clearInterval(interval);
    }

    /* Dot clicks */
    for (var k = 0; k < dots.length; k++) {
      (function(idx) {
        dots[idx].addEventListener('click', function(e) {
          e.preventDefault();
          showSlide(idx);
          startAuto();
        });
      })(k);
    }

    /* Hover pause */
    var hero = document.getElementById('heroSlider');
    if (hero) {
      hero.addEventListener('mouseenter', stopAuto);
      hero.addEventListener('mouseleave', startAuto);
    }

    /* Initial state */
    showSlide(0);
    startAuto();
    console.log('[Slider] Started — rotating every ' + (DELAY / 1000) + 's');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSlider);
  } else {
    initSlider();
  }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>