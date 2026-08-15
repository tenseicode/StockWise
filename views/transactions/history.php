<?php
/** @var array $transactions @var string $base */
// Build filter option lists from the loaded rows.
$types = ['stock_in' => 'Stock In', 'stock_out' => 'Stock Out', 'adjustment' => 'Adjustment', 'transfer' => 'Transfer'];
$locs = [];
$roles = [];
foreach ($transactions as $t) {
    foreach ([$t['from_loc'] ?? '', $t['to_loc'] ?? ''] as $l) {
        if ($l !== '') { $locs[$l] = $l; }
    }
    if (!empty($t['role_name'])) { $roles[$t['role_name']] = ucwords(str_replace('_', ' ', $t['role_name'])); }
}
ksort($locs); ksort($roles);
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 header-actions">
  <h4 class="mb-0">Transaction History</h4>
  <div class="d-flex gap-2 flex-wrap">
    <input type="search" class="form-control btn-nowrap" style="max-width:200px;" placeholder="Search..." data-table-search="txTable" aria-label="Search">
    <a href="<?= $base ?>transactions/stock-in" class="btn btn-sm btn-success btn-nowrap">Stock In</a>
    <a href="<?= $base ?>transactions/stock-out" class="btn btn-sm btn-warning btn-nowrap">Stock Out</a>
    <a href="<?= $base ?>transactions/adjust" class="btn btn-sm btn-secondary btn-nowrap">Adjust</a>
    <a href="<?= $base ?>transactions/transfer" class="btn btn-sm btn-info btn-nowrap">Transfer</a>
  </div>
</div>

<div class="card table-fill" data-filter-panel="#txTable">
  <div class="card-body p-0">
    <div class="filters-bar">
      <div class="filter-group">
        <label>Type</label>
        <select class="form-select form-select-sm" data-filter-key="type">
          <option value="">All</option>
          <?php foreach ($types as $v => $lbl): ?><option value="<?= $v ?>"><?= $lbl ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Location</label>
        <select class="form-select form-select-sm" data-filter-key="loc" data-filter-mode="contains">
          <option value="">All</option>
          <?php foreach ($locs as $l): ?><option value="<?= Security::e($l) ?>"><?= Security::e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Done by</label>
        <select class="form-select form-select-sm" data-filter-key="role">
          <option value="">All</option>
          <?php foreach ($roles as $v => $lbl): ?><option value="<?= $v ?>"><?= Security::e($lbl) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>From</label>
        <input type="date" class="form-control form-control-sm" data-filter-key="date-from" aria-label="From date">
        <label>To</label>
        <input type="date" class="form-control form-control-sm" data-filter-key="date-to" aria-label="To date">
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary btn-nowrap" data-filter-reset>Reset</button>
    </div>
    <div class="table-responsive table-scroll">
    <table class="table table-sm align-middle mb-0" id="txTable">
      <thead><tr><th data-sort="id">#</th><th data-sort="type">Type</th><th data-sort="item">Item</th><th data-sort="qty" class="text-end">Qty</th><th data-sort="ref">Reference</th><th data-sort="loc">From - To</th><th data-sort="by">By</th><th data-sort="remarks">Remarks</th><th data-sort="date">Date</th></tr></thead>
      <tbody>
      <?php if (empty($transactions)): ?>
        <tr><td colspan="9" class="text-muted text-center">No transactions recorded.</td></tr>
      <?php else: foreach ($transactions as $t): ?>
        <tr data-type="<?= Security::e($t['type']) ?>" data-loc="<?= Security::e(trim(($t['from_loc'] ?? '') . ' ' . ($t['to_loc'] ?? ''))) ?>" data-role="<?= Security::e($t['role_name'] ?? '') ?>" data-date="<?= date('Y-m-d', strtotime($t['created_at'])) ?>">
          <td><?= (int)$t['id'] ?></td>
          <td>
            <?php $badge = ['stock_in'=>'success','stock_out'=>'danger','adjustment'=>'secondary','transfer'=>'info'][$t['type']] ?? 'secondary'; ?>
            <span class="badge bg-<?= $badge ?>"><?= Security::e(str_replace('_',' ',strtoupper($t['type']))) ?></span>
          </td>
          <td><code><?= Security::e($t['item_code']) ?></code> <?= Security::e($t['item_name']) ?></td>
          <td class="text-end"><?= (int)$t['quantity'] ?></td>
          <td class="text-nowrap"><?= Security::e($t['reference'] ?? '-') ?></td>
          <td class="text-nowrap">
            <?= Security::e($t['from_loc'] ?? '') ?><span class="text-muted mx-1">-</span><?= Security::e($t['to_loc'] ?? '') ?>
          </td>
          <td><?= Security::e($t['user_name'] ?? '-') ?></td>
          <td><?= Security::e($t['remarks']) ?></td>
          <td class="text-nowrap"><?= date('Y-m-d H:i', strtotime($t['created_at'])) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

