<?php /** @var array $stats */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Budget Head Dashboard</h4>
  <span class="badge bg-success">Budget Head</span>
</div>

<div class="card mb-4"><div class="card-header fw-semibold">Pending Budget Approval (<?= count((array)($stats['myPending'] ?? [])) ?>)</div>
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
          <td><a class="btn btn-sm btn-success" href="<?= $base ?>approvals/view/<?= (int)$r['id'] ?>">Review &amp; Sign</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<a href="<?= $base ?>approvals" class="btn btn-outline-success">Open Approval Panel</a>
