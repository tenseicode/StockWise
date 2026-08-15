<?php /** @var array $item @var array $categories @var array $locations @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Edit Item</h4>
  <a href="<?= $base ?>items" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $base ?>items/edit/<?= (int)$item['id'] ?>">
      <?= Security::csrfField() ?>
      <div class="text-center mb-3">
        <img src="<?= $base ?>items/barcode/<?= (int)$item['id'] ?>" alt="barcode" class="barcode-lg">
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Item Code</label>
          <input type="text" name="item_code" class="form-control" value="<?= Security::e($item['item_code']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" value="<?= Security::e($item['name']) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-select">
            <option value="">-- none --</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === (int)$item['category_id'] ? 'selected' : '' ?>><?= Security::e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Location</label>
          <select name="location_id" class="form-select">
            <option value="">-- none --</option>
            <?php foreach ($locations as $l): ?>
              <option value="<?= (int)$l['id'] ?>" <?= (int)$l['id'] === (int)$item['location_id'] ? 'selected' : '' ?>><?= Security::e($l['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Unit</label>
          <input type="text" name="unit" class="form-control" value="<?= Security::e($item['unit']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Price (PHP)</label>
          <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= Security::e($item['price']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Reorder Point</label>
          <input type="number" min="0" name="reorder_point" class="form-control" value="<?= (int)$item['reorder_point'] ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Current Qty</label>
          <input type="number" min="0" name="current_qty" class="form-control" value="<?= (int)$item['current_qty'] ?>">
        </div>
      </div>
      <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Item</button>
        <a href="<?= $base ?>transactions/stock-in" class="btn btn-outline-success">Record Stock In instead</a>
      </div>
    </form>
  </div>
</div>
