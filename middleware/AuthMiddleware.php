<?php
/** Authentication guards: session login + optional role check. */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Security.php';

class AuthMiddleware
{
    /**
     * Return the currently authenticated user (from session) or null.
     */
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $pdo = db();
        $stmt = $pdo->prepare(
            "SELECT u.*, r.role_name, o.office_name, o.office_code
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN offices o ON o.id = u.office_id
             WHERE u.id = ? AND u.is_active = 1"
        );
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            self::logout();
            return null;
        }
        return $user;
    }

    /**
     * Require an authenticated user. Redirects to login if absent.
     */
    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) {
            Security::redirect('login');
        }
        return $user;
    }

    /**
     * Require the user to hold at least one of the given roles.
     */
    public static function requireRole(array $roles): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role_name'], $roles, true)) {
            Security::abort(403, 'Access denied: insufficient permissions.');
        }
        return $user;
    }

    /**
     * Perform a full login: verify credentials and (optionally) check the role.
     * Regenerates the session id to prevent session fixation.
     */
    public static function attemptLogin(string $email, string $password, ?array $roles = null): array
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            "SELECT u.*, r.role_name FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.is_active = 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        if ($roles !== null && !in_array($user['role_name'], $roles, true)) {
            return ['success' => false, 'message' => 'This account cannot log in here.'];
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role']    = $user['role_name'];
        $_SESSION['name']    = $user['full_name'];

        // Audit log the successful login.
        self::logAudit($user['id'], 'login');

        return ['success' => true, 'user' => $user];
    }

    /**
     * Destroy the session (logout).
     */
    public static function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            self::logAudit($_SESSION['user_id'], 'logout');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /**
     * Write an entry to the audit log.
     */
    public static function logAudit(?int $userId, string $action): void
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, ip_address) VALUES (:uid, :action, :ip)'
        );
        $stmt->execute([
            ':uid'    => $userId,
            ':action' => $action,
            ':ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
