<?php
/** @var string $base @var string|null $error */
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - StockWise</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= $base ?>assets/css/style.css?v=<?= filemtime(BASE_PATH . 'public/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="auth-split">
    <!-- LEFT: form + logo -->
    <div class="auth-form-panel">
      <div class="auth-form-inner mx-auto">
        <div class="auth-brand">
          <i class="bi bi-box-seam"></i> <span>StockWise</span>
        </div>
        <h1 class="h3 fw-bold mb-1">Welcome back</h1>
        <p class="text-muted mb-4">Sign in to manage inventory &amp; procurement.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= Security::e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $base ?>login">
          <?= Security::csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="you@college.edu.ph" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>
        </form>

        <div class="d-flex justify-content-between mt-3">
          <a href="<?= $base ?>forgot" class="small">Forgot password?</a>
          <a href="<?= $base ?>register" class="small">Register</a>
        </div>
      </div>
    </div>

    <!-- RIGHT: campus branding -->
    <div class="auth-right-panel">
      <img src="<?= $base ?>assets/images/tcgc-hero.svg" alt="Tangub City Global College">
      <h2>Tangub City Global College</h2>
      <p>Empowering responsible stewardship of school resources with a modern, transparent inventory and procurement management system.</p>
    </div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
