<?php /** @var array $items @var string $base */
$cats = [];
$locsI = [];
foreach ($items as $it) {
    if (!empty($it['category_name'])) { $cats[$it['category_name']] = $it['category_name']; }
    if (!empty($it['location_name'])) { $locsI[$it['location_name']] = $it['location_name']; }
}
ksort($cats); ksort($locsI);
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 header-actions">
  <h4 class="mb-0">Items / Inventory</h4>
  <div class="d-flex gap-2 flex-wrap">
    <input type="search" class="form-control btn-nowrap" style="max-width:200px;" placeholder="Search items..." data-table-search="itemsTable" aria-label="Search">
    <a href="<?= $base ?>items/print" class="btn btn-sm btn-outline-secondary btn-nowrap"><i class="bi bi-upc-scan"></i> Print Labels</a>
    <a href="<?= $base ?>items/add" class="btn btn-sm btn-primary btn-nowrap"><i class="bi bi-plus-lg"></i> Add Item</a>
  </div>
</div>

<div class="card table-fill" data-filter-panel="#itemsTable">
  <div class="card-body p-0">
    <div class="filters-bar">
      <div class="filter-group">
        <label>Category</label>
        <select class="form-select form-select-sm" data-filter-key="category">
          <option value="">All</option>
          <?php foreach ($cats as $c): ?><option value="<?= Security::e($c) ?>"><?= Security::e($c) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Location</label>
        <select class="form-select form-select-sm" data-filter-key="location">
          <option value="">All</option>
          <?php foreach ($locsI as $l): ?><option value="<?= Security::e($l) ?>"><?= Security::e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary btn-nowrap" data-filter-reset>Reset</button>
    </div>
    <div class="table-responsive table-scroll">
    <table class="table table-hover align-middle mb-0" id="itemsTable">
      <thead><tr>
        <th>Barcode</th>
        <th data-sort="code">Code</th>
        <th data-sort="name">Name</th>
        <th data-sort="category">Category</th>
        <th data-sort="location">Location</th>
        <th data-sort="unit">Unit</th>
        <th data-sort="price" class="text-end">Price</th>
        <th data-sort="qty" class="text-center">Qty</th>
        <th class="text-center">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr data-category="<?= Security::e($it['category_name'] ?? '') ?>" data-location="<?= Security::e($it['location_name'] ?? '') ?>">
          <td><img src="<?= $base ?>items/barcode/<?= (int)$it['id'] ?>" alt="barcode" class="barcode-thumb"></td>
          <td><code><?= Security::e($it['item_code']) ?></code></td>
          <td>
            <a href="<?= $base ?>items/view/<?= (int)$it['id'] ?>" class="text-decoration-none fw-medium"><?= Security::e($it['name']) ?></a>
            <?php if ((int)$it['current_qty'] <= (int)$it['reorder_point'] && (int)$it['reorder_point'] > 0): ?> <span class="badge bg-danger">LOW</span><?php endif; ?>
          </td>
          <td><?= Security::e($it['category_name'] ?? '-') ?></td>
          <td><?= Security::e($it['location_name'] ?? '-') ?></td>
          <td><?= Security::e($it['unit'] ?? '-') ?></td>
          <td class="text-end">&#8369;<?= number_format((float)$it['price'], 2) ?></td>
          <td class="text-center"><?= (int)$it['current_qty'] ?></td>
          <td class="text-center">
            <div class="d-inline-flex flex-nowrap gap-1 align-items-center" style="white-space:nowrap;">
              <a class="btn btn-sm btn-outline-primary" href="<?= $base ?>items/view/<?= (int)$it['id'] ?>" title="view details"><i class="bi bi-eye"></i></a>
              <a class="btn btn-sm btn-outline-secondary" href="<?= $base ?>items/barcode/<?= (int)$it['id'] ?>" target="_blank" title="open barcode"><i class="bi bi-upc-scan"></i></a>
              <a class="btn btn-sm btn-outline-secondary" href="<?= $base ?>items/edit/<?= (int)$it['id'] ?>" title="edit"><i class="bi bi-pencil"></i></a>
              <?php if (isset($user['role_name']) && $user['role_name'] === 'admin'): ?>
                <form class="d-inline" method="post" action="<?= $base ?>items/archive/<?= (int)$it['id'] ?>" onsubmit="return confirm('Archive this item? It will be hidden from inventory.');">
                  <?= Security::csrfField() ?>
                  <button class="btn btn-sm btn-outline-warning" title="archive"><i class="bi bi-archive"></i></button>
                </form>
              <?php endif; ?>
              <form class="d-inline" method="post" action="<?= $base ?>items/delete/<?= (int)$it['id'] ?>" onsubmit="return confirm('Delete this item?');">
                <?= Security::csrfField() ?>
                <button class="btn btn-sm btn-outline-danger" title="delete"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
