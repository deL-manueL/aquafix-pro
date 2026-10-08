<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(is_admin() ? '../admin/index.php' : '../user/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!rate_limit('login', 5, 60)) {
        $error = 'Too many login attempts. Please wait a minute.';
    } else {
        $email = clean($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        [$ok, $err] = attempt_login($email, $pass);
        if ($ok) {
            $u = current_user();
            redirect(($u['role'] ?? '') === 'admin' ? '../admin/index.php' : '../user/dashboard.php');
        } else {
            $error = $err;
        }
    }
}

$pageTitle = 'Login — ' . SITE_NAME;
$baseHref  = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Welcome Back</h1>
    <p>Log in to manage your bookings</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:460px;">
    <div class="card card__body reveal">
      <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control" required autofocus value="<?= e($email) ?>">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn--primary btn--block btn--lg">Sign In</button>
      </form>

      <p style="text-align:center;margin-top:20px;font-size:0.9rem;">
        No account? <a href="register.php" style="color:var(--c-accent);font-weight:600;">Register</a>
      </p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>