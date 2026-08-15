<?php
/**
 * @var string $type @var array $items @var array|null $request @var array|null $existing @var string $base
 */
$isEdit = !empty($request);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $isEdit ? 'Edit' : 'New' ?> PPE - Property / Equipment Request</h4>
  <a href="<?= $base ?>requests" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> My Requests</a>
</div>

<form method="post" action="<?= $base ?>requests/<?= $isEdit ? 'edit/' . (int)$request['id'] : 'new/PPE' ?>">
  <?= Security::csrfField() ?>
  <?php include __DIR__ . DIRECTORY_SEPARATOR . '_meta_fields.php'; ?>
  <?php include __DIR__ . DIRECTORY_SEPARATOR . '_items_rows.php'; ?>
  <div class="alert alert-info small mt-4 mb-0">
    <i class="bi bi-info-circle"></i> The PPE form covers property, plant and equipment <strong>below &#8369;50,000</strong>. Approval chain: Supply Administrator → Budget Head → Procurement Head → VP.
  </div>
  <div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-save"></i> <?= $isEdit ? 'Save Changes' : 'Save as Draft' ?></button>
  </div>
</form>