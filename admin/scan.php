<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) {
    redirect('login.php');
}

$admin = [
    'name'  => $_SESSION['admin_name']  ?? 'Admin',
    'email' => $_SESSION['admin_email'] ?? '',
];

$result = null;

/* Handle QR token submission (camera OR manual) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['qr_token'])) {
    $token = trim($_POST['qr_token']);

    if (preg_match('/^AQUAFIX\|BOOKING\|(\d+)$/', $token, $m)) {
        $bookingId = (int)$m[1];

        $booking = db_run(
            "SELECT b.*, s.title AS service_title, u.name AS user_name
             FROM bookings b
             LEFT JOIN services s ON s.id = b.service_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE b.id = ? LIMIT 1",
            [$bookingId]
        )->fetch();

        if (!$booking) {
            $result = ['type' => 'error', 'msg' => "No booking found with ID #$bookingId"];
        } elseif ($booking['status'] === 'in_progress') {
            $result = ['type' => 'info', 'msg' => "Booking #$bookingId is already In Progress."];
        } elseif (!in_array($booking['status'], ['confirmed','pending'], true)) {
            $result = ['type' => 'error', 'msg' => "Booking #$bookingId is '{$booking['status']}' — cannot check in."];
        } else {
            db_run("UPDATE bookings SET status = 'in_progress' WHERE id = ?", [$bookingId]);

            if (!empty($booking['user_id'])) {
                db_run(
                    "INSERT INTO notifications (user_id, title, body, link, created_at)
                     VALUES (?, ?, ?, ?, NOW())",
                    [
                        (int)$booking['user_id'],
                        "Technician arrived",
                        "Your technician has arrived for booking #$bookingId — your service is now In Progress.",
                        "../user/my-bookings.php"
                    ]
                );
            }

            log_activity((int)($_SESSION['admin_id'] ?? 0), 'qr_checkin', "Checked in booking #$bookingId via QR");

            $result = [
                'type' => 'success',
                'msg'  => "✅ Booking #$bookingId checked in! Status: In Progress."
                        . ($booking['user_name'] ? " Customer: {$booking['user_name']}" : '')
            ];
        }
    } else {
        $result = ['type' => 'error', 'msg' => 'Invalid QR code format.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scan QR — <?= e(SITE_NAME) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="logo">
      <span class="logo__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg></span>
      AquaFix<span style="color:var(--c-accent)">Pro</span>
    </div>
    <nav class="admin-nav">
      <a href="index.php?tab=overview">Overview</a>
      <a href="index.php?tab=bookings">Bookings</a>
      <a href="index.php?tab=users">Users</a>
      <a href="index.php?tab=messages">Messages</a>
      <a href="index.php?tab=reviews">Reviews</a>
      <a href="packages.php">Packages</a>
      <a href="orders.php">Orders</a>
      <a href="chats.php">Live Chats</a>
      <a href="scan.php" class="active">📱 Scan QR</a>
      <a href="index.php?tab=activity">Activity Log</a>
      <a href="../index.php">View Website</a>
      <a href="logout.php" style="color:#f87171;">Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h2 style="margin-bottom:4px;">Scan QR Code</h2>
        <p style="margin:0;font-size:0.9rem;">Point the camera at a customer's booking QR.</p>
      </div>
      <a href="index.php" class="btn btn--outline btn--sm">← Dashboard</a>
    </div>

    <?php if ($result): ?>
      <div class="alert alert--<?= e($result['type']) ?>" style="font-size:1rem;">
        <?= e($result['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="card card__body" style="max-width:600px;">
      <h3 style="margin-bottom:16px;">📷 Live Camera Scanner</h3>
      <div id="qr-reader" style="width:100%;min-height:200px;background:#000;border-radius:12px;"></div>
      <p style="font-size:0.85rem;color:var(--c-text-muted);margin-top:16px;">
        If the camera doesn't work, use <strong>manual entry</strong> below.
      </p>
    </div>

    <div class="card card__body" style="max-width:600px;margin-top:24px;">
      <h3 style="margin-bottom:16px;">⌨️ Manual QR Token Entry</h3>
      <form method="POST">
        <div class="form-group">
          <label>Paste QR token</label>
          <input type="text" name="qr_token" class="form-control"
                 placeholder="AQUAFIX|BOOKING|5" required
                 value="<?= e($_POST['qr_token'] ?? '') ?>">
          <small style="color:var(--c-text-muted);font-size:0.82rem;">
            Format: <code>AQUAFIX|BOOKING|{booking_id}</code>
          </small>
        </div>
        <button class="btn btn--primary">Check In Booking</button>
      </form>
    </div>
  </main>
</div>

<script>
let html5QrCode = null;

function onScanSuccess(decodedText) {
    if (html5QrCode) html5QrCode.stop().catch(() => {});
    const f = document.createElement('form');
    f.method = 'POST';
    const input = document.createElement('input');
    input.name = 'qr_token';
    input.value = decodedText;
    f.appendChild(input);
    document.body.appendChild(f);
    f.submit();
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof Html5Qrcode === 'undefined') return;
    html5QrCode = new Html5Qrcode("qr-reader");
    Html5Qrcode.getCameras()
        .then(cameras => {
            if (!cameras || !cameras.length) {
                document.getElementById('qr-reader').innerHTML =
                    '<p style="color:#ef4444;padding:20px;">No camera detected. Use manual entry below.</p>';
                return;
            }
            const back = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[0];
            return html5QrCode.start(
                back.id,
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                () => {}
            );
        })
        .catch(err => {
            document.getElementById('qr-reader').innerHTML =
                '<p style="color:#ef4444;padding:20px;">Camera not available. Use manual entry below.</p>';
        });
});
</script>
</body>
</html>