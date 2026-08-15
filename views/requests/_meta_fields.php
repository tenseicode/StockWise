<?php
/**
 * Shared meta fields for request forms.
 * @var string $type
 * @var array|null $request
 */
$request = $request ?? null;
$purpose = $request['purpose'] ?? '';
$neededBy = !empty($request['needed_by']) ? date('Y-m-d\TH:i', strtotime($request['needed_by'])) : '';
$publishedNote = in_array($type, ['RIS', 'ARE'], true);
?>
<div class="card">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Purpose / Details</label>
        <textarea name="purpose" class="form-control" rows="2" required placeholder="Describe the request..."><?= Security::e($purpose) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Needed By <span class="text-muted small">(date &amp; time)</span></label>
        <input type="datetime-local" name="needed_by" class="form-control" value="<?= Security::e($neededBy) ?>">
        <div class="form-text">The required date/time this request should be completed.</div>
      </div>
    </div>
  </div>
</div>