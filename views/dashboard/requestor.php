<?php /** @var array $stats */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Requestor Dashboard</h4>
  <span class="badge bg-info text-dark">Requestor</span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">My Requests</div><div class="stat-value"><?= count((array)($stats['myRequests'] ?? [])) ?></div>
  </div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body">
    <div class="stat-label">Drafts</div>
    <div class="stat-value"><?= count(array_filter((array)($stats['myRequests'] ?? []), fn($r) => $r['status'] === 'draft')) ?></div>
  </div></div></div>
  <div class="col-12 col-lg-6">
    <div class="card stat-card h-100"><div class="card-body">
      <div class="stat-label mb-2">Quick Submit</div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary btn-sm" href="<?= $base ?>requests/new/RIS">New RIS</a>
        <a class="btn btn-primary btn-sm" href="<?= $base ?>requests/new/PPMP">New PPMP</a>
        <a class="btn btn-primary btn-sm" href="<?= $base ?>requests/new/PPE">New PPE</a>
        <a class="btn btn-primary btn-sm" href="<?= $base ?>requests/new/ARE">New ARE</a>
        <a class="btn btn-primary btn-sm" href="<?= $base ?>requests/new/BS">New BS</a>
      </div>
    </div></div>
  </div>
</div>

<div class="card"><div class="card-header fw-semibold">My Requests</div>
  <div class="card-body p-0">
    <?php if (empty($stats['myRequests'])): ?>
      <div class="p-3 text-muted">You have no requests yet.</div>
    <?php else: ?>
    <table class="table table-sm mb-0">
      <thead><tr><th>#</th><th>Type</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($stats['myRequests'] as $r): ?>
        <tr>
          <td><?= Security::e($r['request_number']) ?></td>
          <td><span class="badge bg-secondary"><?= Security::e($r['type']) ?></span></td>
          <td><?= Security::e(ucwords(str_replace('_', ' ', $r['status']))) ?></td>
          <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
          <td><a class="btn btn-sm btn-outline-primary" href="<?= $base ?>requests/view/<?= (int)$r['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
