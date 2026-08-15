<?php
/**
 * AdminController - user, category, location, and office-limit management.
 * Access is restricted to the admin role.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'OfficeLimit.php';

class AdminController extends BaseController
{
    // ===== Users ==============================================================
    public function users(): void
    {
        $this->render('admin/users', [
            'users' => User::all(),
        ]);
    }

    public function addUserForm(): void
    {
        $this->render('admin/user_form', [
            'user'    => null,
            'roles'   => User::allRoles(),
            'offices' => User::allOffices(),
        ]);
    }

    public function addUser(): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/users', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        if (empty($d['email']) || empty($d['password']) || empty($d['full_name']) || empty($d['role_id'])) {
            $this->redirect('admin/users/add', 'danger', 'All fields are required (except office).');
        }
        if (User::emailExists($d['email'])) {
            $this->redirect('admin/users/add', 'danger', 'That email is already registered.');
        }
        $roleNameStmt = db()->prepare('SELECT role_name FROM roles WHERE id = ?');
        $roleNameStmt->execute([(int)$d['role_id']]);
        $roleName = $roleNameStmt->fetchColumn();
        if ($roleName === 'requestor' && (empty($d['office_id']) || User::requestorExistsForOffice((int)$d['office_id']))) {
            $this->redirect('admin/users/add', 'danger', 'Each office has exactly one Requestor account. Pick an office without an active Requestor.');
        }
        User::create([
            'office_id' => $d['office_id'] ?? null,
            'role_id'   => $d['role_id'],
            'email'     => $d['email'],
            'password'  => $d['password'],
            'full_name' => $d['full_name'],
            'is_active' => 1,
        ]);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'user_create');
        $this->redirect('admin/users', 'success', 'User created.');
    }

    public function editUserForm(int $id): void
    {
        $this->guard();
        $user = User::find($id);
        if (!$user) {
            Security::abort(404, 'User not found.');
        }
        $this->render('admin/user_form', [
            'user'    => $user,
            'roles'   => User::allRoles(),
            'offices' => User::allOffices(),
        ]);
    }

    public function editUser(int $id): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/users', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        if (empty($d['email']) || empty($d['full_name']) || empty($d['role_id'])) {
            $this->redirect('admin/users/edit/' . $id, 'danger', 'Email, name, and role are required.');
        }
        if (User::emailExists($d['email'], $id)) {
            $this->redirect('admin/users/edit/' . $id, 'danger', 'That email is already in use.');
        }
        $roleNameStmt = db()->prepare('SELECT role_name FROM roles WHERE id = ?');
        $roleNameStmt->execute([(int)$d['role_id']]);
        $roleName = $roleNameStmt->fetchColumn();
        if ($roleName === 'requestor' && (empty($d['office_id']) || User::requestorExistsForOffice((int)$d['office_id'], $id))) {
            $this->redirect('admin/users/edit/' . $id, 'danger', 'Each office has exactly one Requestor account. Pick an office without an active Requestor.');
        }
        $data = [
            'office_id' => $d['office_id'] ?? null,
            'role_id'   => $d['role_id'],
            'email'     => $d['email'],
            'full_name' => $d['full_name'],
            'is_active' => !empty($d['is_active']) ? 1 : 0,
        ];
        if (!empty($d['password'])) {
            $data['password'] = $d['password'];
        }
        User::update($id, $data);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'user_update');
        $this->redirect('admin/users', 'success', 'User updated.');
    }

    public function toggleUser(int $id): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/users', 'danger', 'Invalid security token.');
        }
        $user = User::find($id);
        if (!$user || (int)$user['id'] === (int)$_SESSION['user_id']) {
            $this->redirect('admin/users', 'danger', 'Cannot deactivate your own account.');
        }
        User::setActive($id, (int)$user['is_active'] === 0);
        $this->redirect('admin/users', 'success', 'User status toggled.');
    }

    private function guard(): void
    {
        AuthMiddleware::requireRole(['admin']);
    }

    // ===== Categories =========================================================
    public function categories(): void
    {
        $this->guard();
        $this->render('admin/categories', [
            'rows' => db()->query('SELECT id, name, (SELECT COUNT(*) FROM items WHERE category_id = categories.id) AS item_count FROM categories ORDER BY name')->fetchAll(),
            'type' => 'category',
        ]);
    }

    public function addCategory(): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/categories', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        if (empty($d['name'])) {
            $this->redirect('admin/categories', 'danger', 'Category name required.');
        }
        db()->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$d['name']]);
        $this->redirect('admin/categories', 'success', 'Category added.');
    }

    public function deleteCategory(int $id): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/categories', 'danger', 'Invalid security token.');
        }
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        $this->redirect('admin/categories', 'warning', 'Category deleted.');
    }

    // ===== Locations ==========================================================
    public function locations(): void
    {
        $this->guard();
        $this->render('admin/categories', [
            'rows' => db()->query('SELECT id, name, (SELECT COUNT(*) FROM items WHERE location_id = locations.id) AS item_count FROM locations ORDER BY name')->fetchAll(),
            'type' => 'location',
        ]);
    }

    public function addLocation(): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/locations', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        if (empty($d['name'])) {
            $this->redirect('admin/locations', 'danger', 'Location name required.');
        }
        db()->prepare('INSERT INTO locations (name) VALUES (?)')->execute([$d['name']]);
        $this->redirect('admin/locations', 'success', 'Location added.');
    }

    public function deleteLocation(int $id): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/locations', 'danger', 'Invalid security token.');
        }
        db()->prepare('DELETE FROM locations WHERE id = ?')->execute([$id]);
        $this->redirect('admin/locations', 'warning', 'Location deleted.');
    }

    // ===== Office Limits =======================================================
    public function limits(): void
    {
        $this->guard();
        $year = (int)date('Y');
        $this->render('admin/limits', [
            'limits'  => OfficeLimit::all(),
            'usage'   => OfficeLimit::usageReport($year),
            'offices' => OfficeLimit::allOffices(),
            'items'   => OfficeLimit::allItems(),
            'year'    => $year,
        ]);
    }

    public function setLimit(): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/limits', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        $officeId = (int)($d['office_id'] ?? 0);
        $itemId   = (int)($d['item_id'] ?? 0);
        $year     = (int)($d['year'] ?? 0);
        $maxQty   = (int)($d['max_qty'] ?? 0);

        if (!$officeId || !$itemId || $year < 2000 || $year > 2099) {
            $this->redirect('admin/limits', 'danger', 'Select an office and item, and use a valid year (2000-2099).');
        }
        if ($maxQty < 1) {
            $this->redirect('admin/limits', 'danger', 'Maximum quantity must be at least 1.');
        }
        OfficeLimit::set($officeId, $itemId, $year, $maxQty);
        $this->redirect('admin/limits', 'success', 'Office limit saved.');
    }

    public function deleteLimit(int $id): void
    {
        $this->guard();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/limits', 'danger', 'Invalid security token.');
        }
        OfficeLimit::delete($id);
        $this->redirect('admin/limits', 'warning', 'Limit removed.');
    }
}
