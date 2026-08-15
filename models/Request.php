<?php
/**
 * Request model - purchase requests (RIS, PPMP, PPE, ARE, BS) and the
 * multi-stage approval workflow (supply administrator -> budget -> procurement
 * -> VP), including delegation to Supply Personnel, return/resubmit, and the
 * status history / audit log.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'OfficeLimit.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'Workflow.php';

class Request
{
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            "SELECT r.*, o.office_name, o.office_code, u.full_name AS requestor_name
             FROM requests r
             LEFT JOIN offices o ON o.id = r.office_id
             LEFT JOIN users u ON u.id = r.requestor_id
             WHERE r.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function items(int $requestId): array
    {
        $stmt = db()->prepare(
            "SELECT ri.*, i.name AS item_name, i.item_code, i.unit AS item_unit
             FROM request_items ri
             JOIN items i ON i.id = ri.item_id
             WHERE ri.request_id = ?
             ORDER BY ri.id"
        );
        $stmt->execute([$requestId]);
        return $stmt->fetchAll();
    }

    /** Approval chain (one row per position in the form's fixed sequence). */
    public static function steps(int $requestId): array
    {
        $stmt = db()->prepare(
            'SELECT s.*, a.full_name AS assigned_name, d.full_name AS delegated_by_name, dt.full_name AS delegated_to_name
             FROM request_approval_steps s
             LEFT JOIN users a  ON a.id  = s.assigned_to
             LEFT JOIN users d  ON d.id  = s.delegated_by
             LEFT JOIN users dt ON dt.id = s.delegated_to
             WHERE s.request_id = ?
             ORDER BY s.step_index'
        );
        $stmt->execute([$requestId]);
        return $stmt->fetchAll();
    }

    /** Status history / audit log for a request. */
    public static function history(int $requestId): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM request_status_history WHERE request_id = ? ORDER BY id DESC'
        );
        $stmt->execute([$requestId]);
        return $stmt->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT r.*, o.office_name FROM requests r
             LEFT JOIN offices o ON o.id = r.office_id
             WHERE r.requestor_id = ? ORDER BY r.id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function countByStatus(string $status): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM requests WHERE status = ?');
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }

    public static function countInReview(): int
    {
        return self::countByStatus('in_review');
    }

    /** How many requests are currently delegated to Supply Personnel (supply_admin step). */
    public static function countDelegatedToSupply(): int
    {
        return (int)db()->query(
            "SELECT COUNT(DISTINCT s.request_id)
             FROM request_approval_steps s
             JOIN requests r ON r.id = s.request_id
             WHERE r.status = 'in_review'
               AND s.status = 'pending'
               AND s.role_code = 'supply_admin'
               AND s.delegation_status <> 'none'"
        )->fetchColumn();
    }

    private const BASE_SELECT =
        "SELECT r.*, o.office_name, o.office_code, u.full_name AS requestor_name
         FROM requests r
         LEFT JOIN offices o ON o.id = r.office_id
         LEFT JOIN users u ON u.id = r.requestor_id";

    /**
     * Requests pending at a specific step (role queue), optionally including or
     * excluding the delegated ones. Rows are decorated with an is_delegated flag.
     */
    public static function pendingForStep(string $stepRole, bool $delegated): array
    {
        $op = $delegated ? '<>' : '=';
        $sql = self::BASE_SELECT . "
             JOIN request_approval_steps s ON s.request_id = r.id
             WHERE r.status = 'in_review'
               AND s.status = 'pending'
               AND s.role_code = :role
               AND s.delegation_status " . $op . " 'none'
               AND s.step_index = (
                   SELECT MIN(s2.step_index) FROM request_approval_steps s2
                   WHERE s2.request_id = r.id AND s2.status = 'pending')
             ORDER BY COALESCE(r.submitted_at, r.created_at) ASC";
        $stmt = db()->prepare($sql);
        $stmt->execute([':role' => $stepRole]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['is_delegated'] = $delegated;
        }
        return $rows;
    }

    /** Queue for the currently logged-in user's role. */
    public static function approveQueueFor(string $userRole): array
    {
        switch ($userRole) {
            case 'admin':              return self::pendingForStep('supply_admin', false);
            case 'supply_personnel':   return self::pendingForStep('supply_admin', true);
            case 'budget_head':        return self::pendingForStep('budget_head', false);
            case 'procurement_head':   return self::pendingForStep('procurement_head', false);
            case 'vp_finance':         return self::pendingForStep('vp', false);
            default:                   return [];
        }
    }
