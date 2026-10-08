<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) redirect('../user/dashboard.php');

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!rate_limit('register', 5, 300)) {
        $errors[] = 'Too many signup attempts. Please wait a few minutes.';
    } else {
        $old = [
            'name'  => clean($_POST['name']  ?? ''),
            'email' => clean($_POST['email'] ?? ''),
            'phone' => clean($_POST['phone'] ?? ''),
        ];
        $pass  = $_POST['password']  ?? '';
        $pass2 = $_POST['password2'] ?? '';

        if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

        if (!$errors) {
            [$ok, $err, $id] = register_user($old['name'], $old['email'], $old['phone'], $pass);
            if ($ok) {
                $_SESSION['user_id'] = $id;
                set_flash('Welcome to AquaFix Pro!', 'success');
                redirect('../user/dashboard.php');
            } else {
                $errors[] = $err;
            }
        }
    }
}

$pageTitle = 'Create Account — ' . SITE_NAME;
$baseHref  = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Create Your Account</h1>
    <p>Book services faster and track your appointments</p>
    <div class="breadcrumb"><a href="<?= $baseHref ?>index.php">Home</a> <span class="sep">/</span> <span>Register</span></div>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:520px;">
    <div class="card card__body reveal">
      <h3 style="margin-bottom:6px;">Sign Up</h3>
      <p style="margin-bottom:24px;">It only takes a minute.</p>

      <?php if ($errors): ?>
        <div class="alert alert--error">
          <?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" data-validate>
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Full Name <span class="req">*</span></label>
          <input type="text" name="name" class="form-control" required value="<?= e($old['name']) ?>">
        </div>
        <div class="form-group">
          <label>Email <span class="req">*</span></label>
          <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="tel" name="phone" class="form-control" value="<?= e($old['phone']) ?>">
        </div>
        <div class="form-group">
          <label>Password <span class="req">*</span> <small style="color:var(--c-text-muted)">(min 8 characters)</small></label>
          <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="form-group">
          <label>Confirm Password <span class="req">*</span></label>
          <input type="password" name="password2" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn--primary btn--block btn--lg">Create Account</button>
      </form>

      <p style="text-align:center;margin-top:20px;font-size:0.9rem;">
        Already have an account? <a href="login.php" style="color:var(--c-accent);font-weight:600;">Log in</a>
      </p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>