<?php /** @var array|null $user @var array $roles @var array $offices @var string $base */ ?>
<?php $isEdit = $user !== null; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0"><?= $isEdit ? 'Edit User' : 'Add User' ?></h4>
  <a href="<?= $base ?>admin/users" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $base ?>admin/users/<?= $isEdit ? 'edit/' . (int)$user['id'] : 'add' ?>">
      <?= Security::csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name <span class="text-danger">*</span></label>
          <input type="text" name="full_name" class="form-control" value="<?= $isEdit ? Security::e($user['full_name']) : '' ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" value="<?= $isEdit ? Security::e($user['email']) : '' ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Role <span class="text-danger">*</span></label>
          <select name="role_id" class="form-select" required>
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['id'] ?>" <?= $isEdit && (int)$r['id'] === (int)$user['role_id'] ? 'selected' : '' ?>><?= Security::e($r['role_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Office</label>
          <select name="office_id" class="form-select">
            <option value="">-- none --</option>
            <?php foreach ($offices as $o): ?>
              <option value="<?= (int)$o['id'] ?>" <?= $isEdit && (int)$o['id'] === (int)$user['office_id'] ? 'selected' : '' ?>><?= Security::e($o['office_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Password <?= $isEdit ? '(leave blank to keep)' : '<span class="text-danger">*</span>' ?></label>
          <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
        </div>
        <?php if ($isEdit): ?>
        <div class="col-12">
          <div class="form-check">
            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActive">Active</label>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="mt-4"><button class="btn btn-primary"><?= $isEdit ? 'Update User' : 'Create User' ?></button></div>
    </form>
  </div>
</div>
