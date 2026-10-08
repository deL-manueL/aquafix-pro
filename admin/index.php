<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
start_session();

/* 🔐 REQUIRE ADMIN LOGIN */
if (empty($_SESSION['admin_logged_in'])) {
    redirect('login.php');
}

$admin = [
    'id'    => $_SESSION['admin_id']    ?? 0,
    'name'  => $_SESSION['admin_name']  ?? 'Admin',
    'email' => $_SESSION['admin_email'] ?? '',
];

/* Update booking status + notify user */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    $allowed = ['pending','confirmed','in_progress','completed','cancelled'];
    if (in_array($status, $allowed, true)) {
        $b = db_run("SELECT * FROM bookings WHERE id = ? LIMIT 1", [$id])->fetch();
        db_run("UPDATE bookings SET status = ? WHERE id = ?", [$status, $id]);

        if ($b && !empty($b['user_id'])) {
            $niceStatus = ucfirst(str_replace('_', ' ', $status));
            $link = ($status === 'completed')
                ? "../user/review.php?booking=$id"
                : "../user/my-bookings.php";
            $body = "Your booking #$id is now: $niceStatus.";
            if ($status === 'completed') $body .= " Please take a moment to rate the service ⭐";
            try {
                db_run(
                    "INSERT INTO notifications (user_id, title, body, link, created_at)
                     VALUES (?, ?, ?, ?, NOW())",
                    [(int)$b['user_id'], "Booking #$id — $niceStatus", $body, $link]
                );
            } catch (Exception $e) {}
        }
    }
    redirect('index.php?tab=bookings');
}

/* Delete record */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $table = $_POST['table'] ?? '';
    $allowed = ['bookings','messages','newsletter'];
    if (in_array($table, $allowed, true)) {
        delete_record($table, $id);
    }
    redirect('index.php?tab=' . $table);
}

$tab = $_GET['tab'] ?? 'overview';

