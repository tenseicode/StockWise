<?php /** @var array $settings @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><i class="bi bi-gear"></i> Settings</h4>
  <a href="<?= $base ?>dashboard" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $base ?>settings/save">
      <?= Security::csrfField() ?>

      <h6 class="fw-semibold text-primary">General</h6>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label">System Name</label>
          <input type="text" name="app_name" class="form-control" value="<?= Security::e($settings['app_name'] ?? 'StockWise') ?>">
          <div class="form-text">Shown in the navbar, footer, and page titles.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Timezone</label>
          <select name="app_timezone" class="form-select">
            <?php
              $tzs = ['Asia/Manila'=>'Asia/Manila (UTC+8)', 'Asia/Singapore'=>'Asia/Singapore (UTC+8)', 'UTC'=>'UTC', 'America/New_York'=>'America/New_York', 'Europe/London'=>'Europe/London', 'Australia/Sydney'=>'Australia/Sydney'];
              $cur = $settings['app_timezone'] ?? 'Asia/Manila';
              foreach ($tzs as $v=>$l): ?>
              <option value="<?= Security::e($v) ?>" <?= $cur===$v?'selected':'' ?>><?= Security::e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Items Per Page (tables)</label>
          <input type="number" name="items_per_page" class="form-control" min="10" max="200" value="<?= (int)($settings['items_per_page'] ?? 50) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Default Reorder Point (new items)</label>
          <input type="number" name="default_reorder_point" class="form-control" min="0" value="<?= (int)($settings['default_reorder_point'] ?? 5) ?>">
        </div>
      </div>

      <h6 class="fw-semibold text-primary">Notifications</h6>
      <div class="mb-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="nls" name="notify_low_stock" value="1" <?= ($settings['notify_low_stock'] ?? '1')==='1'?'checked':'' ?>>
          <label class="form-check-label" for="nls">Low-stock alerts to Admin &amp; VP Finance</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="nor" name="notify_on_register" value="1" <?= ($settings['notify_on_register'] ?? '1')==='1'?'checked':'' ?>>
          <label class="form-check-label" for="nor">Notify admins when a new user registers</label>
        </div>
      </div>

      <h6 class="fw-semibold text-primary">Approval &amp; Delegation</h6>
      <div class="mb-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="sad" name="supply_admin_delegation_enabled" value="1" <?= ($settings['supply_admin_delegation_enabled'] ?? '0')==='1'?'checked':'' ?>>
          <label class="form-check-label" for="sad">Automatically delegate the Supply Administrator's approval to Supply Personnel</label>
          <div class="form-text">
            When the Supply Administrator is busy or absent, every new request is automatically routed to Supply Personnel
            for the first step. The delegation is logged in the status history and shown in notifications. The Supply
            Administrator can still delegate individual requests from the approval screen.
          </div>
        </div>
      </div>

      <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Settings</button>
    </form>
  </div>
</div>
