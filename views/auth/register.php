<?php
/** @var string $base @var array $offices @var string|null $error */
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - StockWise</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= $base ?>assets/css/style.css?v=<?= filemtime(BASE_PATH . 'public/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="auth-split auth-split-reverse">
    <!-- LEFT: form + logo -->
    <div class="auth-form-panel">
      <div class="auth-form-inner mx-auto">
        <div class="auth-brand">
          <i class="bi bi-person-plus"></i> <span>StockWise</span>
        </div>
        <h1 class="h3 fw-bold mb-1">Create an Account</h1>
        <p class="text-muted mb-4">Register to create and track procurement requests.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= Security::e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $base ?>register">
          <?= Security::csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" placeholder="Juan Dela Cruz" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="you@college.edu.ph" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Office</label>
            <select name="office_id" class="form-select">
              <option value="">-- select office (optional) --</option>
              <?php foreach ($offices as $o): ?>
                <option value="<?= (int)$o['id'] ?>"><?= Security::e($o['office_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Password (min 8 characters)</label>
            <input type="password" name="password" class="form-control" minlength="8" placeholder="••••••••" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password_confirm" class="form-control" minlength="8" placeholder="••••••••" required>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Register</button>
        </form>

        <div class="text-center mt-3">
          <span class="small text-muted">Already have an account?</span>
          <a href="<?= $base ?>login" class="small">Sign in</a>
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