/* Stats */
$totalBookings  = (int) db_run("SELECT COUNT(*) c FROM bookings")->fetch()['c'];
$pending        = (int) db_run("SELECT COUNT(*) c FROM bookings WHERE status='pending'")->fetch()['c'];
$completed      = (int) db_run("SELECT COUNT(*) c FROM bookings WHERE status='completed'")->fetch()['c'];
$totalUsers     = (int) db_run("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch()['c'];
$totalMessages  = (int) db_run("SELECT COUNT(*) c FROM messages")->fetch()['c'];
$newMessages    = (int) db_run("SELECT COUNT(*) c FROM messages WHERE status='new'")->fetch()['c'];
$totalSubs      = (int) db_run("SELECT COUNT(*) c FROM newsletter")->fetch()['c'];
$totalReviews   = (int) db_run("SELECT COUNT(*) c FROM reviews")->fetch()['c'];
$totalProducts  = (int) db_run("SELECT COUNT(*) c FROM products WHERE is_active = 1")->fetch()['c'];
$totalPackages  = (int) db_run("SELECT COUNT(*) c FROM packages WHERE is_active = 1")->fetch()['c'];
$totalOrders    = (int) db_run("SELECT COUNT(*) c FROM orders WHERE is_deleted = 0")->fetch()['c'];
$pendingRefunds = (int) db_run("SELECT COUNT(*) c FROM orders WHERE payment_status = 'refund_requested'")->fetch()['c'];
$__unreadChats  = (int) db_run("SELECT COUNT(DISTINCT conversation_id) c FROM chat_messages WHERE sender_type='visitor' AND is_read=0")->fetch()['c'];

$bookings  = get_records('bookings');
$messages  = get_records('messages');
$subs      = get_records('newsletter');
$users     = db_run("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$activity  = db_run("SELECT a.*, u.name AS user_name FROM activity_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — <?= e(SITE_NAME) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="logo">
      <span class="logo__icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
      </span>
      AquaFix<span style="color:var(--c-accent)">Pro</span>
    </div>
    <nav class="admin-nav">
      <a href="?tab=overview"   class="<?= $tab==='overview'   ? 'active':'' ?>">Overview</a>
      <a href="?tab=bookings"   class="<?= $tab==='bookings'   ? 'active':'' ?>">Bookings (<?= $totalBookings ?>)</a>
      <a href="?tab=users"      class="<?= $tab==='users'      ? 'active':'' ?>">Users (<?= $totalUsers ?>)</a>
      <a href="?tab=messages"   class="<?= $tab==='messages'   ? 'active':'' ?>">Messages (<?= $totalMessages ?>)</a>
      <a href="?tab=reviews"    class="<?= $tab==='reviews'    ? 'active':'' ?>">Reviews (<?= $totalReviews ?>)</a>
      <a href="products.php">Products (<?= $totalProducts ?>)</a>
      <a href="packages.php">Packages (<?= $totalPackages ?>)</a>
      <a href="orders.php">
        Orders (<?= $totalOrders ?>)
        <?php if ($pendingRefunds > 0): ?>
          <span style="background:#ef4444;color:#fff;padding:2px 8px;border-radius:999px;font-size:0.7rem;margin-left:6px;"><?= $pendingRefunds ?> refund<?= $pendingRefunds > 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </a>
      <a href="chats.php">
        Live Chats
        <?php if ($__unreadChats > 0): ?>
          <span style="background:#ef4444;color:#fff;padding:2px 8px;border-radius:999px;font-size:0.7rem;margin-left:6px;"><?= $__unreadChats ?></span>
        <?php endif; ?>
      </a>
      <a href="scan.php">📱 Scan QR</a>
      <a href="?tab=newsletter" class="<?= $tab==='newsletter' ? 'active':'' ?>">Newsletter (<?= $totalSubs ?>)</a>
      <a href="?tab=activity"   class="<?= $tab==='activity'   ? 'active':'' ?>">Activity Log</a>
      <a href="../index.php">View Website</a>
      <a href="logout.php" style="color:#f87171;">Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h2 style="margin-bottom:4px;"><?= ucfirst(e($tab)) ?></h2>
        <p style="margin:0;font-size:0.9rem;">
          Welcome, <?= e($admin['name']) ?> • <?= date('l, F j, Y') ?>
        </p>
      </div>
      <a href="../index.php" class="btn btn--outline btn--sm">View Website</a>
    </div>

    <?php if ($tab === 'overview'): ?>

      <div class="admin-cards">
        <div class="admin-card">
          <div class="num"><?= $totalBookings ?></div>
          <div class="lbl">Total Bookings</div>
        </div>
        <div class="admin-card">
          <div class="num"><?= $pending ?></div>
          <div class="lbl">Pending</div>
        </div>
        <div class="admin-card">
          <div class="num"><?= $completed ?></div>
          <div class="lbl">Completed</div>
        </div>
        <div class="admin-card">
          <div class="num"><?= $totalUsers ?></div>
          <div class="lbl">Customers</div>
        </div>
        <div class="admin-card">
          <div class="num"><?= $totalProducts ?></div>
          <div class="lbl">Products</div>
        </div>
        <div class="admin-card">
          <div class="num"><?= $totalOrders ?></div>
          <div class="lbl">Shop Orders</div>
        </div>
      </div>

      <h3 style="margin-bottom:16px;">Bookings last 7 days</h3>
      <div class="card" style="padding:20px;margin-bottom:32px;">
        <?php
        $labels = $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime($day));
            $c = db_run("SELECT COUNT(*) c FROM bookings WHERE DATE(created_at) = ?", [$day])->fetch()['c'];
            $values[] = (int)$c;
        }
        ?>
        <canvas id="chartBookings" height="90"></canvas>
      </div>

      <h3 style="margin-bottom:16px;">Recent Activity</h3>
      <?php if (!$activity): ?>
        <div class="alert alert--info">No activity yet.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
          <tbody>
            <?php foreach (array_slice($activity, 0, 8) as $a): ?>
              <tr>
                <td><?= e(date('M j, H:i', strtotime($a['created_at']))) ?></td>
                <td><?= e($a['user_name'] ?? 'Guest') ?></td>
                <td><code><?= e($a['action']) ?></code></td>
                <td><?= e($a['details']) ?></td>
                <td><?= e($a['ip_address']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <script>
        new Chart(document.getElementById('chartBookings'), {
          type: 'line',
          data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
              label: 'Bookings',
              data: <?= json_encode($values) ?>,
              borderColor: '#0ea5e9',
              backgroundColor: 'rgba(14,165,233,0.15)',
              fill: true, tension: 0.35
            }]
          },
          options: { responsive: true, plugins: { legend: { display: false } } }
        });
      </script>

    <?php elseif ($tab === 'bookings'): ?>
      <?php if (!$bookings): ?>
        <div class="alert alert--info">No bookings yet.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr><th>#</th><th>Name</th><th>Contact</th><th>Date</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td>#<?= (int)$b['id'] ?></td>
                <td><?= e($b['name']) ?></td>
                <td><?= e($b['email']) ?><br><small><?= e($b['phone']) ?></small></td>
                <td><?= e($b['booking_date']) ?><br><small><?= e($b['time_slot']) ?></small></td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                    <select name="status" class="form-control" style="padding:6px 10px;font-size:0.82rem;width:auto;display:inline-block;" onchange="this.form.submit()">
                      <?php foreach (['pending','confirmed','in_progress','completed','cancelled'] as $st): ?>
                        <option value="<?= $st ?>" <?= $b['status']===$st?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$st)) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                </td>
                <td>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this booking?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="table" value="bookings">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                    <button class="btn btn--ghost btn--sm" style="color:var(--c-error);">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

    <?php elseif ($tab === 'users'): ?>
      <table class="admin-table">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td>#<?= (int)$u['id'] ?></td>
              <td><?= e($u['name']) ?></td>
              <td><?= e($u['email']) ?></td>
              <td><?= e($u['phone'] ?? '—') ?></td>
              <td><span class="badge badge--<?= $u['role']==='admin'?'done':'pending' ?>"><?= e($u['role']) ?></span></td>
              <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php elseif ($tab === 'messages'): ?>
      <?php if (!$messages): ?>
        <div class="alert alert--info">No messages yet.</div>
      <?php else: ?>
        <div style="display:grid;gap:16px;">
          <?php foreach ($messages as $m): ?>
            <div class="card card__body">
              <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                  <strong><?= e($m['name']) ?></strong>
                  <span style="color:var(--c-text-muted);font-size:0.85rem;"> • <?= e($m['created_at']) ?></span><br>
                  <a href="mailto:<?= e($m['email']) ?>" style="color:var(--c-accent);font-size:0.88rem;"><?= e($m['email']) ?></a>
                  <?php if (!empty($m['phone'])): ?> • <span style="font-size:0.88rem;"><?= e($m['phone']) ?></span><?php endif; ?>
                </div>
                <form method="POST" onsubmit="return confirm('Delete this message?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="table" value="messages">
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <button class="btn btn--ghost btn--sm" style="color:var(--c-error);">Delete</button>
                </form>
              </div>
              <?php if (!empty($m['subject'])): ?><div style="margin-top:10px;font-weight:600;"><?= e($m['subject']) ?></div><?php endif; ?>
              <p style="margin-top:8px;"><?= e($m['message']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($tab === 'reviews'): ?>
      <?php
      $reviews = db_run(
          "SELECT r.*, u.name AS user_name, u.email AS user_email,
                  b.booking_date, s.title AS service_title
           FROM reviews r
           INNER JOIN users    u ON u.id = r.user_id
           INNER JOIN bookings b ON b.id = r.booking_id
           LEFT  JOIN services s ON s.id = b.service_id
           ORDER BY r.created_at DESC"
      )->fetchAll();
      ?>
      <?php if (!$reviews): ?>
        <div class="alert alert--info">
          No reviews yet. Once customers complete bookings and rate them, they'll appear here.
        </div>
      <?php else: ?>
        <div style="display:grid;gap:16px;">
          <?php foreach ($reviews as $r): ?>
            <div class="card card__body">
              <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                  <strong><?= e($r['user_name']) ?></strong>
                  <span style="color:var(--c-text-muted);font-size:0.85rem;">
                    • <?= e($r['user_email']) ?>
                    • <?= e(date('M j, Y H:i', strtotime($r['created_at']))) ?>
                  </span><br>
                  <span style="color:var(--c-text-muted);font-size:0.85rem;">
                    Service: <strong><?= e($r['service_title'] ?? 'N/A') ?></strong>
                    • Booking #<?= (int)$r['booking_id'] ?>
                  </span>
                </div>
                <div style="font-size:1.3rem;color:#f59e0b;letter-spacing:2px;">
                  <?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?>
                </div>
              </div>
              <p style="margin-top:12px;"><?= nl2br(e($r['comment'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($tab === 'newsletter'): ?>
      <?php if (!$subs): ?>
        <div class="alert alert--info">No subscribers yet.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>Email</th><th>Subscribed</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($subs as $s): ?>
              <tr>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['created_at']) ?></td>
                <td>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Remove?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="table" value="newsletter">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="btn btn--ghost btn--sm" style="color:var(--c-error);">Remove</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

    <?php elseif ($tab === 'activity'): ?>
      <?php if (!$activity): ?>
        <div class="alert alert--info">No activity logged yet.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
          <tbody>
            <?php foreach ($activity as $a): ?>
              <tr>
                <td><?= e($a['created_at']) ?></td>
                <td><?= e($a['user_name'] ?? 'Guest') ?></td>
                <td><code><?= e($a['action']) ?></code></td>
                <td><?= e($a['details']) ?></td>
                <td><?= e($a['ip_address']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>