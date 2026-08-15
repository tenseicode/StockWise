<?php /** @var array $item @var array $transactions @var string $base @var string $from @var string $to @var string $type @var string $dailyLabels @var string $dailyIn @var string $dailyOut */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><i class="bi bi-box-seam"></i> Item Details</h4>
  <a href="<?= $base ?>items" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Inventory</a>
</div>

<!-- Item summary -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start gap-3">
          <div>
            <h5 class="mb-1"><?= Security::e($item['name']) ?>
              <?php if ((int)$item['current_qty'] <= (int)$item['reorder_point'] && (int)$item['reorder_point'] > 0): ?>
                <span class="badge bg-danger">LOW STOCK</span>
              <?php endif; ?>
            </h5>
            <div class="text-muted small"><code><?= Security::e($item['item_code']) ?></code></div>
            <div class="mt-3">
              <span class="badge bg-secondary"><?= Security::e($item['category_name'] ?? 'Uncategorized') ?></span>
              <span class="badge bg-info text-dark"><?= Security::e($item['location_name'] ?? 'No location') ?></span>
              <span class="badge bg-light text-dark border"><?= Security::e($item['unit'] ?? 'unit') ?></span>
            </div>
          </div>
          <img src="<?= $base ?>items/barcode/<?= (int)$item['id'] ?>" alt="barcode" class="barcode-lg ms-auto">
        </div>
        <hr>
        <div class="row text-center">
          <div class="col-3">
            <div class="stat-label">Unit Price</div>
            <div class="stat-value fs-6">&#8369;<?= number_format((float)$item['price'], 2) ?></div>
          </div>
          <div class="col-3">
            <div class="stat-label">Current Qty</div>
            <div class="stat-value fs-6"><?= (int)$item['current_qty'] ?></div>
          </div>
          <div class="col-3">
            <div class="stat-label">Stock Value</div>
            <div class="stat-value fs-6">&#8369;<?= number_format((float)$item['price'] * (int)$item['current_qty'], 2) ?></div>
          </div>
          <div class="col-3">
            <div class="stat-label">Reorder Point</div>
            <div class="stat-value fs-6"><?= (int)$item['reorder_point'] ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold"><i class="bi bi-graph-up"></i> Daily Stock Movement</div>
      <div class="card-body">
        <div class="chart-box" style="height:220px;">
          <canvas id="itemChart" data-labels='<?= htmlspecialchars($dailyLabels, ENT_QUOTES, 'UTF-8') ?>' data-in='<?= htmlspecialchars($dailyIn, ENT_QUOTES, 'UTF-8') ?>' data-out='<?= htmlspecialchars($dailyOut, ENT_QUOTES, 'UTF-8') ?>'></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filter + history -->
<div class="card">
  <div class="card-body">
    <form method="get" action="<?= $base ?>items/view/<?= (int)$item['id'] ?>" class="row g-2 align-items-end mb-3">
      <div class="col-auto">
        <label class="form-label small text-muted">From</label>
        <input type="date" name="from" class="form-control" value="<?= Security::e($from) ?>">
      </div>
      <div class="col-auto">
        <label class="form-label small text-muted">To</label>
        <input type="date" name="to" class="form-control" value="<?= Security::e($to) ?>">
      </div>
      <div class="col-auto">
        <label class="form-label small text-muted">Type</label>
        <select name="type" class="form-select">
          <option value="">All types</option>
          <?php foreach (['stock_in','stock_out','adjustment','transfer'] as $t): ?>
            <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= Security::e(ucwords(str_replace('_',' ',$t))) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
      </div>
      <div class="col-auto ms-auto">
        <input type="search" class="form-control" placeholder="Search records..." data-table-search="itemTxTable" aria-label="Search">
      </div>
    </form>

    <div class="table-responsive table-scroll">
      <table class="table table-sm table-hover align-middle mb-0" id="itemTxTable">
        <thead><tr>
          <th data-sort="type" style="width:110px;">Type</th>
          <th data-sort="qty" class="text-end" style="width:80px;">Qty</th>
          <th data-sort="ref" style="width:170px;">Reference</th>
          <th data-sort="loc" style="width:160px;">From - To</th>
          <th data-sort="by" style="width:180px;">By</th>
          <th data-sort="remarks">Remarks</th>
          <th data-sort="date" style="width:160px;">Date</th>
        </tr></thead>
        <tbody>
        <?php if (empty($transactions)): ?>
          <tr><td colspan="7" class="text-muted text-center">No stock movements in this range.</td></tr>
        <?php else: foreach ($transactions as $t): ?>
          <tr>
            <td>
              <?php $badge = ['stock_in'=>'success','stock_out'=>'danger','adjustment'=>'secondary','transfer'=>'info'][$t['type']] ?? 'secondary'; ?>
              <span class="badge bg-<?= $badge ?>"><?= Security::e(strtoupper(str_replace('_',' ',$t['type']))) ?></span>
            </td>
            <td class="text-end"><?= (int)$t['quantity'] ?></td>
            <td class="text-nowrap"><?= Security::e($t['reference'] ?? '-') ?></td>
            <td class="text-nowrap"><?= Security::e($t['from_loc'] ?? '') ?><span class="text-muted mx-1">-</span><?= Security::e($t['to_loc'] ?? '') ?></td>
            <td><?= Security::e($t['user_name'] ?? '-') ?></td>
            <td class="text-truncate" style="max-width:220px;"><?= Security::e($t['remarks'] ?? '') ?></td>
            <td class="text-nowrap"><?= date('Y-m-d H:i', strtotime($t['created_at'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function(){
  function initItemChart(){
    var ctx = document.getElementById('itemChart');
    if(!ctx) return;
    var labels = JSON.parse(ctx.dataset.labels || '[]');
    var inArr  = JSON.parse(ctx.dataset.in  || '[]');
    var outArr = JSON.parse(ctx.dataset.out || '[]');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          { label: 'Stock In',  data: inArr,  borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.1)',  fill: true, tension: 0.3, pointRadius: 3 },
          { label: 'Stock Out', data: outArr, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.1)',  fill: true, tension: 0.3, pointRadius: 3 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, padding: 8 } } },
        scales: {
          x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 10 } } },
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }
  window.addEventListener('load', function(){
    if(window.Chart){ initItemChart(); }
  });
})();
</script>