// ========================================================================
    //  Search & filters (by status, date, requester, office, form type)
    // ========================================================================

    public static function search(array $filters, array $user): array
    {
        $where = [];
        $args  = [];
        $role  = (string)($user['role_name'] ?? '');

        if ($role === 'requestor') {
            $where[] = 'r.requestor_id = :uid';
            $args[':uid'] = (int)$user['id'];
        } elseif (in_array($role, ['budget_head', 'procurement_head', 'vp_finance'], true)) {
            $stepCode = [
                'budget_head'      => 'budget_head',
                'procurement_head' => 'procurement_head',
                'vp_finance'       => 'vp',
            ][$role];
            $where[] = "EXISTS (SELECT 1 FROM request_approval_steps s WHERE s.request_id = r.id AND s.role_code = :step_code)";
            $args[':step_code'] = $stepCode;
        }
        // admin & supply_personnel see every request.

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(r.request_number LIKE :q1 OR r.purpose LIKE :q2 OR u.full_name LIKE :q3)';
            $args[':q1'] = "%$q%";
            $args[':q2'] = "%$q%";
            $args[':q3'] = "%$q%";
        }
        $statusFilter = $filters['status'] ?? '';
        if ($statusFilter !== '') {
            $stepStatusMap = [
                'pending_supply_admin'     => 'supply_admin',
                'pending_budget_head'      => 'budget_head',
                'pending_procurement_head' => 'procurement_head',
                'pending_vp'               => 'vp',
            ];
            if (isset($stepStatusMap[$statusFilter])) {
                $where[] = "r.status = 'in_review' AND r.current_step_role = :step_role";
                $args[':step_role'] = $stepStatusMap[$statusFilter];
            } elseif (in_array($statusFilter, ['draft', 'in_review', 'returned', 'approved'], true)) {
                $where[] = 'r.status = :status';
                $args[':status'] = $statusFilter;
            }
        }
        if (!empty($filters['type'])) {
            $where[] = 'r.type = :type';
            $args[':type'] = strtoupper($filters['type']);
        }
        if (!empty($filters['office_id'])) {
            $where[] = 'r.office_id = :office_id';
            $args[':office_id'] = (int)$filters['office_id'];
        }
        if (!empty($filters['requestor_id'])) {
            $where[] = 'r.requestor_id = :requestor_id';
            $args[':requestor_id'] = (int)$filters['requestor_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(COALESCE(r.submitted_at, r.created_at)) >= :date_from';
            $args[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(COALESCE(r.submitted_at, r.created_at)) <= :date_to';
            $args[':date_to'] = $filters['date_to'];
        }

        $sql = self::BASE_SELECT;
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.id DESC LIMIT 500';
        $stmt = db()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    /**
     * Quick list status pill derived from the request-level columns.
     * The detailed view uses Workflow::requestStatusMeta (delegation-aware).
     */
    public static function listStatus(array $r): array
    {
        $status = (string)($r['status'] ?? 'draft');
        switch ($status) {
            case 'draft':    return ['label' => 'Draft', 'badge' => 'secondary'];
            case 'returned': return ['label' => 'Returned to Requester', 'badge' => 'danger'];
            case 'approved': return ['label' => 'Approved / Done', 'badge' => 'success'];
            case 'in_review':
                $role = (string)($r['current_step_role'] ?? '');
                $label = $role !== '' ? 'Pending ' . Workflow::stepLabel($role) : 'In Review';
                return ['label' => $label, 'badge' => 'warning'];
        }
        return ['label' => ucwords(str_replace('_', ' ', $status)), 'badge' => 'secondary'];
    }
// ========================================================================
    //  Creation / editing
    // ========================================================================

    public static function generateNumber(string $type): string
    {
        $year = date('Y');
        $stmt = db()->prepare("SELECT COUNT(*) FROM requests WHERE type = ? AND YEAR(created_at) = ?");
        $stmt->execute([$type, $year]);
        $count = (int)$stmt->fetchColumn() + 1;
        return strtoupper($type) . '-' . $year . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }

    public static function create(array $data): int
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO requests (request_number, office_id, requestor_id, type, status, purpose, needed_by)
             VALUES (:request_number, :office_id, :requestor_id, :type, :status, :purpose, :needed_by)'
        );
        $stmt->execute([
            ':request_number' => self::generateNumber($data['type']),
            ':office_id'      => $data['office_id'],
            ':requestor_id'   => $data['requestor_id'],
            ':type'           => $data['type'],
            ':status'         => $data['status'] ?? 'draft',
            ':purpose'        => $data['purpose'] ?? null,
            ':needed_by'      => !empty($data['needed_by']) ? $data['needed_by'] : null,
        ]);
        $requestId = (int)$pdo->lastInsertId();

        if (!empty($data['items']) && is_array($data['items'])) {
            self::insertItems($requestId, $data['items']);
        }
        return $requestId;
    }

    /** Edit a draft or a returned request (purpose / needed-by / items). */
    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE requests SET purpose = :purpose, needed_by = :needed_by,
             requestor_signature = NULL WHERE id = :id'
        );
        $stmt->execute([
            ':purpose'   => $data['purpose'] ?? null,
            ':needed_by' => !empty($data['needed_by']) ? $data['needed_by'] : null,
            ':id'        => $id,
        ]);
        if (!empty($data['items']) && is_array($data['items'])) {
            self::updateItems($id, $data['items']);
        }
    }

    private static function insertItems(int $requestId, array $items): void
    {
        $ins = db()->prepare(
            'INSERT INTO request_items (request_id, item_id, requested_qty, unit)
             VALUES (:request_id, :item_id, :requested_qty, :unit)'
        );
        foreach ($items as $ri) {
            if (empty($ri['item_id']) || (int)$ri['requested_qty'] <= 0) {
                continue;
            }
            $ins->execute([
                ':request_id'    => $requestId,
                ':item_id'       => (int)$ri['item_id'],
                ':requested_qty' => (int)$ri['requested_qty'],
                ':unit'          => $ri['unit'] ?? null,
            ]);
        }
    }

    public static function updateItems(int $requestId, array $items): void
    {
        db()->prepare('DELETE FROM request_items WHERE request_id = ?')->execute([$requestId]);
        self::insertItems($requestId, $items);
    }

    public static function setStatus(int $id, string $status): void
    {
        db()->prepare('UPDATE requests SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function delete(int $id): void
    {
        db()->prepare('DELETE FROM requests WHERE id = ?')->execute([$id]);
    }

    /**
     * Apply office limits: clamp each requested quantity to the remaining yearly
     * balance. Returns a map item_id => ['requested'=>, 'allowed'=>] for clamps.
     */
    public static function applyOfficeLimits(int $requestId, int $officeId, int $year, array $items): array
    {
        $clamped = [];
        $upd = db()->prepare('UPDATE request_items SET approved_qty = ? WHERE id = ? AND request_id = ?');

        foreach ($items as $ri) {
            $itemId    = (int)$ri['item_id'];
            $requested = (int)$ri['requested_qty'];
            $limit     = OfficeLimit::maxQty($officeId, $itemId, $year);
            $used      = OfficeLimit::usedQty($officeId, $itemId, $year, $requestId);

            if ($limit === null) {
                $allow = $requested;
            } else {
                $remaining = $limit - $used;
                $allow = max(0, min($requested, $remaining));
            }
            $upd->execute([$allow, (int)$ri['id'], $requestId]);
            if ($allow < $requested) {
                $clamped[$itemId] = ['requested' => $requested, 'allowed' => $allow];
            }
        }
        return $clamped;
    }
// ========================================================================
    //  Workflow actions
    // ========================================================================

    /**
     * Submit a request for the first time, or restart the chain on resubmission.
     * Recreates the approval steps for the form's type from the beginning.
     * Applies automatic delegation of the supply admin step when enabled.
     */
    public static function startFlow(int $id, string $type, array $user, string $signature): bool
    {
        $roles = Workflow::typeSteps($type);
        if (empty($roles)) {
            return false;
        }

        $pdo = db();
        $pdo->prepare('DELETE FROM request_approval_steps WHERE request_id = ?')->execute([$id]);

        $auto = Workflow::autoDelegateEnabled();
        $ins = $pdo->prepare(
            'INSERT INTO request_approval_steps
             (request_id, step_index, role_code, role_label, status, delegation_status, delegated_at)
             VALUES (:rid, :idx, :code, :label, :status, :deleg, :dat)'
        );
        $now = date('Y-m-d H:i:s');
        foreach ($roles as $i => $code) {
            $deleg = 'none';
            $dat   = null;
            if ($i === 0 && $code === 'supply_admin' && $auto) {
                $deleg = 'auto';
                $dat   = $now;
            }
            $ins->execute([
                ':rid'    => $id,
                ':idx'    => $i,
                ':code'   => $code,
                ':label'  => Workflow::stepLabel($code),
                ':status' => 'pending',
                ':deleg'  => $deleg,
                ':dat'    => $dat,
            ]);
        }

        $countStmt = $pdo->prepare('SELECT COALESCE(MAX(submission_count), 0) + 1 FROM requests WHERE id = ?');
        $countStmt->execute([$id]);
        $count = (int)$countStmt->fetchColumn();

        $upd = $pdo->prepare(
            'UPDATE requests SET status = ?, current_step_role = ?, submitted_at = ?,
             submission_count = ?, requestor_signature = ? WHERE id = ?'
        );
        $upd->execute(['in_review', $roles[0], $now, $count, $signature ?: null, $id]);

        self::logHistory($id, $user, 'submitted', 'Submitted');
        if ($auto) {
            self::logHistory($id, null, 'delegated', 'Delegated to Supply Personnel',
                'Automatic delegation - the Supply Administrator action was routed to Supply Personnel.');
        }
        return true;
    }

    /** Append an entry to the request status history / audit log. */
    public static function logHistory(int $id, ?array $user, string $action, ?string $label = null, ?string $remarks = null): void
    {
        $stmt = db()->prepare(
            'INSERT INTO request_status_history (request_id, actor_id, actor_name, actor_role, action, label, remarks, created_at)
             VALUES (:rid, :aid, :aname, :arole, :action, :label, :remarks, :ctime)'
        );
        $stmt->execute([
            ':rid'     => $id,
            ':aid'     => $user ? (int)$user['id'] : null,
            ':aname'   => $user ? (string)($user['full_name'] ?? '') : 'System',
            ':arole'   => $user ? (string)($user['role_name'] ?? '') : 'system',
            ':action'  => $action,
            ':label'   => $label,
            ':remarks' => $remarks,
            ':ctime'   => date('Y-m-d H:i:s'),
        ]);
    }

    /** The first pending step of the request's current chain. */
    public static function currentStep(int $id): ?array
    {
        $stmt = db()->prepare(
            "SELECT * FROM request_approval_steps
             WHERE request_id = ? AND status = 'pending' ORDER BY step_index LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
/**
     * Approve the current pending step (approver signs). Moves the request to
     * the next step in the fixed sequence, or marks it Approved / Done.
     * Returns true when the whole chain is complete.
     */
    public static function approvePendingStep(array $request, array $user, string $signature, string $remarks): bool
    {
        $step = self::currentStep((int)$request['id']);
        if (!$step) {
            return false;
        }
        $pdo = db();
        $now = date('Y-m-d H:i:s');
        $pdo->prepare(
            'UPDATE request_approval_steps SET status = ?, assigned_to = ?, signature_base64 = ?, remarks = ?, acted_at = ? WHERE id = ?'
        )->execute(['approved', (int)$user['id'], $signature ?: null, $remarks, $now, (int)$step['id']]);

        $roles    = Workflow::typeSteps((string)$request['type']);
        $nextRole = isset($roles[$step['step_index'] + 1]) ? $roles[$step['step_index'] + 1] : null;

        if ($nextRole === null) {
            $pdo->prepare("UPDATE requests SET status = 'approved', current_step_role = NULL WHERE id = ?")
                ->execute([(int)$request['id']]);
            self::logHistory((int)$request['id'], $user, 'approve', 'Approved / Done', $remarks);
            return true;
        }

        $pdo->prepare('UPDATE requests SET current_step_role = ? WHERE id = ?')
            ->execute([$nextRole, (int)$request['id']]);
        self::logHistory((int)$request['id'], $user, 'approve', 'Approved by ' . $step['role_label'], $remarks);
        return false;
    }

    /** Reject the current pending step and return the request to the requester. */
    public static function rejectPendingStep(array $request, array $user, string $remarks): void
    {
        $step = self::currentStep((int)$request['id']);
        if (!$step) {
            return;
        }
        $pdo = db();
        $now = date('Y-m-d H:i:s');
        $pdo->prepare(
            'UPDATE request_approval_steps SET status = ?, assigned_to = ?, remarks = ?, acted_at = ? WHERE id = ?'
        )->execute(['rejected', (int)$user['id'], $remarks, $now, (int)$step['id']]);

        $pdo->prepare("UPDATE requests SET status = 'returned', current_step_role = NULL WHERE id = ?")
            ->execute([(int)$request['id']]);
        self::logHistory((int)$request['id'], $user, 'reject', 'Rejected by ' . $step['role_label'], $remarks);
    }

    /** Supply Administrator manually delegates their pending step to a Supply Personnel. */
    public static function delegateSupplyStep(array $request, array $admin, ?int $personnelId): bool
    {
        $step = self::currentStep((int)$request['id']);
        if (!$step || $step['role_code'] !== 'supply_admin' || $step['delegation_status'] !== 'none'
            || ($request['status'] ?? '') !== 'in_review') {
            return false;
        }
        $pdo = db();
        $pdo->prepare(
            'UPDATE request_approval_steps SET delegation_status = ?, delegated_by = ?, delegated_to = ?, delegated_at = ? WHERE id = ?'
        )->execute(['manual', (int)$admin['id'], $personnelId, date('Y-m-d H:i:s'), (int)$step['id']]);
        self::logHistory((int)$request['id'], $admin, 'delegate', 'Delegated to Supply Personnel',
            $personnelId ? 'Supply Administrator delegated this approval to a Supply Personnel.' : 'Supply Administrator delegated this approval to the Supply Office.');
        return true;
    }
}
