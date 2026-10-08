<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$bookingId = (int)($_GET['booking'] ?? 0);
$errors = [];

/* Load booking — must belong to user AND be completed */
$booking = db_run(
    "SELECT b.*, s.title AS service_title
     FROM bookings b
     LEFT JOIN services s ON s.id = b.service_id
     WHERE b.id = ? AND (b.user_id = ? OR b.email = ?) LIMIT 1",
    [$bookingId, $user['id'], $user['email']]
)->fetch();

if (!$booking) {
    set_flash('Booking not found.', 'error');
    redirect('my-bookings.php');
}
if ($booking['status'] !== 'completed') {
    set_flash('You can only review completed bookings.', 'info');
    redirect('my-bookings.php');
}

/* Check if already reviewed */
$existing = db_run("SELECT id FROM reviews WHERE booking_id = ? LIMIT 1", [$bookingId])->fetch();
if ($existing) {
    set_flash('You already reviewed this booking.', 'info');
    redirect('my-bookings.php');
}

/* Handle submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) $errors[] = 'Please select a rating from 1 to 5 stars.';
    if ($comment === '') $errors[] = 'Please write a short review.';
    if (strlen($comment) > 1000) $errors[] = 'Review is too long (max 1000 characters).';

    if (!$errors) {
        try {
            db_run(
                "INSERT INTO reviews (user_id, booking_id, rating, comment, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [$user['id'], $bookingId, $rating, $comment]
            );

            /* Notify admin */
            $admins = db_run("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $a) {
                db_run(
                    "INSERT INTO notifications (user_id, title, body, link, created_at)
                     VALUES (?, ?, ?, ?, NOW())",
                    [
                        (int)$a['id'],
                        "New review for " . ($booking['service_title'] ?? 'a service'),
                        "{$user['name']} rated {$rating}/5 stars: " . substr($comment, 0, 80) . '...',
                        "../admin/index.php?tab=reviews"
                    ]
                );
            }

            log_activity((int)$user['id'], 'review_submit', "Reviewed booking #$bookingId with $rating stars");
            set_flash('Thank you! Your review has been submitted.', 'success');
            redirect('my-bookings.php');
        } catch (Exception $e) {
            $errors[] = 'Could not save review. Please try again.';
        }
    }
}

$flash = get_flash();
$pageTitle = 'Write a Review — ' . SITE_NAME;
$baseHref = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Write a Review</h1>
    <p>Rate your experience with AquaFix Pro</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:600px;">

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

    <?php if ($errors): ?>
      <div class="alert alert--error">
        <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card card__body" style="margin-top:24px;">
      <h3 style="margin-bottom:6px;">Rate: <?= e($booking['service_title'] ?? 'Your Service') ?></h3>
      <p style="color:var(--c-text-muted);font-size:0.9rem;margin-bottom:24px;">
        Booking #<?= (int)$booking['id'] ?> • <?= e(date('M j, Y', strtotime($booking['booking_date']))) ?>
      </p>

      <form method="POST">
        <!-- Star rating -->
        <div class="form-group">
          <label>Your Rating <span class="req">*</span></label>
          <div id="starRating" style="display:flex;gap:8px;font-size:2.5rem;cursor:pointer;user-select:none;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star" data-value="<?= $i ?>" style="color:#cbd5e1;transition:color .15s;">★</span>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="ratingInput" value="0">
          <div id="ratingLabel" style="margin-top:8px;font-size:0.9rem;color:var(--c-text-muted);">Click a star to rate</div>
        </div>

        <div class="form-group">
          <label>Your Review <span class="req">*</span></label>
          <textarea name="comment" class="form-control" rows="5"
                    placeholder="Tell us about your experience..."
                    maxlength="1000" required><?= e($_POST['comment'] ?? '') ?></textarea>
          <div style="font-size:0.8rem;color:var(--c-text-muted);margin-top:6px;">Max 1000 characters</div>
        </div>

        <div style="display:flex;gap:12px;">
          <a href="my-bookings.php" class="btn btn--outline">Cancel</a>
          <button type="submit" class="btn btn--primary" style="flex:1;">Submit Review</button>
        </div>
      </form>
    </div>

  </div>
</section>

<script>
(function() {
  const stars = document.querySelectorAll('#starRating .star');
  const input = document.getElementById('ratingInput');
  const label = document.getElementById('ratingLabel');
  const labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

  stars.forEach(star => {
    star.addEventListener('mouseenter', () => paint(parseInt(star.dataset.value), '#f59e0b'));
    star.addEventListener('mouseleave', () => paint(parseInt(input.value), '#f59e0b'));
    star.addEventListener('click', () => {
      input.value = star.dataset.value;
      paint(parseInt(star.dataset.value), '#f59e0b');
      label.textContent = labels[star.dataset.value] + ' (' + star.dataset.value + '/5)';
      label.style.color = '#f59e0b';
    });
  });

  function paint(upTo, color) {
    stars.forEach(s => {
      s.style.color = parseInt(s.dataset.value) <= upTo ? color : '#cbd5e1';
    });
  }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>