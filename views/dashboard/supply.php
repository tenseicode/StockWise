<?php /** @var array $stats */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Supply Dashboard</h4>
  <span class="badge bg-warning text-dark">Supply Personnel</span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Total Items</div><div class="stat-value"><?= (int)$stats['totalItems'] ?></div>
  </div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Low Stock</div><div class="stat-value text-danger"><?= (int)$stats['lowStockCount'] ?></div>
  </div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Delegated to Me</div><div class="stat-value text-warning"><?= (int)($stats['delegatedCount'] ?? 0) ?></div>
  </div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Today's Transactions</div><div class="stat-value text-success"><?= (int)$stats['todayTx'] ?></div>
  </div></div></div>
</div>

<div class="card mb-4"><div class="card-header fw-semibold">Delegated Approvals (act on behalf of the Supply Administrator)</div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <thead><tr><th>Request#</th><th>Type</th><th>Office</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($stats['myPending'])): ?>
        <tr><td colspan="4" class="text-muted">Nothing was delegated to you.</td></tr>
      <?php else: foreach ($stats['myPending'] as $r): ?>
        <tr>
          <td><?= Security::e($r['request_number']) ?></td>
          <td><span class="badge bg-info text-dark"><?= Security::e($r['type']) ?></span></td>
          <td><?= Security::e($r['office_name']) ?></td>
          <td><a class="btn btn-sm btn-warning" href="<?= $base ?>approvals/view/<?= (int)$r['id'] ?>">Review &amp; Sign</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="d-flex gap-2">
  <a href="<?= $base ?>transactions/stock-in" class="btn btn-success"><i class="bi bi-arrow-down-circle"></i> Stock In</a>
  <a href="<?= $base ?>transactions/stock-out" class="btn btn-warning"><i class="bi bi-arrow-up-circle"></i> Stock Out</a>
  <a href="<?= $base ?>transactions" class="btn btn-outline-secondary">History</a>
  <a href="<?= $base ?>approvals" class="btn btn-outline-primary">Approvals Queue</a>
  <a href="<?= $base ?>requests" class="btn btn-outline-secondary">All Requests</a>
</div>