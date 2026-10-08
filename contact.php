<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
start_session();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '')  $errors[] = 'Name is required';
    if ($email === '') $errors[] = 'Email is required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';
    if ($message === '') $errors[] = 'Message is required';

    if (!$errors) {
        try {
            save_message([
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'subject' => $subject, 'message' => $message,
            ]);
            $success = true;
        } catch (Exception $ex) {
            $errors[] = 'Something went wrong. Please try again or call us directly.';
        }
    }
}

$pageTitle = 'Contact Us — ' . SITE_NAME;
$activePage = 'contact';
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
  <div class="container">
    <h1>Get In Touch</h1>
    <p>We're here to help with all your plumbing needs — 24 hours a day, 7 days a week</p>
    <div class="breadcrumb"><a href="index.php">Home</a> <span class="sep">/</span> <span>Contact</span></div>
  </div>
</section>

<!-- Contact Section -->
<section class="section">
  <div class="container">
    <div class="grid-2">
      <!-- Contact Info -->
      <div class="reveal">
        <span class="section-tag">Contact</span>
        <h2>Let's Talk <span class="gradient-text">Plumbing</span></h2>
        <p style="margin: 16px 0 32px; font-size: 1.05rem;">Whether you have a quick question or a full-scale plumbing emergency, our team is ready to help. Reach out any way you prefer.</p>

        <div style="display:grid;gap:20px;">
          <div style="display:flex;gap:16px;align-items:flex-start;">
            <div class="card__icon" style="flex-shrink:0;width:48px;height:48px;border-radius:12px;margin:0;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
              <h3 style="margin-bottom:4px;">Phone</h3>
              <p><a href="tel:<?= e(SITE_PHONE) ?>"><?= e(SITE_PHONE) ?></a></p>
              <p style="font-size:0.85rem;">24/7 Emergency Line</p>
            </div>
          </div>

          <div style="display:flex;gap:16px;align-items:flex-start;">
            <div class="card__icon" style="flex-shrink:0;width:48px;height:48px;border-radius:12px;margin:0;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
              <h3 style="margin-bottom:4px;">Email</h3>
              <p><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></p>
              <p style="font-size:0.85rem;">We reply within 2 hours</p>
            </div>
          </div>

          <div style="display:flex;gap:16px;align-items:flex-start;">
            <div class="card__icon" style="flex-shrink:0;width:48px;height:48px;border-radius:12px;margin:0;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <h3 style="margin-bottom:4px;">Address</h3>
              <p><?= e(SITE_ADDRESS) ?></p>
              <p style="font-size:0.85rem;"><?= e(SITE_HOURS) ?></p>
            </div>
          </div>
        </div>

        <a href="https://wa.me/<?= e(SITE_WHATSAPP) ?>" target="_blank" class="btn btn--accent btn--lg mt-4">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.32A7.85 7.85 0 0 0 12.05 4 7.94 7.94 0 0 0 4.1 11.93a7.86 7.86 0 0 0 1.07 3.97L4 20l4.2-1.1a7.9 7.9 0 0 0 3.85 1h.01a7.94 7.94 0 0 0 7.94-7.93 7.9 7.9 0 0 0-2.4-5.65z"/></svg>
          Chat on WhatsApp
        </a>
      </div>

      <!-- Contact Form -->
      <div class="card card__body reveal">
        <h3>Send Us a Message</h3>
        <p style="margin-bottom:24px;">Fill out the form below and we'll get back to you shortly.</p>

        <?php if ($success): ?>
          <div class="alert alert--success">
            <strong>Thank you!</strong> Your message has been sent. We'll contact you soon.
          </div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="alert alert--error">
            <?php foreach ($errors as $err): ?>
              <div><?= e($err) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="" data-validate>
          <div class="form-grid">
            <div class="form-group">
              <label>Name <span class="req">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="John Smith" required value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" name="phone" class="form-control" placeholder="(555) 000-0000" value="<?= e($_POST['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Email <span class="req">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="john@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject" class="form-control" placeholder="How can we help?" value="<?= e($_POST['subject'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Message <span class="req">*</span></label>
            <textarea name="message" class="form-control" placeholder="Describe your plumbing issue..." required><?= e($_POST['message'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn--primary btn--block btn--lg">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9"/></svg>
            Send Message
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
