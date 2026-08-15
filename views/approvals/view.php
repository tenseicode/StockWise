<?php
/**
 * @var array $request @var array $items @var array $steps @var array $history
 * @var array $meta @var string $action @var bool $canDelegate
 * @var array $supplyPersonnel @var string $base
 */
$current = Workflow::currentPendingStep($steps);
$delegated = $current ? (($current['delegation_status'] ?? 'none') !== 'none') : false;
$isSupplyAdminStep = $current && $current['role_code'] === 'supply_admin';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Request <?= Security::e($request['request_number']) ?></h4>
  <a href="<?= $base ?>approvals" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Approvals</a>
</div>

<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
      <h5 class="mb-0"><?= Security::e($request['type']) ?> - <?= Security::e($request['office_name'] ?? '') ?></h5>
      <span class="badge bg-<?= Security::e($meta['badge']) ?> fs-6"><?= Security::e($meta['label']) ?></span>
    </div>
    <div class="row small">
      <div class="col-md-4"><strong>Requestor:</strong> <?= Security::e($request['requestor_name']) ?></div>
      <div class="col-md-4"><strong>Office:</strong> <?= Security::e($request['office_name']) ?></div>
      <div class="col-md-4"><strong>Needed By:</strong> <?= Workflow::fmt($request['needed_by']) ?></div>
      <div class="col-md-4 mt-1"><strong>Submitted:</strong> <?= Workflow::fmt($request['submitted_at']) ?></div>
      <div class="col-md-4 mt-1"><strong>Submissions:</strong> <?= (int)$request['submission_count'] ?></div>
      <div class="col-12 mt-2"><strong>Purpose:</strong> <?= Security::e($request['purpose']) ?></div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="bi bi-diagram-3"></i> Approval Progress</div>
  <div class="card-body sw-timeline-wrap">
    <?php include BASE_PATH . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . '_timeline.php'; ?>
  </div>
</div>

<?php if ($action === 'approve'): ?>
  <?php if ($isSupplyAdminStep && $canDelegate && !$delegated && ($user['role_name'] ?? '') === 'admin'): ?>
    <div class="card mb-4 border-warning">
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <strong><i class="bi bi-person-workspace"></i> Supply Administrator busy or absent?</strong>
          <div class="text-muted small">Delegate this approval to a Supply Personnel. The delegation is recorded in the request history and notifications.</div>
        </div>
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#delegateModal">
          <i class="bi bi-arrow-left-right"></i> Delegate to Supply Personnel
        </button>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header fw-semibold">Your Decision
      <?php if ($delegated): ?><span class="badge bg-warning text-dark ms-1"><i class="bi bi-person-workspace"></i> Acting on behalf of the Supply Administrator</span><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="post" action="<?= $base ?>approvals/act/<?= (int)$request['id'] ?>" onsubmit="return captureApprovalSig(this);">
        <?= Security::csrfField() ?>
        <input type="hidden" name="signature" id="signatureField">
        <div class="mb-3">
          <label class="form-label">Sign below (mouse/touch) - required for approval</label>
          <canvas id="sigCanvas" class="sig-canvas" width="520" height="160"></canvas>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearApprovalSig()"><i class="bi bi-eraser"></i> Clear</button>
        </div>
        <div class="mb-3">
          <label class="form-label">Comments / Remarks <span class="text-danger">*</span></label>
          <textarea name="remarks" class="form-control" rows="2" required placeholder="Your remarks are required on every approval or rejection."></textarea>
        </div>
        <div class="mb-1 d-flex gap-2 flex-wrap">
          <button type="submit" name="decision" value="approve" class="btn btn-success"><i class="bi bi-check-lg"></i> Approve &amp; Sign</button>
          <button type="submit" name="decision" value="reject" class="btn btn-danger" onclick="return confirm('Reject this request? It will be returned to the requester.');"><i class="bi bi-x-lg"></i> Reject &amp; Return</button>
        </div>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-secondary">This request is not at your step in the workflow.</div>
