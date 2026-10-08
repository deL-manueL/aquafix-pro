<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
start_session();

$services = get_services();
$prefill = $_GET['service'] ?? '';
$errors = [];
$success = false;
$bookingData = null;

/* ---- Pull logged-in user for auto-fill ---- */
$loggedUser = null;
if (!empty($_SESSION['user_id'])) {
    try {
        $loggedUser = db_run("SELECT id, name, email, phone FROM users WHERE id = ? LIMIT 1", [$_SESSION['user_id']])->fetch();
    } catch (Exception $e) { $loggedUser = null; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $date    = trim($_POST['date'] ?? '');
    $time    = trim($_POST['time'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '')    $errors[] = 'Name is required';
    if ($email === '')   $errors[] = 'Email is required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';
    if ($phone === '')   $errors[] = 'Phone is required';
    if ($address === '') $errors[] = 'Address is required';
    if ($service === '') $errors[] = 'Please select a service';
    if ($date === '')    $errors[] = 'Preferred date is required';
    if ($time === '')    $errors[] = 'Preferred time is required';

    if (!$errors) {
        try {
            $bookingId = save_booking([
                'user_id' => $loggedUser['id'] ?? null,
                'name'    => $name,
                'email'   => $email,
                'phone'   => $phone,
                'address' => $address,
                'service' => $service,
                'date'    => $date,
                'time'    => $time,
                'message' => $message,
            ]);

            if ($loggedUser) {
                try {
                    db_run(
                        "INSERT INTO notifications (user_id, title, body, link, created_at)
                         VALUES (?, ?, ?, ?, NOW())",
                        [
                            (int)$loggedUser['id'],
                            "Booking #$bookingId received",
                            "We've received your booking for $service on " . date('M j, Y', strtotime($date)) . ". We'll confirm shortly.",
                            "../user/my-bookings.php"
                        ]
                    );
                } catch (Exception $e) {}
            }

            $success = true;
            $bookingData = compact('name', 'email', 'phone', 'address', 'service', 'date', 'time', 'message');
        } catch (Exception $ex) {
            $errors[] = 'Something went wrong. Please try again or call us.';
        }
    }
}

$pageTitle = 'Book a Service — ' . SITE_NAME;
$activePage = 'booking';
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
  <div class="container">
    <h1>Book a Service</h1>
    <p>Schedule your plumbing appointment in just a few simple steps</p>
    <div class="breadcrumb"><a href="index.php">Home</a> <span class="sep">/</span> <span>Booking</span></div>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width: 800px;">

    <?php if ($loggedUser && !$success): ?>
      <div class="alert alert--info" style="background:rgba(16,185,129,.1);color:var(--c-success);border:1px solid rgba(16,185,129,.3);">
        ✅ <strong>Welcome back, <?= e($loggedUser['name']) ?>!</strong>
        Your details are pre-filled.
        <a href="user/my-bookings.php" style="color:var(--c-success);text-decoration:underline;">View my bookings &rarr;</a>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <!-- Success Screen -->
      <div class="card card__body text-center reveal" style="padding: 56px;">
        <div style="width:80px;height:80px;border-radius:50%;background:rgba(16,185,129,.12);display:grid;place-items:center;margin:0 auto 24px;">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--c-success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"/></svg>
        </div>
        <h2>Booking Confirmed!</h2>
        <p style="margin: 16px 0 32px; font-size: 1.1rem;">Thank you, <?= e($bookingData['name']) ?>! Your appointment request has been received. Our team will call you at <?= e($bookingData['phone']) ?> to confirm the details.</p>

        <div style="text-align:left;max-width:400px;margin:0 auto 32px;background:var(--c-bg-alt);border-radius:var(--radius);padding:24px;">
          <div style="display:grid;gap:12px;">
            <div><strong>Service:</strong> <?= e($bookingData['service']) ?></div>
            <div><strong>Date:</strong> <?= e(date('l, F j, Y', strtotime($bookingData['date']))) ?></div>
            <div><strong>Time:</strong> <?= e($bookingData['time']) ?></div>
            <div><strong>Address:</strong> <?= e($bookingData['address']) ?></div>
          </div>
        </div>

        <?php if ($loggedUser): ?>
          <a href="user/my-bookings.php" class="btn btn--primary btn--lg">View My Bookings</a>
        <?php else: ?>
          <a href="index.php" class="btn btn--primary btn--lg">Back to Home</a>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <!-- Booking Steps Indicator -->
      <div class="booking-steps mb-6 reveal">
        <div class="booking-step"><div class="booking-step__circle">1</div><div class="booking-step__label">Your Details</div></div>
        <div class="booking-step"><div class="booking-step__circle">2</div><div class="booking-step__label">Service Info</div></div>
        <div class="booking-step"><div class="booking-step__circle">3</div><div class="booking-step__label">Schedule</div></div>
        <div class="booking-step"><div class="booking-step__circle">4</div><div class="booking-step__label">Confirm</div></div>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert--error">
          <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Booking Form -->
      <form method="POST" action="" id="bookingForm" data-validate>
        <div class="form-msg hidden"></div>

        <!-- Step 1: Personal Details -->
        <div class="booking-panel card card__body">
          <h3 style="margin-bottom: 6px;">Your Details</h3>
          <p style="margin-bottom: 24px;">Tell us who you are so we can reach you.</p>
          <div class="form-grid">
            <div class="form-group">
              <label>Full Name <span class="req">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="John Smith" required
                     value="<?= e($_POST['name'] ?? $loggedUser['name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Phone <span class="req">*</span></label>
              <input type="tel" name="phone" class="form-control" placeholder="(555) 000-0000" required
                     value="<?= e($_POST['phone'] ?? $loggedUser['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Email <span class="req">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="john@example.com" required
                   value="<?= e($_POST['email'] ?? $loggedUser['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Service Address <span class="req">*</span></label>
            <input type="text" name="address" class="form-control" placeholder="123 Main St, Springfield, IL" required value="<?= e($_POST['address'] ?? '') ?>">
          </div>
          <div class="flex justify-end mt-2">
            <button type="button" class="btn btn--primary" data-action="next">Next <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg></button>
          </div>
        </div>

        <!-- Step 2: Service Selection -->
        <div class="booking-panel card card__body hidden">
          <h3 style="margin-bottom: 6px;">Select a Service</h3>
          <p style="margin-bottom: 24px;">What kind of plumbing service do you need?</p>
          <div class="form-group">
            <label>Service <span class="req">*</span></label>
            <select name="service" class="form-control" required>
              <option value="">-- Choose a service --</option>
              <?php foreach ($services as $svc): ?>
                <option value="<?= e($svc['title']) ?>" <?= ($_POST['service'] ?? $prefill) === $svc['title'] ? 'selected' : '' ?>>
                  <?= e($svc['title']) ?> — <?= e(format_service_price($svc['price'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Describe the Problem</label>
            <textarea name="message" class="form-control" placeholder="Please describe your plumbing issue in detail..."><?= e($_POST['message'] ?? '') ?></textarea>
          </div>
          <div class="flex gap-2 justify-between mt-2">
            <button type="button" class="btn btn--outline" data-action="prev"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12,19 5,12 12,5"/></svg> Back</button>
            <button type="button" class="btn btn--primary" data-action="next">Next <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg></button>
          </div>
        </div>

        <!-- Step 3: Schedule -->
        <div class="booking-panel card card__body hidden">
          <h3 style="margin-bottom: 6px;">Schedule Your Appointment</h3>
          <p style="margin-bottom: 24px;">Pick a date and time that works for you.</p>
          <div class="form-grid">
            <div class="form-group">
              <label>Preferred Date <span class="req">*</span></label>
              <input type="date" name="date" class="form-control" min="<?= date('Y-m-d') ?>" required value="<?= e($_POST['date'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Preferred Time <span class="req">*</span></label>
              <select name="time" class="form-control" required>
                <option value="">-- Select --</option>
                <option <?= ($_POST['time'] ?? '') === '7:00 AM - 9:00 AM' ? 'selected' : '' ?>>7:00 AM - 9:00 AM</option>
                <option <?= ($_POST['time'] ?? '') === '9:00 AM - 11:00 AM' ? 'selected' : '' ?>>9:00 AM - 11:00 AM</option>
                <option <?= ($_POST['time'] ?? '') === '11:00 AM - 1:00 PM' ? 'selected' : '' ?>>11:00 AM - 1:00 PM</option>
                <option <?= ($_POST['time'] ?? '') === '1:00 PM - 3:00 PM' ? 'selected' : '' ?>>1:00 PM - 3:00 PM</option>
                <option <?= ($_POST['time'] ?? '') === '3:00 PM - 5:00 PM' ? 'selected' : '' ?>>3:00 PM - 5:00 PM</option>
                <option <?= ($_POST['time'] ?? '') === '5:00 PM - 7:00 PM' ? 'selected' : '' ?>>5:00 PM - 7:00 PM</option>
              </select>
            </div>
          </div>
          <div class="flex gap-2 justify-between mt-2">
            <button type="button" class="btn btn--outline" data-action="prev"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12,19 5,12 12,5"/></svg> Back</button>
            <button type="button" class="btn btn--primary" data-action="next">Review <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg></button>
          </div>
        </div>

        <!-- Step 4: Confirm -->
        <div class="booking-panel card card__body hidden">
          <h3 style="margin-bottom: 6px;">Review &amp; Confirm</h3>
          <p style="margin-bottom: 24px;">Please review your details and submit your booking request.</p>
          <div style="background:var(--c-bg-alt);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
            <p style="margin-bottom:8px;"><strong>Note:</strong> This is a request — we'll call you to confirm the exact time and provide a quote.</p>
            <p style="font-size:0.85rem;">For emergencies, call <a href="tel:<?= e(SITE_PHONE) ?>" style="color:var(--c-accent);font-weight:600;"><?= e(SITE_PHONE) ?></a> for immediate assistance.</p>
          </div>
          <div class="flex gap-2 justify-between">
            <button type="button" class="btn btn--outline" data-action="prev"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12,19 5,12 12,5"/></svg> Back</button>
            <button type="submit" class="btn btn--accent btn--lg">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"/></svg>
              Confirm Booking
            </button>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>