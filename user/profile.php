<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name  = clean($_POST['name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if ($pass !== '' && strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $fields = ['name' => $name, 'phone' => $phone];
        if ($pass !== '') {
            $fields['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        $set = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($fields)));
        $params = [':id' => $user['id']];
        foreach ($fields as $k => $v) $params[':' . $k] = $v;
        db_run("UPDATE users SET $set WHERE id = :id", $params);

        log_activity((int)$user['id'], 'profile_update', 'Updated own profile');
        set_flash('Profile updated successfully.', 'success');
        redirect('profile.php');
    }
}

$flash = get_flash();
$pageTitle = 'My Profile — ' . SITE_NAME;
$baseHref  = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>My Profile</h1>
    <p>Update your personal information</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:560px;">

    <div class="filter-bar" style="justify-content:flex-start;">
      <a href="dashboard.php" class="filter-btn">Dashboard</a>
      <a href="my-bookings.php" class="filter-btn">My Bookings</a>
      <a href="notifications.php" class="filter-btn">Notifications</a>
      <a href="profile.php" class="filter-btn active">Profile</a>
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
      <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Email <small style="color:var(--c-text-muted)">(cannot be changed)</small></label>
          <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
        </div>
        <hr style="margin:24px 0;border:none;border-top:1px solid var(--c-border);">
        <h4 style="margin-bottom:12px;">Change Password <small style="color:var(--c-text-muted);font-weight:400;">(leave blank to keep current)</small></h4>
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="password" class="form-control" minlength="8">
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="password2" class="form-control" minlength="8">
        </div>
        <button type="submit" class="btn btn--primary btn--block btn--lg">Save Changes</button>
      </form>
    </div>

  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>