<?php endif; ?>
<!-- Delegate modal (Supply Administrator -> Supply Personnel) -->
<div class="modal fade" id="delegateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= $base ?>approvals/delegate/<?= (int)$request['id'] ?>">
        <?= Security::csrfField() ?>
        <div class="modal-header">
          <h6 class="modal-title"><i class="bi bi-person-workspace"></i> Delegate Approval</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Assign to Supply Personnel</label>
          <select name="supply_personnel_id" class="form-select">
            <option value="">-- any active Supply Personnel --</option>
            <?php foreach ($supplyPersonnel as $sp): ?>
              <option value="<?= (int)$sp['id'] ?>"><?= Security::e($sp['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">The delegation is logged in the status history and notified to the parties involved.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-left-right"></i> Confirm Delegation</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="row g-4 mt-1">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header fw-semibold"><i class="bi bi-list-ul"></i> Line Items</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Item</th><th>Requested</th><th>Approved</th><th>Unit</th></tr></thead>
          <tbody>
          <?php foreach ($items as $ri): ?>
            <tr>
              <td><?= Security::e($ri['item_name']) ?> <small class="text-muted">(<?= Security::e($ri['item_code']) ?>)</small></td>
              <td><?= (int)$ri['requested_qty'] ?></td>
              <td><?= (int)$ri['approved_qty'] ?></td>
              <td><?= Security::e($ri['unit'] ?? $ri['item_unit']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header fw-semibold"><i class="bi bi-clock-history"></i> Status History</div>
      <div class="card-body sw-history">
        <?php if (empty($history)): ?>
          <div class="text-muted small">No activity recorded yet.</div>
        <?php else: foreach ($history as $h): ?>
          <div class="d-flex align-items-start mb-3">
            <span class="me-2 mt-1">
              <i class="bi bi-<?= $h['action'] === 'approve' ? 'check-circle text-success' : ($h['action'] === 'reject' || $h['action'] === 'returned' ? 'x-circle text-danger' : ($h['action'] === 'delegated' ? 'person-workspace text-warning' : 'circle text-secondary')) ?>"></i>
            </span>
            <div class="small">
              <div class="fw-semibold"><?= Security::e($h['label'] ?? ucfirst($h['action'])) ?></div>
              <?php if ($h['actor_name'] && $h['actor_name'] !== 'System'): ?>
                <div class="text-muted"><?= Security::e($h['actor_name']) ?><?= $h['actor_role'] ? ' (' . Security::e(ucwords(str_replace('_', ' ', $h['actor_role']))) . ')' : '' ?></div>
              <?php endif; ?>
              <?php if ($h['remarks']): ?><div class="text-muted"><?= Security::e($h['remarks']) ?></div><?php endif; ?>
              <div class="text-muted small"><?= Workflow::fmt($h['created_at']) ?></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
var apCanvas = null, apCtx = null, apWriting = false;
function initApprovalSig() {
  apCanvas = document.getElementById('sigCanvas');
  if (!apCanvas) return;
  apCtx = apCanvas.getContext('2d');
  apCtx.fillStyle = '#fff'; apCtx.fillRect(0, 0, apCanvas.width, apCanvas.height);
  apCtx.strokeStyle = '#0d0d0d'; apCtx.lineWidth = 2; apCtx.lineCap = 'round';
  apCanvas.addEventListener('mousedown', function (e) { apWriting = true; apCtx.beginPath(); apCtx.moveTo(e.offsetX, e.offsetY); });
  apCanvas.addEventListener('mousemove', function (e) { if (apWriting) { apCtx.lineTo(e.offsetX, e.offsetY); apCtx.stroke(); } });
  window.addEventListener('mouseup', function () { apWriting = false; });
  apCanvas.addEventListener('touchstart', function (e) { e.preventDefault(); apWriting = true; var t = e.touches[0]; var r = apCanvas.getBoundingClientRect(); apCtx.beginPath(); apCtx.moveTo(t.clientX - r.left, t.clientY - r.top); }, { passive: false });
  apCanvas.addEventListener('touchmove', function (e) { e.preventDefault(); if (apWriting) { var t = e.touches[0]; var r = apCanvas.getBoundingClientRect(); apCtx.lineTo(t.clientX - r.left, t.clientY - r.top); apCtx.stroke(); } }, { passive: false });
  apCanvas.addEventListener('touchend', function () { apWriting = false; });
}
function clearApprovalSig() {
  if (apCtx && apCanvas) { apCtx.clearRect(0, 0, apCanvas.width, apCanvas.height); apCtx.fillStyle = '#fff'; apCtx.fillRect(0, 0, apCanvas.width, apCanvas.height); }
  var f = document.getElementById('signatureField'); if (f) f.value = '';
}
function captureApprovalSig(form) {
  var decision = form.elements['decision'] ? form.elements['decision'].value : '';
  if (decision === 'approve') {
    if (!apCanvas) { alert('Signature pad not available.'); return false; }
    document.getElementById('signatureField').value = apCanvas.toDataURL('image/png');
  }
  return true;
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initApprovalSig); } else { initApprovalSig(); }
</script>