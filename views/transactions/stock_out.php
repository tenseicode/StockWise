<?php /** @var array $items @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Stock Out</h4>
  <a href="<?= $base ?>transactions" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> History</a>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header fw-semibold"><i class="bi bi-upc-scan"></i> Scan Barcode (Webcam)</div>
      <div class="card-body">
        <button id="scanBtn" class="btn btn-outline-primary w-100"><i class="bi bi-camera"></i> Start Camera Scan</button>
        <div id="qrScanner" class="mt-3 d-none"></div>
        <div id="scanResult" class="alert alert-success mt-2 d-none mb-0"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?= $base ?>transactions/stock-out">
          <?= Security::csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Item</label>
            <select name="item_id" id="itemSelect" class="form-select" required>
              <option value="">-- select / scan item --</option>
              <?php foreach ($items as $it): ?>
                <option value="<?= (int)$it['id'] ?>" data-code="<?= Security::e($it['item_code']) ?>">
                                    <?= Security::e($it['item_code']) ?> - <?= Security::e($it['name']) ?> (qty: <?= (int)$it['current_qty'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" min="1" required></div>
          <div class="mb-3"><label class="form-label">Remarks (e.g. requestor / purpose)</label><input type="text" name="remarks" class="form-control"></div>
          <button class="btn btn-warning"><i class="bi bi-arrow-up-circle"></i> Record Stock Out</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
var STOCKWISE_ITEMS = <?= htmlspecialchars(json_encode(array_map(fn($i) => ['code' => $i['item_code'], 'id' => (int)$i['id'], 'name' => $i['name']], $items)), ENT_QUOTES, 'UTF-8') ?>;
</script>
