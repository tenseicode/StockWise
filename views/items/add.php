<?php /** @var array $categories @var array $locations @var string $nextCode @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Add New Item</h4>
  <a href="<?= $base ?>items" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $base ?>items/add">
      <?= Security::csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Item Code</label>
          <input type="text" name="item_code" class="form-control" value="<?= Security::e($nextCode) ?>" readonly>
          <div class="form-text">Auto-generated as TCGC-ITEM-XXXX and used for the barcode.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-select">
            <option value="">-- none --</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= Security::e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Location</label>
          <select name="location_id" class="form-select">
            <option value="">-- none --</option>
            <?php foreach ($locations as $l): ?><option value="<?= (int)$l['id'] ?>"><?= Security::e($l['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Unit</label>
          <input type="text" name="unit" class="form-control" placeholder="piece, ream, box...">
        </div>
        <div class="col-md-4">
          <label class="form-label">Price (PHP)</label>
          <input type="number" step="0.01" min="0" name="price" class="form-control" value="0">
        </div>
        <div class="col-md-4">
          <label class="form-label">Reorder Point</label>
          <input type="number" min="0" name="reorder_point" class="form-control" value="0">
        </div>
        <div class="col-md-4">
          <label class="form-label">Opening / Current Qty</label>
          <input type="number" min="0" name="current_qty" class="form-control" value="0">
        </div>
      </div>
      <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Item</button>
      </div>
    </form>
  </div>
</div>
