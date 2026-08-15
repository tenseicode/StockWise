<?php
/**
 * Setting model - key/value application settings (used by the Settings page).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class Setting
{
    /** Defaults per key; also used to seed fresh rows. */
    public const DEFAULTS = [
        'app_name'                     => 'StockWise',
        'app_timezone'                 => 'Asia/Manila',
        'notify_low_stock'             => '1',
        'notify_on_register'           => '1',
        'items_per_page'               => '50',
        'default_reorder_point'        => '5',
        'supply_admin_delegation_enabled' => '0',
    ];

    /**
     * All settings as an associative key => value map (with defaults filled in).
     */
    public static function all(): array
    {
        $rows  = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $map   = self::DEFAULTS;
        foreach ($rows as $r) {
            $map[$r['setting_key']] = $r['setting_value'];
        }
        return $map;
    }

    /**
     * Get a single setting value with a fallback default.
     */
    public static function get(string $key, $default = null)
    {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        if ($val === false) {
            return $default ?? (self::DEFAULTS[$key] ?? null);
        }
        return $val;
    }

    /**
     * Set a single setting (insert-or-update).
     */
    public static function set(string $key, string $value): void
    {
        $stmt = db()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }

    /**
     * Bulk update from a posted array (only known/default keys are allowed).
     */
    public static function updateMany(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, self::DEFAULTS)) {
                continue; // ignore unknown keys
            }
            self::set($key, (string)$value);
        }
    }
}
