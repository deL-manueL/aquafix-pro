<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'About Us — ' . SITE_NAME;
$activePage = 'about';
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
  <div class="container">
    <h1>About AquaFix Pro</h1>
    <p>15 years of trusted plumbing service in Springfield and surrounding areas</p>
    <div class="breadcrumb"><a href="index.php">Home</a> <span class="sep">/</span> <span>About</span></div>
  </div>
</section>

<!-- Story -->
<section class="section">
  <div class="container">
    <div class="grid-2">
      <div class="reveal">
        <img src="images/8.jpg" alt="AquaFix Pro team" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
      </div>
      <div class="reveal">
        <span class="section-tag">Our Story</span>
        <h2>Built on Trust, <span class="gradient-text">Driven by Quality</span></h2>
        <p style="margin: 16px 0;">AquaFix Pro was founded in 2009 by master plumber Robert Mitchell with a simple mission: provide honest, high-quality plumbing service that homeowners can rely on.</p>
        <p style="margin-bottom: 24px;">What started as a one-person operation has grown into a team of 12 certified technicians serving over 5,000 satisfied customers across Springfield and the surrounding communities. Through it all, our commitment to upfront pricing, quality workmanship, and genuine customer care has never wavered.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
          <div><div style="font-size:2rem;font-weight:800;color:var(--c-primary);font-family:var(--font-display);">15+</div><div style="color:var(--c-text-light);font-size:0.9rem;">Years in Business</div></div>
          <div><div style="font-size:2rem;font-weight:800;color:var(--c-primary);font-family:var(--font-display);">12k+</div><div style="color:var(--c-text-light);font-size:0.9rem;">Jobs Completed</div></div>
          <div><div style="font-size:2rem;font-weight:800;color:var(--c-primary);font-family:var(--font-display);">12</div><div style="color:var(--c-text-light);font-size:0.9rem;">Expert Technicians</div></div>
          <div><div style="font-size:2rem;font-weight:800;color:var(--c-primary);font-family:var(--font-display);">A+</div><div style="color:var(--c-text-light);font-size:0.9rem;">BBB Rating</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Values -->
<section class="section section--alt">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">Our Values</span>
      <h2>What We <span class="gradient-text">Stand For</span></h2>
    </div>
    <div class="grid-3">
      <div class="card card__body reveal">
        <div class="card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <h3>Integrity</h3>
        <p>We do what's right, not what's easy. Honest assessments, fair pricing, and no unnecessary repairs — ever.</p>
      </div>
      <div class="card card__body reveal">
        <div class="card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg></div>
        <h3>Excellence</h3>
        <p>We hold ourselves to the highest standards. Every repair, every installation, every interaction — done right.</p>
      </div>
      <div class="card card__body reveal">
        <div class="card__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></div>
        <h3>Customer First</h3>
        <p>Our customers are our neighbors. We treat your home with respect and your time as valuable.</p>
      </div>
    </div>
  </div>
</section>

<!-- Team -->
<section class="section">
  <div class="container">
    <div class="text-center mb-6 reveal">
      <span class="section-tag">Our Team</span>
      <h2>Meet the <span class="gradient-text">Experts</span></h2>
    </div>
    <div class="grid-3">
      <?php
      $team = [
        ['Robert Mitchell', 'Founder & Master Plumber', 'RM', '15+ years experience. Licensed master plumber (IL #PL-45821).'],
        ['Sarah Johnson', 'Operations Manager', 'SJ', 'Keeps the team running smoothly. Your go-to for scheduling and questions.'],
        ['Mike Rodriguez', 'Senior Technician', 'MR', 'Specializes in water heaters and repiping. 10 years with AquaFix.'],
      ];
      foreach ($team as $member):
      ?>
        <div class="card card__body text-center reveal">
          <div style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));color:#fff;display:grid;place-items:center;font-family:var(--font-display);font-size:2rem;font-weight:800;margin:0 auto 18px;"><?= e($member[2]) ?></div>
          <h3><?= e($member[0]) ?></h3>
          <p style="color:var(--c-accent);font-weight:600;font-size:0.88rem;margin-bottom:10px;"><?= e($member[1]) ?></p>
          <p><?= e($member[3]) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta-section reveal">
      <h2>Ready to Work With Us?</h2>
      <p>Experience the AquaFix difference — quality plumbing, honest service, fair prices.</p>
      <div class="flex gap-2 justify-center flex-wrap">
        <a href="booking.php" class="btn btn--primary btn--lg">Book a Service</a>
        <a href="contact.php" class="btn btn--outline btn--lg">Contact Us</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
