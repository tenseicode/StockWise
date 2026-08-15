<?php
/** @var string $base @var string $mode @var array $offices @var string|null $error */
$error = $error ?? null;
$offices = $offices ?? [];
$mode = ($mode ?? 'login') === 'register' ? 'register' : 'login';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$img = $base . 'assets/images/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $mode === 'register' ? 'Register' : 'Login' ?> - StockWise</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= $base ?>assets/css/style.css?v=<?= filemtime(BASE_PATH . 'public/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="auth-split<?= $mode === 'register' ? ' register-mode' : '' ?>" id="authSplit">

    <!-- ============ LEFT PANEL ============ -->
    <div class="auth-panel auth-left">

      <!-- LOGIN (visible in login mode) -->
      <div class="panel-content auth-login-panel">
        <div class="auth-form-inner">
          <div class="auth-brand">
            <img class="auth-logo" src="<?= $img ?>stockwise-logo-transparentbg.png" alt="StockWise logo">
            <span>StockWise</span>
          </div>
          <h1 class="h3 fw-bold mb-1">Welcome back</h1>
          <p class="text-muted mb-4">Sign in to manage inventory &amp; procurement.</p>

          <?php if ($flash): ?><div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'warning' ?>"><?= Security::e($flash['message']) ?></div><?php endif; ?>
          <?php if ($error && $mode === 'login'): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

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

          <div class="d-flex justify-content-between align-items-center mt-3">
            <a href="<?= $base ?>forgot" class="small">Forgot password?</a>
            <a href="#" class="auth-switch" data-switch="register">Create account</a>
          </div>
        </div>
      </div>

      <!-- CAMPUS BRANDING (visible in register mode) -->
      <div class="panel-content auth-brand-panel">
        <div class="auth-brand"><img class="tcgc-logo" src="<?= $img ?>tcgc-logo.jpg" alt="TCGC logo"></div>
        <h2>Tangub City Global College</h2>
        <p>Empowering responsible stewardship of school resources with a modern, transparent inventory and procurement management system.</p>
      </div>
    </div>
    <!-- /auth-left -->

    <!-- ============ RIGHT PANEL ============ -->
    <div class="auth-panel auth-right">

      <!-- CAMPUS BRANDING (visible in login mode) -->
      <div class="panel-content auth-brand-panel">
        <div class="auth-brand"><img class="tcgc-logo" src="<?= $img ?>tcgc-logo.jpg" alt="TCGC logo"></div>
        <h2>Tangub City Global College</h2>
        <p>Empowering responsible stewardship of school resources with a modern, transparent inventory and procurement management system.</p>
      </div>

      <!-- REGISTER (visible in register mode) -->
      <div class="panel-content auth-register-panel">
        <div class="auth-form-inner">
          <div class="auth-brand">
            <img class="auth-logo" src="<?= $img ?>stockwise-logo-transparentbg.png" alt="StockWise logo">
            <span>StockWise</span>
          </div>
          <h1 class="h3 fw-bold mb-1">Create an Account</h1>
          <p class="text-muted mb-4">Register to create &amp; track procurement requests.</p>

          <?php if ($error && $mode === 'register'): ?><div class="alert alert-danger"><?= Security::e($error) ?></div><?php endif; ?>

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
              <select name="office_id" class="form-select" required>
                <option value="">-- select your office --</option>
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
            <a href="#" class="auth-switch" data-switch="login">Sign in</a>
          </div>
        </div>
      </div>
    </div>
    <!-- /auth-right -->
  </div>
<script src="<?= $base ?>assets/js/app.js?v=<?= filemtime(BASE_PATH . 'public/assets/js/app.js') ?>"></script>
</body>
</html>