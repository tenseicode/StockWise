<?php
/**
 * AuthController - login, logout, and password reset (forgot).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'NotificationHelper.php';

class AuthController extends BaseController
{
    public function showLogin(): void
    {
        AuthMiddleware::logAudit($_SESSION['user_id'] ?? null, 'view_login');
        if (AuthMiddleware::user()) {
            Security::redirect('dashboard');
        }
        $this->renderStandalone('auth/auth', ['mode' => 'login']);
    }

    public function doLogin(): void
    {
        if (!Security::verifyCsrf()) {
            $this->renderStandalone('auth/auth', ['mode' => 'login', 'error' => 'Invalid security token. Please try again.']);
            return;
        }
        $data = Security::clean($_POST);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $result = AuthMiddleware::attemptLogin($email, $password);
        if (!$result['success']) {
            $this->renderStandalone('auth/auth', ['mode' => 'login', 'error' => $result['message']]);
            return;
        }
        Security::redirect('dashboard');
    }

    public function logout(): void
    {
        AuthMiddleware::logout();
        Security::redirect('login');
    }

    /**
     * Self-service registration for requestor accounts.
     */
    public function showRegister(): void
    {
        if (AuthMiddleware::user()) {
            Security::redirect('dashboard');
        }
        $this->renderStandalone('auth/auth', [
            'mode'    => 'register',
            'offices' => User::allOffices(),
        ]);
    }

    public function doRegister(): void
    {
        if (!Security::verifyCsrf()) {
            $this->renderStandalone('auth/auth', [
                'mode'    => 'register',
                'offices' => User::allOffices(),
                'error'   => 'Invalid security token. Please try again.',
            ]);
            return;
        }
        $d = Security::clean($_POST);
        $email    = $d['email'] ?? '';
        $password = $d['password'] ?? '';
        $confirm  = $d['password_confirm'] ?? '';
        $fullName = $d['full_name'] ?? '';
        $officeId = $d['office_id'] ?? null;

        if (empty($email) || empty($password) || empty($fullName)) {
            $this->renderStandalone('auth/auth', ['mode' => 'register', 'offices' => User::allOffices(), 'error' => 'All fields are required.']);
            return;
        }
        if (empty($officeId)) {
            $this->renderStandalone('auth/auth', ['mode' => 'register', 'offices' => User::allOffices(), 'error' => 'Please select your office - only one Requestor is assigned per office.']);
            return;
        }
        if (User::requestorExistsForOffice((int)$officeId)) {
            $this->renderStandalone('auth/auth', ['mode' => 'register', 'offices' => User::allOffices(), 'error' => 'That office already has an active Requestor account. Ask the administrator to add you under the Supply office instead.']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderStandalone('auth/auth', ['mode' => 'register', 'offices' => User::allOffices(), 'error' => 'Please enter a valid email address.']);
            return;
        }
        if (strlen($password) < 8) {
            $this->renderStandalone('auth/auth', ['mode' => 'register', 'offices' => User::allOffices(), 'error' => 'Password must be at least 8 characters.']);
            return;
        }
        if ($password !== $confirm) {
            $this->renderStandalone('auth/auth', ['mode' => 'register', 'offices' => User::allOffices(), 'error' => 'Passwords do not match.']);
            return;
        }
        if (User::emailExists($email)) {
            $this->renderStandalone('auth/auth', ['mode' => 'register', 'offices' => User::allOffices(), 'error' => 'That email is already registered.']);
            return;
        }

        $requestorRoleId = User::findRoleId('requestor') ?? 3;
        User::create([
            'office_id' => $officeId ? (int)$officeId : null,
            'role_id'   => $requestorRoleId,
            'email'     => $email,
            'password'  => $password,
            'full_name' => $fullName,
            'is_active' => 1,
        ]);

        // Notify admins of the new account (respect the settings toggle).
        if (Setting::get('notify_on_register', '1') === '1') {
            NotificationHelper::notifyRole(['admin'], "New registration: $fullName ($email).", 'admin/users');
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Registration successful! You can now sign in.'];
        Security::redirect('login');
    }

    public function showForgot(): void
    {
        $this->renderStandalone('auth/forgot', []);
    }

    /** No mail server on XAMPP: reset the password and show it inline. */
    public function doForgot(): void
    {
        if (!Security::verifyCsrf()) {
            $this->renderStandalone('auth/forgot', ['error' => 'Invalid security token.']);
            return;
        }
        $data = Security::clean($_POST);
        $email = $data['email'] ?? '';

        require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'User.php';
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $uid = $stmt->fetchColumn();
        if (!$uid) {
            $this->renderStandalone('auth/forgot', ['error' => 'No account found with that email.']);
            return;
        }
        $temp = 'Temp@' . random_int(1000, 9999);
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($temp, PASSWORD_BCRYPT), (int)$uid]);
        AuthMiddleware::logAudit((int)$uid, 'password_reset');
        $this->renderStandalone('auth/forgot', ['success' => 'Password reset. Your temporary password is: ' . Security::e($temp)]);
    }
}
