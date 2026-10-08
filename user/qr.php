<?php
/**
 * Show the QR code for a booking.
 * Compatible with endroid/qr-code v6.
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

/* --- Load Composer autoload for QR library --- */
$autoload = __DIR__ . '/../vendor/autoload.php';
$hasQrLib = false;
if (file_exists($autoload)) {
    require_once $autoload;
    $hasQrLib = class_exists('Endroid\QrCode\QrCode');
}

$user      = current_user();
$bookingId = (int)($_GET['booking'] ?? 0);

/* --- Load booking (must belong to this user) --- */
$booking = db_run(
    "SELECT b.*, s.title AS service_title
     FROM bookings b
     LEFT JOIN services s ON s.id = b.service_id
     WHERE b.id = ? AND (b.user_id = ? OR b.email = ?) LIMIT 1",
    [$bookingId, $user['id'], $user['email']]
)->fetch();

if (!$booking) {
    http_response_code(404);
    die('Booking not found.');
}

if (!in_array($booking['status'], ['confirmed','in_progress'], true)) {
    set_flash('QR check-in is available once your booking is confirmed.', 'info');
    redirect('my-bookings.php');
}

/* The token encoded in the QR */
$token = 'AQUAFIX|BOOKING|' . $booking['id'];

/* Generate QR image data URI */
$qrDataUri = null;
$qrError   = null;

if ($hasQrLib) {
    try {
        /* v6 API — uses named-argument builder style */
        $qrCode = new \Endroid\QrCode\QrCode(
            data: $token,
            encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
            errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
            size: 320,
            margin: 10,
            roundBlockSizeMode: \Endroid\QrCode\RoundBlockSizeMode::Margin,
            foregroundColor: new \Endroid\QrCode\Color\Color(30, 58, 138),
            backgroundColor: new \Endroid\QrCode\Color\Color(255, 255, 255)
        );

        $writer    = new \Endroid\QrCode\Writer\PngWriter();
        $result    = $writer->write($qrCode);
        $qrDataUri = $result->getDataUri();
    } catch (Exception $e) {
        $qrError = 'QR generation failed: ' . $e->getMessage();
    }
} else {
    $qrError = 'QR library not installed. Run: composer require endroid/qr-code';
}

$flash = get_flash();
$pageTitle = 'Booking QR Code — ' . SITE_NAME;
$baseHref = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Your Booking QR Code</h1>
    <p>Show this to your technician on arrival</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:560px;">

    <div class="filter-bar" style="justify-content:flex-start;">
      <a href="dashboard.php" class="filter-btn">Dashboard</a>
      <a href="my-bookings.php" class="filter-btn active">My Bookings</a>
      <a href="notifications.php" class="filter-btn">Notifications</a>
      <a href="profile.php" class="filter-btn">Profile</a>
      <a href="../auth/logout.php" class="filter-btn">Logout</a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>" style="margin-top:20px;"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="card card__body text-center" style="margin-top:24px;padding:40px;">
      <h3 style="margin-bottom:8px;"><?= e($booking['service_title'] ?? 'Booking') ?></h3>
      <p style="color:var(--c-text-muted);font-size:0.9rem;margin-bottom:24px;">
        Booking #<?= (int)$booking['id'] ?> •
        <?= e(date('M j, Y', strtotime($booking['booking_date']))) ?> •
        <?= e($booking['time_slot']) ?>
      </p>

      <?php if ($qrDataUri): ?>
        <div style="display:inline-block;padding:20px;background:#fff;border-radius:16px;box-shadow:var(--shadow-md);">
          <img src="<?= $qrDataUri ?>" alt="Booking QR Code"
               style="width:280px;height:280px;display:block;">
        </div>

        <div style="margin-top:24px;">
          <p style="font-weight:600;font-size:0.95rem;">
            Booking ID: <code>#<?= (int)$booking['id'] ?></code>
          </p>
          <p style="font-size:0.85rem;color:var(--c-text-muted);margin-top:8px;line-height:1.6;">
            Show this QR to your technician when they arrive.<br>
            They'll scan it to mark your service as <strong>In Progress</strong>.
          </p>
        </div>

        <div style="margin-top:24px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
          <a href="my-bookings.php" class="btn btn--outline">← Back to Bookings</a>
          <button onclick="window.print()" class="btn btn--primary">🖨 Print QR</button>
        </div>

      <?php else: ?>
        <div class="alert alert--error" style="text-align:left;">
          <strong>QR code unavailable</strong><br>
          <?= e($qrError) ?>
        </div>

        <div style="margin-top:20px;padding:20px;background:var(--c-bg-alt);border-radius:12px;text-align:left;">
          <p style="font-size:0.85rem;margin:0;">
            <strong>Fallback — share this token with your technician manually:</strong>
          </p>
          <p style="font-family:monospace;font-size:1rem;background:#fff;padding:12px;border-radius:8px;margin-top:12px;border:1px dashed var(--c-border);">
            <?= e($token) ?>
          </p>
          <p style="font-size:0.78rem;color:var(--c-text-muted);margin-top:12px;">
            The technician can enter this in the admin scan page.
          </p>
        </div>

        <div style="margin-top:20px;">
          <a href="my-bookings.php" class="btn btn--outline">← Back to Bookings</a>
        </div>
      <?php endif; ?>
    </div>

    <div class="alert alert--info" style="margin-top:20px;font-size:0.88rem;">
      <strong>How it works:</strong>
      Your technician will scan this QR code on arrival.
      Your booking status will automatically change to <strong>In Progress</strong>,
      and you'll get a notification confirming their arrival.
    </div>

  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>