<?php
/**
 * BaseController - shared view rendering and flash messages.
 */

require_once BASE_PATH . 'middleware' . DIRECTORY_SEPARATOR . 'AuthMiddleware.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Notification.php';

class BaseController
{
    protected array $user;

    /**
     * Render a page inside the application layout (header + sidebar + footer).
     * Requires authentication.
     */
    protected function render(string $view, array $data = []): void
    {
        $this->user = AuthMiddleware::requireLogin();

        $data['user'] = $this->user;
        $data['unread'] = Notification::unreadCount((int)$this->user['id']);
        $data['notifications'] = Notification::forUser((int)$this->user['id'], 10);
        $data['appName'] = class_exists('Setting') ? (string)Setting::get('app_name', 'StockWise') : 'StockWise';

        // Alias the view path in a way the layout can use.
        $data['contentView'] = $view;
        $data['base'] = BASE_URL;

        extract($data, EXTR_SKIP);
        require BASE_PATH . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
        require BASE_PATH . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'sidebar.php';
        require BASE_PATH . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
        require BASE_PATH . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php';
    }

    /**
     * Render a standalone page (login, forgot) with no sidebar.
     */
    protected function renderStandalone(string $view, array $data = []): void
    {
        $data['base'] = BASE_URL;
        extract($data, EXTR_SKIP);
        require BASE_PATH . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
    }

    /**
     * Store a one-shot flash message.
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Redirect with an optional flash message.
     */
    protected function redirect(string $path, ?string $type = null, ?string $message = null): void
    {
        if ($type && $message) {
            $this->flash($type, $message);
        }
        Security::redirect($path);
    }
}
