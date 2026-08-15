<?php
/**
 * User model - data access for users, roles, and offices.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class User
{
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            "SELECT u.*, r.role_name FROM users u
             JOIN roles r ON r.id = u.role_id WHERE u.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(): array
    {
        return db()->query(
            "SELECT u.*, r.role_name, o.office_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN offices o ON o.id = u.office_id
             ORDER BY u.id"
        )->fetchAll();
    }

    public static function allRoles(): array
    {
        return db()->query('SELECT id, role_name FROM roles ORDER BY id')->fetchAll();
    }

    public static function allOffices(): array
    {
        return db()->query('SELECT id, office_code, office_name FROM offices ORDER BY office_code')->fetchAll();
    }

    public static function findRoleId(string $roleName): ?int
    {
        $stmt = db()->prepare('SELECT id FROM roles WHERE role_name = ?');
        $stmt->execute([$roleName]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    public static function findByRole(string $roleName): array
    {
        $rid = self::findRoleId($roleName);
        if (!$rid) {
            return [];
        }
        $stmt = db()->prepare('SELECT * FROM users WHERE role_id = ? AND is_active = 1');
        $stmt->execute([$rid]);
        return $stmt->fetchAll();
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $args = [$email];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $args[] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * True when an active Requestor already exists for the given office
     * (the spec restricts to one requester per office).
     */
    public static function requestorExistsForOffice(int $officeId, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE r.role_name = 'requestor' AND u.office_id = ? AND u.is_active = 1";
        $args = [$officeId];
        if ($excludeId !== null) {
            $sql .= ' AND u.id != ?';
            $args[] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO users (office_id, role_id, email, password_hash, full_name, is_active)
             VALUES (:office_id, :role_id, :email, :password_hash, :full_name, :is_active)'
        );
        $stmt->execute([
            ':office_id'     => $data['office_id'] ?? null,
            ':role_id'       => $data['role_id'],
            ':email'         => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':full_name'     => $data['full_name'],
            ':is_active'     => $data['is_active'] ?? 1,
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE users SET office_id = :office_id, role_id = :role_id, email = :email,
                full_name = :full_name, is_active = :is_active';
        $params = [
            ':office_id' => $data['office_id'] ?? null,
            ':role_id'   => $data['role_id'],
            ':email'     => $data['email'],
            ':full_name' => $data['full_name'],
            ':is_active' => $data['is_active'] ?? 1,
        ];
        if (!empty($data['password'])) {
            $sql .= ', password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $sql .= ' WHERE id = :id';
        $params[':id'] = $id;
        db()->prepare($sql)->execute($params);
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = db()->prepare('UPDATE users SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }
}
