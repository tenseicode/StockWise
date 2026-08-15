<?php /** @var array $stats */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">VP Finance Dashboard</h4>
  <span class="badge bg-danger">VP Finance</span>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-4">
    <div class="card"><div class="card-header fw-semibold">Inventory Value by Category</div>
      <div class="card-body">
        <div class="chart-box"><canvas id="valueByCatChart" data-values='<?= htmlspecialchars(json_encode($stats['valueByCategory'] ?? []), ENT_QUOTES, 'UTF-8') ?>'></canvas></div>
        <div class="text-center text-muted small mt-2">Total: &#8369;<?= number_format((float)($stats['totalValue'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card"><div class="card-header fw-semibold">Pending VP Approval (<?= count((array)($stats['myPending'] ?? [])) ?>)</div>
      <div class="card-body p-0">
        <?php if (empty($stats['myPending'])): ?>
          <div class="p-3 text-muted">No requests awaiting your approval.</div>
        <?php else: ?>
        <table class="table table-sm mb-0">
          <thead><tr><th>Request#</th><th>Type</th><th>Office</th><th>Requestor</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($stats['myPending'] as $r): ?>
            <tr>
              <td><?= Security::e($r['request_number']) ?></td>
              <td><span class="badge bg-secondary"><?= Security::e($r['type']) ?></span></td>
              <td><?= Security::e($r['office_name']) ?></td>
              <td><?= Security::e($r['requestor_name']) ?></td>
              <td><a class="btn btn-sm btn-danger" href="<?= $base ?>approvals/view/<?= (int)$r['id'] ?>">Review &amp; Sign</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<a href="<?= $base ?>approvals" class="btn btn-outline-danger">Open Approval Panel</a>
