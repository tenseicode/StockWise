<?php
/** @var string $base @var string|null $error @var string|null $success */
$error = $error ?? null;
$success = $success ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - StockWise</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= $base ?>assets/css/style.css?v=<?= filemtime(BASE_PATH . 'public/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="auth-split">
    <!-- LEFT: reset form -->
    <div class="auth-panel auth-left">
      <div class="panel-content auth-login-panel">
        <div class="auth-form-inner">
          <div class="auth-brand">
            <img class="auth-logo" src="<?= $base ?>assets/images/stockwise-logo-transparentbg.png" alt="StockWise logo">
            <span>StockWise</span>
          </div>
          <h1 class="h3 fw-bold mb-1">Reset Password</h1>
          <p class="text-muted mb-4">Enter your account email to reset your password.</p>
          <?php if ($error): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>
          <?php if ($success): ?><div class="alert alert-success"><?= Security::e($success) ?></div><?php endif; ?>
          <form method="post" action="<?= $base ?>forgot">
            <?= Security::csrfField() ?>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="you@college.edu.ph" required>
            </div>
            <button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat"></i> Reset Password</button>
          </form>
          <div class="text-center mt-3"><a href="<?= $base ?>login" class="small">Back to login</a></div>
        </div>
      </div>
    </div>

    <!-- RIGHT: campus branding -->
    <div class="auth-panel auth-right">
      <div class="panel-content auth-brand-panel">
        <div class="auth-brand"><img class="tcgc-logo" src="<?= $base ?>assets/images/tcgc-logo.jpg" alt="TCGC logo"></div>
        <h2>Tangub City Global College</h2>
        <p>Empowering responsible stewardship of school resources with a modern, transparent inventory and procurement management system.</p>
      </div>
    </div>
  </div>
<script src="<?= $base ?>assets/js/app.js?v=<?= filemtime(BASE_PATH . 'public/assets/js/app.js') ?>"></script>
</body>
</html>
