<?php
/**
 * OfficeLimit model - yearly per-office, per-item consumption limits.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class OfficeLimit
{
    public static function all(): array
    {
        return db()->query(
            "SELECT ol.*, o.office_code, o.office_name, i.item_code, i.name AS item_name
             FROM office_limits ol
             JOIN offices o ON o.id = ol.office_id
             JOIN items i ON i.id = ol.item_id
             ORDER BY ol.year DESC, o.office_code"
        )->fetchAll();
    }

    /**
     * Configured max quantity for office/item/year, or null when none set.
     */
    public static function maxQty(int $officeId, int $itemId, int $year): ?int
    {
        $stmt = db()->prepare(
            'SELECT max_qty FROM office_limits WHERE office_id = ? AND item_id = ? AND year = ?'
        );
        $stmt->execute([$officeId, $itemId, $year]);
        $val = $stmt->fetchColumn();
        return ($val === false) ? null : (int)$val;
    }

    /**
     * Approved qty already booked by this office/item/year.
     *
     * @param int|null $excludeRequestId skip a request (e.g. the one being edited)
     */
    public static function usedQty(int $officeId, int $itemId, int $year, ?int $excludeRequestId = null): int
    {
        $sql = "SELECT COALESCE(SUM(ri.approved_qty),0)
                FROM request_items ri
                JOIN requests r ON r.id = ri.request_id
                WHERE r.office_id = :office_id
                  AND ri.item_id = :item_id
                  AND YEAR(r.created_at) = :year
                  AND r.status NOT IN ('draft','returned')";
        $params = [':office_id' => $officeId, ':item_id' => $itemId, ':year' => $year];
        if ($excludeRequestId !== null) {
            $sql .= ' AND r.id != :exclude';
            $params[':exclude'] = $excludeRequestId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Remaining available quantity for an office/item/year.
     * Returns $maxQty (unlimited) when no limit is configured.
     */
    public static function remainingQty(int $officeId, int $itemId, int $year): int
    {
        $max = self::maxQty($officeId, $itemId, $year);
        if ($max === null) {
            return PHP_INT_MAX;
        }
        return max(0, $max - self::usedQty($officeId, $itemId, $year));
    }

    /**
     * Consumption report - one row per configured limit with used / remaining.
     */
    public static function usageReport(int $year): array
    {
        $rows = db()->query(
            "SELECT ol.id, ol.office_id, ol.item_id, ol.year, ol.max_qty,
                    o.office_code, o.office_name,
                    i.item_code, i.name AS item_name, i.unit
             FROM office_limits ol
             JOIN offices o ON o.id = ol.office_id
             JOIN items i ON i.id = ol.item_id
             WHERE ol.year = " . (int)$year . "
             ORDER BY o.office_code, i.item_code"
        )->fetchAll();
        foreach ($rows as &$r) {
            $used = self::usedQty((int)$r['office_id'], (int)$r['item_id'], (int)$year);
            $r['used_qty']  = $used;
            $r['remaining'] = max(0, (int)$r['max_qty'] - $used);
        }
        return $rows;
    }

    public static function set(int $officeId, int $itemId, int $year, int $maxQty): void
    {
        $stmt = db()->prepare(
            'INSERT INTO office_limits (office_id, item_id, year, max_qty)
             VALUES (:office_id, :item_id, :year, :max_qty)
             ON DUPLICATE KEY UPDATE max_qty = :max_qty2'
        );
        $stmt->execute([
            ':office_id'=> $officeId,
            ':item_id'  => $itemId,
            ':year'     => $year,
            ':max_qty'  => $maxQty,
            ':max_qty2' => $maxQty,
        ]);
    }

    public static function delete(int $id): void
    {
        db()->prepare('DELETE FROM office_limits WHERE id = ?')->execute([$id]);
    }

    public static function allOffices(): array
    {
        return db()->query('SELECT id, office_code, office_name FROM offices ORDER BY office_code')->fetchAll();
    }

    public static function allItems(): array
    {
        return db()->query('SELECT id, item_code, name FROM items ORDER BY item_code')->fetchAll();
    }
}
