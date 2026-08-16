<?php
/**
 * Horizontal approval timeline.
 * Expects: $steps (Request::steps), $request, $meta (Workflow::requestStatusMeta).
 * Each step shows approver role/name, status, signature and date/time.
 */
/** @var array $steps @var array $request @var array $meta */
$currentIdx = null;
$rejectIdx  = null; // index of the rejected step (if any)
foreach ($steps as $i => $s) {
    if (($s['status'] ?? '') === 'rejected') {
        $rejectIdx = $i;
    } elseif (($s['status'] ?? '') === 'pending' && $currentIdx === null) {
        $currentIdx = $i;
    }
}
?>
<div class="sw-timeline">
  <?php foreach ($steps as $i => $s): ?>
    <?php
      $state  = ($s['status'] ?? 'pending') === 'approved' ? 'done' : (($s['status'] ?? 'pending') === 'rejected' ? 'rejected' : 'todo');
      $isCur  = ($i === $currentIdx) && $state === 'todo';
      $isLast = $i === array_key_last($steps);
      $isAfterReject = $rejectIdx !== null && $i > $rejectIdx;
    ?>
    <div class="sw-step sw-step-<?= $state ?><?= $isCur ? ' current' : '' ?>">
      <div class="sw-step-head">
        <span class="sw-dot"><i class="bi <?= $state === 'done' ? 'bi-check-lg' : ($state === 'rejected' ? 'bi-x-lg' : '') ?>"></i></span>
        <?php if (!$isLast): ?><span class="sw-line"></span><?php endif; ?>
      </div>
      <div class="sw-card">
        <div class="sw-role"><?= Security::e($s['role_label']) ?></div>
        <div class="sw-status">
          <?php if ($state === 'done'): ?>
            <span class="badge bg-success">Approved</span>
          <?php elseif ($state === 'rejected'): ?>
            <span class="badge bg-danger">Rejected</span>
          <?php elseif ($isCur && ($s['delegation_status'] ?? 'none') !== 'none'): ?>
            <span class="badge bg-warning text-dark"><i class="bi bi-person-workspace"></i> Current (Delegated)</span>
          <?php elseif ($isCur): ?>
            <span class="badge bg-primary">Current</span>
          <?php elseif ($isAfterReject): ?>
            <span class="badge bg-secondary">Not reached</span>
          <?php else: ?>
            <span class="badge bg-secondary">Pending</span>
          <?php endif; ?>
        </div>

        <?php if ($state === 'done' || $state === 'rejected'): ?>
          <?php if (!empty($s['assigned_name'])): ?>
            <div class="sw-name"><?= Security::e($s['assigned_name']) ?></div>
          <?php endif; ?>
          <?php if (!empty($s['signature_base64'])): ?>
            <img class="sw-sig" src="<?= Security::e($s['signature_base64']) ?>" alt="signature">
          <?php endif; ?>
          <?php if ($s['remarks']): ?><div class="sw-remarks"><?= Security::e($s['remarks']) ?></div><?php endif; ?>
          <div class="sw-time"><?= Workflow::fmt($s['acted_at']) ?></div>
        <?php elseif ($isCur && ($s['delegation_status'] ?? 'none') !== 'none'): ?>
          <div class="sw-delegated">
            <?php if (!empty($s['delegated_to_name'])): ?>
              <i class="bi bi-person-down"></i> <?= Security::e($s['delegated_to_name']) ?>
            <?php else: ?>
              <i class="bi bi-person-down"></i> Supply Personnel
            <?php endif; ?>
            <span class="sw-time"><?= Workflow::fmt($s['delegated_at']) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Final result node -->
  <?php
    $finalState = 'todo';
    $finalLabel = 'Pending';
    $finalBadge = 'secondary';
    if (($request['status'] ?? '') === 'approved') {
        $finalState = 'done';
        $finalBadge = 'success';
        $finalLabel = $meta['label'] ?? 'Approved / Done';
    } elseif (($request['status'] ?? '') === 'returned') {
        $finalState = 'rejected';
        $finalBadge = 'danger';
        $finalLabel = 'Returned to Requester';
    }
  ?>
  <div class="sw-step sw-step-<?= $finalState ?>">
    <div class="sw-step-head">
      <span class="sw-dot"><i class="bi <?= $finalState === 'done' ? 'bi-check-lg' : ($finalState === 'rejected' ? 'bi-x-lg' : '') ?>"></i></span>
    </div>
    <div class="sw-card">
      <div class="sw-role">Result</div>
      <div class="sw-status">
        <span class="badge bg-<?= $finalBadge ?>"><?= Security::e($finalLabel) ?></span>
      </div>
    </div>
  </div>
</div>