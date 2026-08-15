<?php /** @var array $archives @var array $items @var array $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><i class="bi bi-archive"></i> Archive</h4>
  <a href="<?= $base ?>items" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-left"></i> Active Items</a>
</div>

<ul class="nav nav-tabs mb-3" id="archTab" role="tablist">
  <li class="nav-item" role="presentation"><button class="nav-link active" id="arch-items-tab" data-bs-toggle="tab" data-bs-target="#arch-items">Archived Items</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link" id="arch-audit-tab" data-bs-toggle="tab" data-bs-target="#arch-audit">Archive Audit Trail</button></li>
</ul>
<div class="tab-content">
  <div class="tab-pane fade show active" id="arch-items">
    <?php if (empty($items)): ?>
      <p class="text-muted">No archived items.</p>
    <?php else: ?>
      <div class="table-responsive table-scroll">
      <table class="table table-sm table-bordered align-middle">
        <thead class="table-light"><tr><th data-sort="id">#</th><th data-sort="code">Code</th><th data-sort="name">Name</th><th data-sort="qty" class="text-end">Qty</th><th data-sort="loc">Location</th><th>Restored</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= (int)$it['id'] ?></td>
            <td><?= Security::e($it['item_code']) ?></td>
            <td><?= Security::e($it['name']) ?></td>
            <td class="text-end"><?= (int)$it['current_qty'] ?></td>
            <td><?= Security::e($it['location_name'] ?? '-') ?></td>
            <td>
              <form method="post" action="<?= $base ?>items/restore/<?= (int)$it['id'] ?>" class="d-inline">
                <?= Security::csrfField() ?>
                <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
  <div class="tab-pane fade" id="arch-audit">
    <?php if (empty($archives)): ?>
      <p class="text-muted">No archive audit records.</p>
    <?php else: ?>
      <div class="table-responsive table-scroll">
      <table class="table table-sm table-bordered">
        <thead class="table-light"><tr><th data-sort="id">#</th><th data-sort="type">Type</th><th data-sort="eid">ID</th><th data-sort="by">By</th><th data-sort="when">When</th><th>Data</th><th>Delete</th></tr></thead>
        <tbody>
        <?php foreach ($archives as $a): ?>
          <tr>
            <td><?= (int)$a['id'] ?></td>
            <td><?= Security::e($a['entity_type']) ?></td>
            <td><?= (int)$a['entity_id'] ?></td>
            <td><?= Security::e($a['archived_by_name'] ?? '-') ?></td>
            <td class="text-nowrap"><?= Security::e($a['created_at'] ?? '') ?></td>
            <td><code><?= Security::e($a['data_json'] ?? '') ?></code></td>
            <td>
              <form method="post" action="<?= $base ?>admin/archived/delete/<?= (int)$a['id'] ?>" class="d-inline" onsubmit="return confirm('Remove this audit record permanently?')";>
                <?= Security::csrfField() ?>
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
                </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
</div>

