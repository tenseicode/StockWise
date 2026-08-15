<?php
/**
 * Archive model - records soft-deletions / archivals for audit and restore.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class Archive
{
    /**
     * Write an archive record.
     */
    public static function record(string $entityType, int $entityId, array $data = []): void
    {
        $stmt = db()->prepare(
            'INSERT INTO archives (entity_type, entity_id, data_json, archived_by)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $entityType,
            $entityId,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $_SESSION['user_id'] ?? null,
        ]);
    }

    /**
     * All archive records (most recent first), with the acting user's name.
     */
    public static function all(): array
    {
        return db()->query(
            "SELECT a.*, u.full_name AS archived_by_name
             FROM archives a
             LEFT JOIN users u ON u.id = a.archived_by
             ORDER BY a.id DESC LIMIT 500"
        )->fetchAll();
    }
}
