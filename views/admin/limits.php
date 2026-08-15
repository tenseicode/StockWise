<?php /** @var array $limits @var array $offices @var array $items @var int $year @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Office Consumption Limits</h4>
  <a href="<?= $base ?>dashboard" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card mb-4">
  <div class="card-body">
    <h6 class="fw-semibold">Add / Update Limit</h6>
    <form method="post" action="<?= $base ?>admin/limits/set" class="row g-2">
      <?= Security::csrfField() ?>
      <div class="col-md-3">
        <select name="office_id" class="form-select" required>
          <option value="">Office...</option>
          <?php foreach ($offices as $o): ?><option value="<?= (int)$o['id'] ?>"><?= Security::e($o['office_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <select name="item_id" class="form-select" required>
          <option value="">Item...</option>
          <?php foreach ($items as $i): ?><option value="<?= (int)$i['id'] ?>"><?= Security::e($i['item_code']) ?> - <?= Security::e($i['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><input type="number" name="year" class="form-control" value="<?= (int)$year ?>" min="2000" max="2099" required></div>
      <div class="col-md-2"><input type="number" name="max_qty" class="form-control" placeholder="Max Qty" min="1" required></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive table-scroll">
    <table class="table table-sm table-hover mb-0">
      <thead><tr><th data-sort="office">Office</th><th data-sort="item">Item</th><th data-sort="year">Year</th><th data-sort="max" class="text-end">Max Qty</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <?php if (empty($limits)): ?><tr><td colspan="5" class="text-muted">No limits configured.</td></tr>
      <?php else: foreach ($limits as $l): ?>
        <tr>
          <td><?= Security::e($l['office_name']) ?></td>
          <td><?= Security::e($l['item_code']) ?> - <?= Security::e($l['item_name']) ?></td>
          <td><?= (int)$l['year'] ?></td>
          <td><?= (int)$l['max_qty'] ?></td>
          <td class="text-end">
            <form class="d-inline" method="post" action="<?= $base ?>admin/limits/delete/<?= (int)$l['id'] ?>" onsubmit="return confirm('Remove this limit?');">
              <?= Security::csrfField() ?>
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
            </tbody>
    </table>
    </div>
  </div>
</div>

<?php if (!empty($usage)): ?>
<div class="card">
  <div class="card-body">
    <h6 class="fw-semibold mb-3">Consumption Report (<?= (int)($year ?? date('Y')) ?>)</h6>
    <div class="table-responsive table-scroll">
    <table class="table table-sm table-bordered align-middle mb-0">
      <thead class="table-light"><tr><th>Office</th><th>Item</th><th data-sort="year" class="text-center">Year</th><th data-sort="max" class="text-end">Max</th><th data-sort="used" class="text-end">Used</th><th data-sort="rem" class="text-end">Remaining</th><th>Progress</th></tr></thead>
      <tbody>
      <?php foreach ($usage as $u): ?>
        <?php $pct = (int)$u['max_qty'] > 0 ? min(100, (int)round(((int)$u['max_qty'] - (int)$u['remaining']) / (int)$u['max_qty'] * 100)) : 0; ?>
        <tr>
          <td><?= Security::e($u['office_name']) ?></td>
          <td><?= Security::e($u['item_code']) ?> - <?= Security::e($u['item_name']) ?></td>
          <td><?= (int)$u['year'] ?></td>
          <td class="text-end"><?= (int)$u['max_qty'] ?></td>
          <td class="text-end"><?= (int)($u['used_qty'] ?? 0) ?></td>
          <td class="text-end"><?= (int)($u['remaining'] ?? 0) ?></td>
          <td class="text-nowrap">
            <div class="progress" style="height:14px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
<?php endif; ?>
