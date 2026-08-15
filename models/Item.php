<?php
/**
 * Item model - inventory items with category/location joins and codes.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class Item
{
    public static function all(): array
    {
        return db()->query(
            "SELECT i.*, c.name AS category_name, l.name AS location_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN locations l ON l.id = i.location_id
             WHERE i.is_archived = 0
             ORDER BY i.item_code"
        )->fetchAll();
    }

    /** Archived items only (for the admin archive list). */
    public static function archived(): array
    {
        return db()->query(
            "SELECT i.*, c.name AS category_name, l.name AS location_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN locations l ON l.id = i.location_id
             WHERE i.is_archived = 1
             ORDER BY i.created_at DESC, i.item_code"
        )->fetchAll();
    }

    /** Soft-archive an item (and log it to the archives table). */
    public static function archive(int $id): void
    {
        db()->prepare('UPDATE items SET is_archived = 1 WHERE id = ?')->execute([$id]);
    }

    /** Restore a soft-archived item. */
    public static function restore(int $id): void
    {
        db()->prepare('UPDATE items SET is_archived = 0 WHERE id = ?')->execute([$id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            "SELECT i.*, c.name AS category_name, l.name AS location_name
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             LEFT JOIN locations l ON l.id = i.location_id
             WHERE i.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = db()->prepare('SELECT * FROM items WHERE item_code = ?');
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM items WHERE item_code = ?';
        $args = [$code];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $args[] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function allCategories(): array
    {
        return db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    }

    public static function allLocations(): array
    {
        return db()->query('SELECT id, name FROM locations ORDER BY name')->fetchAll();
    }

    public static function lowStock(): array
    {
        return db()->query(
            "SELECT i.*, c.name AS category_name FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE i.reorder_point > 0 AND i.current_qty <= i.reorder_point
             ORDER BY i.current_qty"
        )->fetchAll();
    }

    public static function count(): int
    {
        return (int)db()->query('SELECT COUNT(*) FROM items')->fetchColumn();
    }

    public static function totalValue(): float
    {
        return (float)db()->query('SELECT COALESCE(SUM(price * current_qty),0) FROM items')->fetchColumn();
    }

    public static function valueByCategory(): array
    {
        return db()->query(
            "SELECT COALESCE(c.name,'Uncategorized') AS label,
                    SUM(i.price * i.current_qty) AS value
             FROM items i
             LEFT JOIN categories c ON c.id = i.category_id
             GROUP BY c.id, c.name"
        )->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO items (category_id, location_id, item_code, name, unit, price, reorder_point, current_qty, barcode_image)
             VALUES (:category_id, :location_id, :item_code, :name, :unit, :price, :reorder_point, :current_qty, :barcode_image)'
        );
        $stmt->execute([
            ':category_id'   => $data['category_id'] ?? null,
            ':location_id'   => $data['location_id'] ?? null,
            ':item_code'     => $data['item_code'],
            ':name'          => $data['name'],
            ':unit'          => $data['unit'] ?? null,
            ':price'         => $data['price'] ?? 0,
            ':reorder_point' => $data['reorder_point'] ?? 0,
            ':current_qty'   => $data['current_qty'] ?? 0,
            ':barcode_image' => $data['barcode_image'] ?? null,
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE items SET category_id = :category_id, location_id = :location_id,
             item_code = :item_code, name = :name, unit = :unit, price = :price,
             reorder_point = :reorder_point, current_qty = :current_qty, barcode_image = :barcode_image
             WHERE id = :id'
        );
        $stmt->execute([
            ':category_id'   => $data['category_id'] ?? null,
            ':location_id'   => $data['location_id'] ?? null,
            ':item_code'     => $data['item_code'],
            ':name'          => $data['name'],
            ':unit'          => $data['unit'] ?? null,
            ':price'         => $data['price'] ?? 0,
            ':reorder_point' => $data['reorder_point'] ?? 0,
            ':current_qty'   => $data['current_qty'] ?? 0,
            ':barcode_image' => $data['barcode_image'] ?? null,
            ':id'            => $id,
        ]);
    }

    public static function adjustQuantity(int $id, int $delta): void
    {
        $stmt = db()->prepare('UPDATE items SET current_qty = current_qty + ? WHERE id = ?');
        $stmt->execute([$delta, $id]);
    }

        /** Delete an item unless it still has stock/request history (returns false then). */
    public static function delete(int $id): bool
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Config-only references (per-office consumption limits) go with the item.
            $pdo->prepare('DELETE FROM office_limits WHERE item_id = ?')->execute([$id]);

            $check = function (string $table) use ($pdo, $id): bool {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE item_id = ?");
                $stmt->execute([$id]);
                return (int)$stmt->fetchColumn() > 0;
            };
            if ($check('transactions') || $check('request_items')) {
                $pdo->rollBack();
                return false;
            }

            $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    /** Generate the next sequential item code: TCGC-ITEM-000N */
    public static function nextCode(): string
    {
        $stmt = db()->query("SELECT COUNT(*) FROM items");
        $count = (int)$stmt->fetchColumn();
        $num = $count + 1;
        // Ensure uniqueness in case of deletions.
        do {
            $code = 'TCGC-ITEM-' . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
            $num++;
        } while (self::codeExists($code));
        return $code;
    }
}
