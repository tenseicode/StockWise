<?php
/**
 * Approval queue panel.
 * @var string $mode @var string $title @var array $requests @var array $columns @var string $base
 */
$title = $title ?? 'Approvals';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><?= Security::e($title) ?></h4>
  <div class="d-flex gap-2 align-items-center">
    <input type="search" class="form-control" style="max-width:240px;" placeholder="Search queue..." data-table-search="approvalTable" aria-label="Search">
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (empty($requests)): ?>
      <div class="p-4 text-center text-muted"><i class="bi bi-check-circle"></i> No requests in this queue.</div>
    <?php else: ?>
    <div class="table-responsive table-scroll">
    <table class="table table-hover align-middle mb-0" id="approvalTable">
      <thead><tr>
        <?php foreach ($columns as $i => $c): ?>
          <th<?= $i < 5 ? ' data-sort="c'.$i.'"' : '' ?>><?= Security::e($c) ?></th>
        <?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($requests as $r): $cnt = count(Request::items((int)$r['id'])); ?>
        <tr>
          <td><?= Security::e($r['request_number']) ?></td>
          <td><span class="badge bg-secondary"><?= Security::e($r['type']) ?></span></td>
          <td><?= Security::e($r['office_name']) ?></td>
          <td><?= Security::e($r['requestor_name']) ?></td>
          <td><?= $cnt ?> item(s)</td>
          <td>
            <?php if (!empty($r['is_delegated'])): ?>
              <span class="badge bg-warning text-dark"><i class="bi bi-person-workspace"></i> Delegated to Supply</span>
            <?php else: ?>
              <span class="badge bg-primary">Your check</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-primary" href="<?= $base ?>approvals/view/<?= (int)$r['id'] ?>">Review</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>