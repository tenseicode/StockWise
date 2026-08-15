<?php
/**
 * Notification helper: create and fetch notifications, plus low-stock alerts.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Setting.php';

class NotificationHelper
{
    /**
     * Notify a single user by id.
     */
    public static function notify(int $userId, string $message, ?string $link = null): void
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, message, link, is_read)
             VALUES (:uid, :msg, :link, 0)'
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':msg'  => $message,
            ':link' => $link,
        ]);
    }

    /**
     * Notify every user that holds one of the given role names.
     */
    public static function notifyRole(array $roleNames, string $message, ?string $link = null): void
    {
        $pdo = db();
        $in  = implode(',', array_fill(0, count($roleNames), '?'));
        $stmt = $pdo->prepare(
            "SELECT u.id FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.role_name IN ($in) AND u.is_active = 1"
        );
        $stmt->execute($roleNames);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($users as $uid) {
            self::notify((int)$uid, $message, $link);
        }
    }

    /**
     * Notify the office of the requestor when a request is created/changed.
     */
    public static function notifyRequestor(int $requestorId, string $message, ?string $link = null): void
    {
        self::notify($requestorId, $message, $link);
    }

    /**
     * Unread notification count for the current user.
     */
    public static function unreadCount(int $userId): int
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Latest notifications for the bell dropdown.
     */
    public static function latest(int $userId, int $limit = 10): array
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int)$limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mark a single notification (or all) as read.
     */
    public static function markRead(int $userId, ?int $id = null): void
    {
        $pdo = db();
        if ($id !== null) {
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
        } else {
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
            $stmt->execute([$userId]);
        }
    }

    /**
     * Check all items and alert admin + VP finance about low stock.
     * Runs as a lightweight dedup: only notifies when crossing the threshold.
     */
    public static function checkLowStock(): void
    {
        // Respect the admin setting "notify_low_stock".
        if (Setting::get('notify_low_stock', '1') !== '1') {
            return;
        }
        $pdo = db();
        $stmt = $pdo->query(
            'SELECT id, name, current_qty, reorder_point FROM items
             WHERE reorder_point > 0 AND current_qty <= reorder_point'
        );
        $low = $stmt->fetchAll();
        foreach ($low as $item) {
            self::notifyRole(
                ['admin', 'vp_finance'],
                "Low stock alert: {$item['name']} ({$item['current_qty']} left, threshold {$item['reorder_point']}).",
                'items'
            );
        }
    }
}
