<?php /** @var array $items @var array $low @var float $value @var array $valueByCat @var array $movement @var array $recent @var string $base @var string $from @var string $to @var string $dailyLabels @var string $dailyIn @var string $dailyOut @var array $topMovers */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><i class="bi bi-bar-chart-line"></i> Reports & Analytics</h4>
  <a href="<?= $base ?>reports/export" class="btn btn-outline-success"><i class="bi bi-download"></i> Export CSV</a>
</div>

<!-- Date filter -->
<form method="get" action="<?= $base ?>reports" class="card mb-4">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-auto">
        <label class="form-label small text-muted">From</label>
        <input type="date" name="from" class="form-control" value="<?= Security::e($from) ?>">
      </div>
      <div class="col-auto">
        <label class="form-label small text-muted">To</label>
        <input type="date" name="to" class="form-control" value="<?= Security::e($to) ?>">
      </div>
      <div class="col-auto">
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
      </div>
      <div class="col-auto">
        <a href="<?= $base ?>reports" class="btn btn-outline-secondary">Reset</a>
      </div>
    </div>
  </div>
</form>

<!-- KPI cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card stat-card border-start border-4 border-primary">
      <div class="card-body">
        <div class="stat-label"><i class="bi bi-box-seam"></i> Total Items</div>
        <div class="stat-value"><?= count($items) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card border-start border-4 border-success">
      <div class="card-body">
        <div class="stat-label"><i class="bi bi-currency-dollar"></i> Inventory Value</div>
        <div class="stat-value text-success">&#8369;<?= number_format($value, 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card border-start border-4 border-danger">
      <div class="card-body">
        <div class="stat-label"><i class="bi bi-exclamation-triangle"></i> Low Stock</div>
        <div class="stat-value text-danger"><?= count($low) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card border-start border-4 border-info">
      <div class="card-body">
        <div class="stat-label"><i class="bi bi-arrow-left-right"></i> Stock In / Out</div>
        <div class="stat-value"><?= (int)$movement['stock_in'] ?> <span class="text-muted">/</span> <?= (int)$movement['stock_out'] ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Charts row -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-graph-up"></i> Daily Stock Movement</span>
        <span class="badge bg-success">Stock In</span>
        <span class="badge bg-danger ms-1">Stock Out</span>
      </div>
      <div class="card-body">
        <div class="chart-box" style="height:320px;">
          <canvas id="dailyChart" data-labels='<?= htmlspecialchars($dailyLabels, ENT_QUOTES, 'UTF-8') ?>' data-in='<?= htmlspecialchars($dailyIn, ENT_QUOTES, 'UTF-8') ?>' data-out='<?= htmlspecialchars($dailyOut, ENT_QUOTES, 'UTF-8') ?>'></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold"><i class="bi bi-pie-chart"></i> Value by Category</div>
      <div class="card-body">
        <div class="chart-box" style="height:320px;">
          <canvas id="reportCatChart" data-values='<?= htmlspecialchars(json_encode($valueByCat), ENT_QUOTES, 'UTF-8') ?>'></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Top movers + Low stock -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header fw-semibold"><i class="bi bi-lightning"></i> Top Movers (<?= Security::e($from) ?> â†’ <?= Security::e($to) ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Code</th><th>Name</th><th class="text-end">Txns</th><th class="text-end">Total Qty</th></tr></thead>
            <tbody>
            <?php if (empty($topMovers)): ?><tr><td colspan="4" class="text-muted text-center">No transactions in this range.</td></tr>
            <?php else: foreach ($topMovers as $tm): ?>
              <tr>
                <td><code><?= Security::e($tm['item_code']) ?></code></td>
                <td><?= Security::e($tm['name']) ?></td>
                <td class="text-end"><?= (int)$tm['cnt'] ?></td>
                <td class="text-end"><?= (int)$tm['total_qty'] ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header fw-semibold text-danger"><i class="bi bi-exclamation-circle"></i> Low Stock Items</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Threshold</th></tr></thead>
            <tbody>
            <?php if (empty($low)): ?><tr><td colspan="3" class="text-muted text-center">No low stock items.</td></tr>
            <?php else: foreach ($low as $l): ?>
              <tr>
                <td><?= Security::e($l['name']) ?></td>
                <td class="text-end"><span class="badge bg-danger"><?= (int)$l['current_qty'] ?></span></td>
                <td class="text-end"><?= (int)$l['reorder_point'] ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent movements -->
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="bi bi-clock-history"></i> Recent Stock Movements</div>
  <div class="card-body p-0">
    <div class="table-responsive table-scroll">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th data-sort="type" style="width: 110px;">Type</th><th data-sort="item">Item</th><th data-sort="qty" class="text-end" style="width: 80px;">Qty</th><th data-sort="by" style="width: 180px;">By</th><th data-sort="date" style="width: 160px;">Date</th></tr></thead>
        <tbody>
        <?php if (empty($recent)): ?><tr><td colspan="5" class="text-muted text-center">No movements yet.</td></tr>
        <?php else: foreach ($recent as $r): ?>
          <tr>
            <td><span class="badge bg-<?= $r['type']==='stock_in'?'success':($r['type']==='stock_out'?'danger':'secondary') ?>"><?= Security::e(strtoupper(str_replace('_', ' ', $r['type']))) ?></span></td>
            <td class="text-truncate" style="max-width: 260px;"><?= Security::e($r['item_name']) ?></td>
            <td class="text-end"><?= (int)$r['quantity'] ?></td>
            <td class="text-truncate" style="max-width: 180px;"><?= Security::e($r['user_name'] ?? '-') ?></td>
            <td class="text-nowrap"><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function(){
  function initDaily(){
    var ctx = document.getElementById('dailyChart');
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
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { maxTicksLimit: 12, font: { size: 11 } } },
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }
  function initCat(){
    var ctx = document.getElementById('reportCatChart');
    if(!ctx) return;
    var data = JSON.parse(ctx.dataset.values || '[]');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: data.map(function(x){ return x.label; }),
        datasets: [{ data: data.map(function(x){ return x.value; }), backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6610f2'] }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } } }
      }
    });
  }
  window.addEventListener('load', function(){
    if(window.Chart){ initDaily(); initCat(); }
  });
})();
</script>
