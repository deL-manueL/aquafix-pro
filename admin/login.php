<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
start_session();

/* If already logged in as admin, go to dashboard */
if (!empty($_SESSION['admin_logged_in'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['name'] ?? '');
    $pass     = $_POST['password'] ?? '';

    if ($username === '' || $pass === '') {
        $error = 'Please enter both name and password.';
    } else {
        // Look up by NAME (case-insensitive)
        $user = db_run("SELECT * FROM users WHERE LOWER(name) = LOWER(?) LIMIT 1", [$username])->fetch();

        if (!$user) {
            $error = 'No account found with that name.';
        } elseif ($user['role'] !== 'admin') {
            $error = 'This account is not an administrator.';
        } elseif (!password_verify($pass, $user['password_hash'])) {
            $error = 'Incorrect password.';
        } else {
            // ✅ Success
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = (int)$user['id'];
            $_SESSION['admin_name']      = $user['name'];
            $_SESSION['admin_email']     = $user['email'];
            redirect('index.php');
        }
    }
}

$pageTitle = 'Admin Login — ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="logo" style="justify-content:center;">
      <span class="logo__icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
      </span>
      AquaFix<span style="color:var(--c-accent)">Pro</span>
    </div>
    <h2>Admin Login</h2>
    <p class="subtitle">Sign in to manage the platform</p>

    <?php if ($error): ?>
      <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="name" class="form-control"
               placeholder="Administrator" required autofocus
               value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn--primary btn--block btn--lg">Sign In</button>
    </form>

    <div class="alert alert--info mt-4" style="font-size:0.82rem;">
      <strong>Login:</strong> Administrator / aquafix2024
    </div>

    <div class="text-center mt-4">
      <a href="../index.php" style="color:var(--c-text-light);font-size:0.88rem;">&larr; Back to website</a>
    </div>
  </div>
</div>
</body>
</html>