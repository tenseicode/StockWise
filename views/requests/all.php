<?php
/**
 * All Requests - search & filter page for admin, supply personnel and approvers.
 * @var array $requests @var array $requestors @var array $offices @var array $filters @var string $base
 */
$filters = $filters ?? [];
$fv = fn($k) => Security::e((string)($filters[$k] ?? ''));
$statusOptions = [
    'in_review'                   => 'Submitted / In Review (all)',
    'pending_supply_admin'        => 'Pending Supply Administrator',
    'pending_budget_head'         => 'Pending Budget Head',
    'pending_procurement_head'    => 'Pending Procurement Head',
    'pending_vp'                  => 'Pending VP',
    'returned'                    => 'Returned to Requester',
    'approved'                    => 'Approved / Done',
    'draft'                       => 'Draft',
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">All Requests</h4>
  <a href="<?= $base ?>dashboard" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" action="<?= $base ?>requests" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small text-muted">Search</label>
        <input type="search" name="q" class="form-control" value="<?= $fv('q') ?>" placeholder="Request #, purpose, requester...">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Status</label>
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <?php foreach ($statusOptions as $val => $label): ?>
            <option value="<?= Security::e($val) ?>" <?= ($filters['status'] ?? '') === $val ? 'selected' : '' ?>><?= Security::e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Form Type</label>
        <select name="type" class="form-select">
          <option value="">All</option>
          <?php foreach (['RIS','PPMP','PPE','ARE','BS'] as $t): ?>
            <option value="<?= $t ?>" <?= strtoupper((string)($filters['type'] ?? '')) === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Office / Department</label>
        <select name="office_id" class="form-select">
          <option value="">All</option>
          <?php foreach ($offices as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= ($filters['office_id'] ?? '') == $o['id'] ? 'selected' : '' ?>><?= Security::e($o['office_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Requester</label>
        <select name="requestor_id" class="form-select">
          <option value="">All</option>
          <?php foreach ($requestors as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ($filters['requestor_id'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= Security::e($u['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Date From</label>
        <input type="date" name="date_from" class="form-control" value="<?= $fv('date_from') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Date To</label>
        <input type="date" name="date_to" class="form-control" value="<?= $fv('date_to') ?>">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
        <a href="<?= $base ?>requests" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive table-scroll">
    <table class="table table-hover align-middle mb-0" id="allReqTable">
      <thead>
        <tr>
          <th data-sort="c0">Request#</th><th data-sort="c1">Type</th><th data-sort="c2">Office</th>
          <th data-sort="c3">Requester</th><th data-sort="c4">Status</th>
          <th data-sort="c5">Needed By</th><th data-sort="c6">Submitted</th><th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($requests)): ?>
        <tr><td colspan="8" class="text-muted text-center p-3">No requests match your filters.</td></tr>
      <?php else: foreach ($requests as $r): $st = Request::listStatus($r); ?>
        <tr>
          <td><?= Security::e($r['request_number']) ?></td>
          <td><span class="badge bg-secondary"><?= Security::e($r['type']) ?></span></td>
          <td><?= Security::e($r['office_name']) ?></td>
          <td><?= Security::e($r['requestor_name']) ?></td>
          <td><span class="badge bg-<?= Security::e($st['badge']) ?>"><?= Security::e($st['label']) ?></span></td>
          <td><?= Workflow::fmt($r['needed_by']) ?></td>
          <td><?= Workflow::fmt($r['submitted_at']) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" title="View" href="<?= $base ?>requests/view/<?= (int)$r['id'] ?>"><i class="bi bi-eye"></i></a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>