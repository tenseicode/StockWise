<?php /** @var array $requests @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">My Requests</h4>
  <div class="d-flex gap-2">
    <input type="search" class="form-control" style="max-width:240px;" placeholder="Search requests..." data-table-search="myReqTable" aria-label="Search">
    <div class="dropdown">
      <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">New Request</button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?= $base ?>requests/new/RIS">RIS - Requisition &amp; Issue Slip</a></li>
        <li><a class="dropdown-item" href="<?= $base ?>requests/new/PPMP">PPMP - Annual Procurement Plan</a></li>
        <li><a class="dropdown-item" href="<?= $base ?>requests/new/PPE">PPE - Property / Equipment</a></li>
        <li><a class="dropdown-item" href="<?= $base ?>requests/new/ARE">ARE - Acknowledgement Receipt for Equipment</a></li>
        <li><a class="dropdown-item" href="<?= $base ?>requests/new/BS">BS - Borrower's Slip</a></li>
      </ul>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive table-scroll">
    <table class="table table-hover align-middle mb-0" id="myReqTable">
      <thead>
        <tr>
          <th data-sort="c0">Request#</th><th data-sort="c1">Type</th><th data-sort="c2">Status</th>
          <th data-sort="c3">Purpose</th><th data-sort="c4">Needed By</th><th data-sort="c5">Submitted</th><th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($requests)): ?>
        <tr><td colspan="7" class="text-muted text-center p-3">No requests yet.</td></tr>
      <?php else: foreach ($requests as $r): $st = Request::listStatus($r); ?>
        <tr>
          <td><?= Security::e($r['request_number']) ?></td>
          <td><span class="badge bg-secondary"><?= Security::e($r['type']) ?></span></td>
          <td><span class="badge bg-<?= Security::e($st['badge']) ?>"><?= Security::e($st['label']) ?></span></td>
          <td class="text-truncate" style="max-width:200px"><?= Security::e($r['purpose']) ?></td>
          <td><?= Workflow::fmt($r['needed_by']) ?></td>
          <td><?= Workflow::fmt($r['submitted_at']) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" title="View" href="<?= $base ?>requests/view/<?= (int)$r['id'] ?>"><i class="bi bi-eye"></i></a>
            <?php if (in_array($r['status'], ['draft', 'returned'], true)): ?>
              <a class="btn btn-sm btn-outline-secondary" title="Edit" href="<?= $base ?>requests/edit/<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
              <?php if ($r['status'] === 'returned'): ?>
                <a class="btn btn-sm btn-success" title="Resubmit (sign on next page)" href="<?= $base ?>requests/view/<?= (int)$r['id'] ?>"><i class="bi bi-send"></i></a>
              <?php else: ?>
                <a class="btn btn-sm btn-success" title="Submit (sign on next page)" href="<?= $base ?>requests/view/<?= (int)$r['id'] ?>"><i class="bi bi-send"></i></a>
              <?php endif; ?>
            <?php endif; ?>
            <?php if ($r['status'] === 'draft'): ?>
              <form class="d-inline" method="post" action="<?= $base ?>requests/delete/<?= (int)$r['id'] ?>" onsubmit="return confirm('Delete draft?');">
                <?= Security::csrfField() ?>
                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>