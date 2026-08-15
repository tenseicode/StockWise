<?php /** @var array $rows @var string $type @var string $base */ ?>
<?php $label = $type === 'category' ? 'Categories' : 'Locations'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><?= $label ?></h4>
  <a href="<?= $base ?>dashboard" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $base ?>admin/<?= $type ?>s/add" class="row g-2 mb-3">
      <?= Security::csrfField() ?>
      <div class="col-auto"><input type="text" name="name" class="form-control" placeholder="New <?= $type ?> name" required></div>
      <div class="col-auto"><button class="btn btn-primary">Add</button></div>
    </form>
    <div class="table-responsive table-scroll">
    <table class="table table-sm mb-0">
      <thead><tr><th data-sort="name">Name</th><th data-sort="cnt" class="text-center">Used By (items)</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?><tr><td colspan="3" class="text-muted">No <?= $type ?>s yet.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= Security::e($r['name']) ?></td>
          <td><?= (int)$r['item_count'] ?></td>
          <td class="text-end">
            <form class="d-inline" method="post" action="<?= $base ?>admin/<?= $type ?>s/delete/<?= (int)$r['id'] ?>" onsubmit="return confirm('Delete this <?= $type ?>?');">
              <?= Security::csrfField() ?>
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
