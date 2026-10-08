<?php
require_once __DIR__ . '/config/config.php';
http_response_code(404);
$pageTitle = 'Page Not Found — ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>
<section class="error-page section">
  <div class="container">
    <div class="code">404</div>
    <h2>Page Not Found</h2>
    <p>The page you're looking for doesn't exist or has been moved. Let's get you back on track.</p>
    <div class="flex gap-2 justify-center flex-wrap">
      <a href="index.php" class="btn btn--primary btn--lg">Back to Home</a>
      <a href="contact.php" class="btn btn--outline btn--lg">Contact Us</a>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
