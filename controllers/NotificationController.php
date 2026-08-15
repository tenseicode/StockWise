<?php
/**
 * NotificationController - notification inbox.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Notification.php';

class NotificationController extends BaseController
{
    public function index(): void
    {
        $user = AuthMiddleware::requireLogin();
        Notification::markAllRead((int)$user['id']);
        $this->render('notifications/list', [
            'notifications' => Notification::forUser((int)$user['id'], 500),
        ]);
    }

    public function markRead(int $id): void
    {
        $user = AuthMiddleware::requireLogin();
        Notification::markRead((int)$user['id'], $id);
        $this->redirect('notifications', 'info', 'Notification marked as read.');
    }
}
