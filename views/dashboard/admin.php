<?php
/** @var array $stats */
$stats = $stats ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Admin Dashboard</h4>
  <span class="badge bg-primary">System Administrator</span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Total Items</div><div class="stat-value"><?= (int)$stats['totalItems'] ?></div>
  </div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Low Stock</div><div class="stat-value text-danger"><?= (int)$stats['lowStockCount'] ?></div>
  </div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Pending Requests</div><div class="stat-value text-warning"><?= (int)$stats['pendingRequests'] ?></div>
  </div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Today's Transactions</div><div class="stat-value text-success"><?= (int)$stats['todayTx'] ?></div>
  </div></div></div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card"><div class="card-header fw-semibold">Low Stock Items</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Item</th><th>Qty</th><th>Threshold</th></tr></thead>
          <tbody>
          <?php if (empty($stats['lowStock'])): ?>
            <tr><td colspan="3" class="text-muted">No low stock items.</td></tr>
          <?php else: foreach ($stats['lowStock'] as $ls): ?>
            <tr>
              <td><?= Security::e($ls['name']) ?></td>
              <td><span class="badge bg-danger"><?= (int)$ls['current_qty'] ?></span></td>
              <td><?= (int)$ls['reorder_point'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-header fw-semibold">Recent Items</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Code</th><th>Name</th><th>Qty</th><th>Value</th></tr></thead>
          <tbody>
          <?php foreach ((array)($stats['recentItems'] ?? []) as $it): ?>
            <tr>
              <td><code><?= Security::e($it['item_code']) ?></code></td>
              <td><?= Security::e($it['name']) ?></td>
              <td><?= (int)$it['current_qty'] ?></td>
              <td>&#8369;<?= number_format($it['price'] * $it['current_qty'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="mt-4 d-flex gap-2">
  <a href="<?= $base ?>reports" class="btn btn-outline-primary"><i class="bi bi-bar-chart"></i> Reports</a>
  <a href="<?= $base ?>admin/users" class="btn btn-outline-secondary"><i class="bi bi-people"></i> Manage Users</a>
  <a href="<?= $base ?>admin/limits" class="btn btn-outline-secondary"><i class="bi bi-sliders"></i> Office Limits</a>
</div>
