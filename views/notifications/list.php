<?php /** @var array $notifications @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Notifications</h4>
  <a href="<?= $base ?>dashboard" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
    <?php if (empty($notifications)): ?>
      <div class="list-group-item text-muted">You have no notifications.</div>
    <?php else: foreach ($notifications as $n): ?>
      <div class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <div <?= $n['is_read'] ? 'class="text-muted"' : 'class="fw-semibold"' ?>><?= Security::e($n['message']) ?></div>
          <small class="text-muted"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></small>
        </div>
        <?php if ($n['link']): ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= $base . ltrim($n['link'], '/') ?>">Open</a>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
    </div>
  </div>
</div>
