<?php
/** @var array $user @var int $unread @var array $notifications @var string $base @var string $appName */
$appName = $appName ?? 'StockWise';
$role = $user['role_name'];
$roleLabel = ucwords(str_replace('_', ' ', $role));
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Security::e($appName) ?> - <?= Security::e($roleLabel) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= $base ?>assets/css/style.css?v=<?= filemtime(BASE_PATH . 'public/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="app-body">
<!-- Non-layout-shifting toast notifications (rendered once, floats above content) -->
<div id="swToastStack" class="sw-toast-stack" style="position:fixed; top:1rem; right:1rem; z-index:2050;"></div>
<?php if (!empty($flash)): ?>
<script>
window.addEventListener('DOMContentLoaded', function () {
  var stack = document.getElementById('swToastStack');
  var el = document.createElement('div');
  el.className = 'toast align-items-center text-bg-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'danger' ? 'danger' : 'warning') ?> border-0';
  el.setAttribute('role', 'alert');
    el.innerHTML = '<div class="d-flex"><div class="toast-body">' + <?= json_encode((string)$flash['message']) ?> + '</div><button type="button" class="btn-close btn-close-white m-2" data-bs-dismiss="toast"></button></div>';
  stack.appendChild(el);
    new bootstrap.Toast(el, {delay: 4000, autostart: true}).show();
});
</script>
<?php endif; ?>


<nav class="navbar navbar-dark navbar-expand-lg topbar">
  <div class="container-fluid">
        <a class="navbar-brand fw-semibold d-flex align-items-center" href="<?= $base ?>dashboard">
      <img src="<?= $base ?>assets/images/stockwise-logo.png" alt="StockWise logo" class="me-2">
      <?= Security::e($appName) ?>
    </a>
    <button id="swSidebarToggle" type="button" class="btn btn-sm btn-outline-light d-none d-lg-inline-flex align-items-center me-2" title="Toggle sidebar" aria-label="Toggle sidebar">
      <i class="bi bi-layout-sidebar"></i>
    </button>


    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <!-- Notifications bell -->
        <li class="nav-item dropdown me-2">
          <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-bell fs-5"></i>
            <?php if ($unread > 0): ?>
              <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"><?= (int)$unread ?></span>
            <?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-end notif-menu shadow">
            <h6 class="dropdown-header">Notifications</h6>
            <?php if (empty($notifications)): ?>
              <span class="dropdown-item text-muted small">No notifications yet.</span>
            <?php else: ?>
              <?php foreach ($notifications as $n): ?>
                <a class="dropdown-item small text-wrap<?= $n['is_read'] ? ' text-muted' : ' fw-semibold' ?>" href="<?= $base . ($n['link'] ? ltrim($n['link'], '/') : 'notifications') ?>">
                  <?= Security::e($n['message']) ?>
                </a>
              <?php endforeach; ?>
              <a class="dropdown-item text-center small" href="<?= $base ?>notifications">View all</a>
            <?php endif; ?>
          </div>
        </li>
        <li class="nav-item me-3 d-none d-xl-inline">
          <span class="navbar-text small" title="<?= Security::e(date_default_timezone_get()) ?> timezone">
            <i class="bi bi-clock"></i>             <span id="appClock" data-server="<?= (int)time() ?>"><?= Security::e(date('D, M j, Y g:i A')) ?></span>
            <span class="text-white-50"><?= Security::e(date_default_timezone_get()) ?></span>
          </span>
        </li>        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Logout confirmation modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header">
        <h6 class="modal-title mb-0"><i class="bi bi-box-arrow-right"></i> Confirm Sign Out</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body small text-muted">
        You are about to sign out. Any unsaved changes will be lost.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="<?= $base ?>logout" class="btn btn-sm btn-primary"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid">
  <div class="row">
    <div class="col-lg-2 col-md-3 sidebar-col p-0">
