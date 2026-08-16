<?php
/**
 * Workflow - central definition of the Request & Approval System rules:
 * per-form approval sequences, step roles, delegation, and status labels.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Setting.php';

class Workflow
{
    /** Supported request form types. */
    public const TYPES = ['RIS', 'PPMP', 'PPE', 'ARE', 'BS'];

    public const STEPS = [
        'supply_admin'     => ['label' => 'Supply Administrator', 'user_role' => 'admin'],
        'budget_head'      => ['label' => 'Budget Head',           'user_role' => 'budget_head'],
        'procurement_head' => ['label' => 'Procurement Head',      'user_role' => 'procurement_head'],
        'vp'               => ['label' => 'VP',                    'user_role' => 'vp_finance'],
    ];

    public const FLOWS = [
        'PPMP' => ['supply_admin', 'budget_head', 'procurement_head', 'vp'],
        'PPE'  => ['supply_admin', 'budget_head', 'procurement_head', 'vp'],
        'RIS'  => ['supply_admin', 'vp'],
        'ARE'  => ['supply_admin', 'vp'],
        'BS'   => ['supply_admin'],
    ];

    /** Years the step uses COALESCE(@reg, ...). @see FLOWS */
    public static function typeSteps(string $type): array
    {
        $type = strtoupper($type);
        return self::FLOWS[$type] ?? [];
    }

    public static function stepLabel(string $roleCode): string
    {
        return self::STEPS[$roleCode]['label'] ?? ucwords(str_replace('_', ' ', $roleCode));
    }

    /** users.role_name that is responsible for a step. */
    public static function userRoleForStep(string $roleCode): string
    {
        return self::STEPS[$roleCode]['user_role'] ?? $roleCode;
    }

    public static function isRequestType(string $type): bool
    {
        return in_array(strtoupper($type), self::TYPES, true);
    }

    /** Does the given step role code appear in a type's approval chain? */
    public static function typeUsesStep(string $type, string $roleCode): bool
    {
        return in_array($roleCode, self::typeSteps($type), true);
    }

    /**
     * Should the Supply Administrator's first step be auto-delegated to Supply Personnel?
     * Auto-delegation triggers when an administrator enables it (busy), or automatically when
     * no active Supply Administrator exists at all (absent).
     */
    public static function autoDelegateEnabled(): bool
    {
        if ((string)Setting::get('supply_admin_delegation_enabled', '0') === '1') {
            return true;
        }
        return !self::hasActiveSupplyAdministrator();
    }

    /** True when at least one active Supply Administrator (admin) user exists. */
    public static function hasActiveSupplyAdministrator(): bool
    {
        $rid = (int)db()->query("SELECT id FROM roles WHERE role_name = 'admin'")->fetchColumn();
        if (!$rid) {
            return false;
        }
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE role_id = ? AND is_active = 1');
        $stmt->execute([$rid]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** First active supply personnel user id (null when nobody holds the role). */
    public static function firstSupplyPersonnelId(): ?int
    {
        $rid = (int)db()->query("SELECT id FROM roles WHERE role_name = 'supply_personnel'")->fetchColumn();
        if (!$rid) {
            return null;
        }
        $stmt = db()->prepare('SELECT id FROM users WHERE role_id = ? AND is_active = 1 ORDER BY id LIMIT 1');
        $stmt->execute([$rid]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    /**
     * Format a DB datetime as a friendly local string ('M d, Y h:i A').
     */
    public static function fmt(?string $dt): string
    {
        if (empty($dt)) {
            return '—';
        }
        $ts = strtotime($dt);
        return $ts ? date('M d, Y h:i A', $ts) : '—';
    }

    /**
     * First pending step in an approval chain (null when none is pending).
     */
    public static function currentPendingStep(array $steps): ?array
    {
        foreach ($steps as $s) {
            if (($s['status'] ?? '') === 'pending') {
                return $s;
            }
        }
        return null;
    }

    /**
     * Request-level status pill (clear labels per the requirements).
     * Returns ['label'=>string, 'badge'=>bootstrapBg, 'detail'=>?string, 'submitted_at'=>?string].
     */
    public static function requestStatusMeta(array $request, array $steps = []): array
    {
        $status = (string)($request['status'] ?? 'draft');
        $step   = self::currentPendingStep($steps);
        $out = ['label' => 'Draft', 'badge' => 'secondary', 'detail' => null];

        if ($status === 'draft') {
            $out['label'] = 'Draft';
            $out['badge'] = 'secondary';
            $out['detail'] = 'Not yet submitted.';
        } elseif ($status === 'approved') {
            $out['label'] = 'Approved / Done';
            $out['badge'] = 'success';
            $out['detail'] = null;
        } elseif ($status === 'returned') {
            $out['label'] = 'Returned to Requester';
            $out['badge'] = 'danger';
            $out['detail'] = null;
        } elseif ($status === 'in_review') {
            if (!$step) {
                $out['label'] = 'Approved / Done';
                $out['badge'] = 'success';
                return $out;
            }
            if (($step['delegation_status'] ?? 'none') !== 'none') {
                $out['label'] = 'Pending ' . self::stepLabel($step['role_code']) . ' (Delegated)';
                $out['badge'] = 'warning';
                $out['detail'] = 'Delegated to Supply Personnel for action.';
            } else {
                $out['label'] = 'Pending ' . self::stepLabel($step['role_code']);
                $out['badge'] = 'warning';
                $out['detail'] = null;
            }
        }

        $out['submitted'] = ($status === 'in_review' || $status === 'returned' || $status === 'approved');
        return $out;
    }
}