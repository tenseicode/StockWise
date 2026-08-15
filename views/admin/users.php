<?php /** @var array $users @var string $base */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">User Management</h4>
  <div class="d-flex gap-2">
    <input type="search" class="form-control" style="max-width:240px;" placeholder="Search users..." data-table-search="usersTable" aria-label="Search">
        <a href="<?= $base ?>admin/users/add" class="btn btn-primary btn-nowrap"><i class="bi bi-person-plus"></i> Add User</a>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive table-scroll">
    <table class="table table-hover align-middle mb-0" id="usersTable">
      <thead><tr><th data-sort="name">Name</th><th data-sort="email">Email</th><th data-sort="role">Role</th><th data-sort="office">Office</th><th data-sort="status">Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= Security::e($u['full_name']) ?></td>
          <td><?= Security::e($u['email']) ?></td>
          <td><span class="badge bg-secondary"><?= Security::e($u['role_name']) ?></span></td>
          <td><?= Security::e($u['office_name'] ?? '-') ?></td>
          <td><?= $u['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?= $base ?>admin/users/edit/<?= (int)$u['id'] ?>"><i class="bi bi-pencil"></i></a>
                        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
            <button type="button"
              class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'warning' : 'success' ?> toggle-user"
              title="toggle status"
              data-uid="<?= (int)$u['id'] ?>"
              data-name="<?= Security::e($u['full_name']) ?>"
              data-active="<?= $u['is_active'] ? '1' : '0' ?>">
              <i class="bi bi-person-check"></i>
            </button>
            <form id="toggleForm-<?= (int)$u['id'] ?>" method="post" action="<?= $base ?>admin/users/toggle/<?= (int)$u['id'] ?>" class="d-none">
              <?= Security::csrfField() ?>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
            </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Confirm user status toggle -->
<div class="modal fade" id="userToggleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header">
        <h6 class="modal-title mb-0"><i class="bi bi-person-check"></i> Confirm Status Change</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body small text-muted">
        <p class="mb-0">Change status for <strong id="toggleUserName"></strong>?</p>
        <p class="mb-0 mt-1" id="toggleUserAction"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-primary" id="toggleUserConfirm">Confirm</button>
      </div>
    </div>
  </div>
</div>



