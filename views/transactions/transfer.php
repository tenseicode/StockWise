<?php /** @var array $items @var array $locations @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><i class="bi bi-truck"></i> Stock Transfer</h4>
  <a href="<?= $base ?>transactions" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body">
        <form method="post" action="<?= $base ?>transactions/transfer">
      <?= Security::csrfField() ?>
      <div class="mb-3">
        <label class="form-label">Item</label>
        <select name="item_id" class="form-select" required>
          <option value="">- Select item -</option>
          <?php foreach ($items as $it): ?>
            <option value="<?= (int)$it['id'] ?>"><?= Security::e($it['item_code']) ?> - <?= Security::e($it['name']) ?> (qty: <?= (int)$it['current_qty'] ?>, located at <?= Security::e($it['location_name'] ?? '-') ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Destination Location</label>
        <select name="to_location_id" class="form-select" required>
          <option value="">- Select destination -</option>
          <?php foreach ($locations as $l): ?>
            <option value="<?= (int)$l['id'] ?>"><?= Security::e($l['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" class="form-control" min="1" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" rows="2" maxlength="200" placeholder="Optional note for the transfer."></textarea>
      </div>
      <button class="btn btn-primary"><i class="bi bi-truck"></i> Transfer Stock</button>
    </form>
  </div>
</div>
