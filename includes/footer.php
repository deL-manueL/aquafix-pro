<footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo">
            <span class="logo__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
            </span>
            AquaFix<span style="color:var(--c-accent)">Pro</span>
          </div>
          <p>Premium plumbing solutions for residential and commercial properties. Licensed, insured, and available 24/7 for emergencies.</p>
          <div class="footer-social">
            <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
            <a href="https://twitter.com/" target="_blank" rel="noopener noreferrer" aria-label="Twitter / X">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
            </a>
            <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            </a>
          </div>
        </div>
        <div>
          <h4>Services</h4>
          <ul class="footer-links">
            <li><a href="<?= $baseHref ?>services.php">Leak Detection</a></li>
            <li><a href="<?= $baseHref ?>services.php">Drain Cleaning</a></li>
            <li><a href="<?= $baseHref ?>services.php">Water Heater</a></li>
            <li><a href="<?= $baseHref ?>services.php">Emergency Service</a></li>
            <li><a href="<?= $baseHref ?>services.php">Sewer Line Service</a></li>
          </ul>
        </div>
        <div>
          <h4>Company</h4>
          <ul class="footer-links">
            <li><a href="<?= $baseHref ?>about.php">About Us</a></li>
            <li><a href="<?= $baseHref ?>products.php">Products</a></li>
            <li><a href="<?= $baseHref ?>shop.php">Packages &amp; Plans</a></li>
            <li><a href="<?= $baseHref ?>booking.php">Book Appointment</a></li>
            <li><a href="<?= $baseHref ?>contact.php">Contact</a></li>
            <li><a href="<?= $baseHref ?>admin/login.php">Admin Login</a></li>
          </ul>
        </div>
        <div>
          <h4>Contact</h4>
          <ul class="footer-links footer-contact">
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?= e(SITE_ADDRESS) ?>
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <a href="tel:<?= e(SITE_PHONE) ?>"><?= e(SITE_PHONE) ?></a>
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved. Licensed &amp; Insured • IL #PL-45821
      </div>
    </div>
  </footer>

  <div class="floating-btns">
    <a href="https://wa.me/<?= e(SITE_WHATSAPP) ?>" class="floating-btn floating-btn--wa" target="_blank" title="Chat on WhatsApp">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.32A7.85 7.85 0 0 0 12.05 4 7.94 7.94 0 0 0 4.1 11.93a7.86 7.86 0 0 0 1.07 3.97L4 20l4.2-1.1a7.9 7.9 0 0 0 3.85 1h.01a7.94 7.94 0 0 0 7.94-7.93 7.9 7.9 0 0 0-2.4-5.65zM12.05 18.5h-.01a6.6 6.6 0 0 1-3.36-.92l-.24-.14-2.49.65.67-2.43-.16-.25a6.56 6.56 0 0 1-1.01-3.5 6.57 6.57 0 0 1 6.57-6.56c1.76 0 3.41.69 4.65 1.93a6.53 6.53 0 0 1 1.93 4.64 6.57 6.57 0 0 1-6.58 6.58z"/></svg>
    </a>
    <button class="floating-btn floating-btn--top" id="backTop" title="Back to top">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5,12 12,5 19,12"/></svg>
    </button>
  </div>

  <div class="cookie-bar" id="cookieBar">
    <div class="container">
      <p>We use cookies to improve your experience. By continuing to browse, you agree to our use of cookies.</p>
      <button class="btn btn--primary btn--sm" id="cookieAccept">Got it</button>
    </div>
  </div>

  <script src="<?= $baseHref ?>assets/js/main.js"></script>

  <?php
  /* ---- Live Chat Widget (loaded on all pages except /admin/) ---- */
  $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
  if (strpos($currentScript, '/admin/') === false) {
      include __DIR__ . '/chat_widget.php';
  }
  ?>
</body>
</html>