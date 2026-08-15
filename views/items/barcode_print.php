<?php /** @var array $items @var string $base */ ?>
<?php $role = $user['role_name'] ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barcode Labels - StockWise</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background: #fff; }
  .label-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 15px; }
  .barcode-label { border: 1px dashed #999; text-align: center; padding: 12px; page-break-inside: avoid; }
  .barcode-label img { max-width: 100%; height: 70px; }
  .barcode-label .lbl-name { font-weight: 600; font-size: 11px; }
  .barcode-label .lbl-code { font-size: 10px; color: #666; }
  @media print {
    .no-print { display: none; }
    .label-grid { grid-template-columns: repeat(3, 1fr); }
  }
</style>
</head>
<body>
<div class="no-print text-center my-3">
  <button onclick="window.print()" class="btn btn-primary">Print Labels</button>
  <a class="btn btn-secondary" href="<?= $base ?>items">Back</a>
</div>
<div class="label-grid">
  <?php foreach ($items as $it): ?>
    <div class="barcode-label">
      <img src="<?= $base ?>items/barcode/<?= (int)$it['id'] ?>" alt="barcode">
      <div class="lbl-name"><?= Security::e($it['item_code']) ?></div>
      <div class="lbl-name"><?= Security::e($it['name']) ?></div>
      <div class="lbl-code"><?= Security::e($it['unit'] ?? '') ?></div>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>
