<?php /** @var array $items @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><i class="bi bi-journal-plus"></i> Stock Adjustment</h4>
  <a href="<?= $base ?>transactions" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body">
        <form method="post" action="<?= $base ?>transactions/adjust">
      <?= Security::csrfField() ?>
      <div class="mb-3">
        <label class="form-label">Item</label>
        <select name="item_id" class="form-select" required>
          <option value="">- Select item -</option>
          <?php foreach ($items as $it): ?>
            <option value="<?= (int)$it['id'] ?>"><?= Security::e($it['item_code']) ?> - <?= Security::e($it['name']) ?> (<?= (int)$it['current_qty'] ?> available)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" class="form-control" required>
        <div class="form-text">Use a negative number to subtract stock, positive to add.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Reason <span class="text-danger">*</span></label>
        <input type="text" name="reason" class="form-control" maxlength="200" required>
        <div class="form-text">Required. e.g. "Damaged goods", "Cycle count", "Correction".</div>
      </div>
      <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Record Adjustment</button>
    </form>
  </div>
</div>
