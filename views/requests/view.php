<?php
/**
 * @var array $request @var array $steps @var array $history @var array $items
 * @var array $meta @var bool $canEdit @var bool $canSubmit @var string $base
 */
$rejected = array_values(array_filter($steps, fn($s) => ($s['status'] ?? '') === 'rejected'));
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><?= Security::e($request['type']) ?> - <?= Security::e($request['request_number']) ?></h4>
  <a href="<?= $base ?>requests" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (($request['status'] ?? '') === 'returned'): ?>
  <div class="alert alert-danger d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <strong>Returned to Requester.</strong> The request must be edited and resubmitted; the approval chain will restart from the beginning.
      <?php if (!empty($rejected)): ?>
        <div class="small mt-1">
          Rejected by <strong><?= Security::e($rejected[0]['role_label']) ?></strong> -
          <?= Security::e($rejected[0]['remarks'] ?? 'No remarks provided.') ?>
        </div>
      <?php endif; ?>
    </div>
    <?php if ($canEdit): ?>
      <a href="<?= $base ?>requests/edit/<?= (int)$request['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-pencil"></i> Edit &amp; Resubmit</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

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
      <div class="col-md-4 mt-1"><strong>Created:</strong> <?= Workflow::fmt($request['created_at']) ?></div>
      <div class="col-md-4 mt-1"><strong>Submissions:</strong> <?= (int)$request['submission_count'] ?></div>
      <div class="col-12 mt-2"><strong>Purpose:</strong> <?= Security::e($request['purpose']) ?></div>
      <?php if (!empty($request['requestor_signature'])): ?>
        <div class="col-12 mt-2">
          <strong>Requestor signature:</strong>
          <img src="<?= Security::e($request['requestor_signature']) ?>" class="sig-thumb d-block mt-1" alt="requestor signature">
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="bi bi-diagram-3"></i> Approval Progress</div>
  <div class="card-body sw-timeline-wrap">
    <?php include BASE_PATH . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . '_timeline.php'; ?>
  </div>
</div>

<?php if ($canSubmit): ?>
  <div class="card mb-4 border-primary">
    <div class="card-header fw-semibold text-primary">
      <?= ($request['status'] ?? '') === 'returned' ? 'Resubmit for Approval' : 'Submit for Approval' ?>
    </div>
    <div class="card-body">
      <p class="text-muted small">
        Your signature is required as proof of submission<?= ($request['status'] ?? '') === 'returned' ? ' (the approval sequence will restart from the beginning)' : ' (the request will start with the Supply Administrator)' ?>.
      </p>
      <form method="post" action="<?= $base ?>requests/submit/<?= (int)$request['id'] ?>" onsubmit="return captureRequestSig(this);">
        <?= Security::csrfField() ?>
        <input type="hidden" name="requestor_signature" id="reqSignatureField">
        <div class="mb-3">
          <label class="form-label">Sign below (mouse/touch)</label>
          <canvas id="reqSigCanvas" class="sig-canvas" width="520" height="140"></canvas>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearReqSig()"><i class="bi bi-eraser"></i> Clear</button>
        </div>
        <button class="btn btn-success"><i class="bi bi-send"></i> <?= ($request['status'] ?? '') === 'returned' ? 'Resubmit' : 'Submit' ?> Request</button>
      </form>
    </div>
  </div>
<?php elseif ($canEdit): ?>
  <div class="mb-4">
    <a href="<?= $base ?>requests/edit/<?= (int)$request['id'] ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit Draft</a>
  </div>
<?php endif; ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header fw-semibold"><i class="bi bi-list-ul"></i> Line Items</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Item</th><th>Requested</th><th>Approved</th><th>Unit</th></tr></thead>
          <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="4" class="text-muted">No line items.</td></tr>
          <?php else: foreach ($items as $ri): ?>
            <tr>
              <td><?= Security::e($ri['item_name']) ?> <small class="text-muted">(<?= Security::e($ri['item_code']) ?>)</small></td>
              <td><?= (int)$ri['requested_qty'] ?></td>
              <td><?= (int)$ri['approved_qty'] ?></td>
              <td><?= Security::e($ri['unit'] ?? $ri['item_unit']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
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
var reqCanvas = null, reqCtx = null, reqWriting = false;
function initReqSig() {
  reqCanvas = document.getElementById('reqSigCanvas');
  if (!reqCanvas) return;
  reqCtx = reqCanvas.getContext('2d');
  reqCtx.fillStyle = '#fff'; reqCtx.fillRect(0, 0, reqCanvas.width, reqCanvas.height);
  reqCtx.strokeStyle = '#0d0d0d'; reqCtx.lineWidth = 2; reqCtx.lineCap = 'round';
  reqCanvas.addEventListener('mousedown', function (e) { reqWriting = true; reqCtx.beginPath(); reqCtx.moveTo(e.offsetX, e.offsetY); });
  reqCanvas.addEventListener('mousemove', function (e) { if (reqWriting) { reqCtx.lineTo(e.offsetX, e.offsetY); reqCtx.stroke(); } });
  window.addEventListener('mouseup', function () { reqWriting = false; });
  reqCanvas.addEventListener('touchstart', function (e) { e.preventDefault(); reqWriting = true; var t = e.touches[0]; var r = reqCanvas.getBoundingClientRect(); reqCtx.beginPath(); reqCtx.moveTo(t.clientX - r.left, t.clientY - r.top); }, { passive: false });
  reqCanvas.addEventListener('touchmove', function (e) { e.preventDefault(); if (reqWriting) { var t = e.touches[0]; var r = reqCanvas.getBoundingClientRect(); reqCtx.lineTo(t.clientX - r.left, t.clientY - r.top); reqCtx.stroke(); } }, { passive: false });
  reqCanvas.addEventListener('touchend', function () { reqWriting = false; });
}
function clearReqSig() {
  if (reqCtx && reqCanvas) { reqCtx.clearRect(0, 0, reqCanvas.width, reqCanvas.height); reqCtx.fillStyle = '#fff'; reqCtx.fillRect(0, 0, reqCanvas.width, reqCanvas.height); }
  var f = document.getElementById('reqSignatureField'); if (f) f.value = '';
}
function captureRequestSig(form) {
  var c = document.getElementById('reqSigCanvas');
  if (!c) { alert('Signature pad not available.'); return false; }
  document.getElementById('reqSignatureField').value = c.toDataURL('image/png');
  return true;
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initReqSig); } else { initReqSig(); }