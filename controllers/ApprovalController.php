<?php
/**
 * ApprovalController - sequential approval queue with digital signatures,
 * remarks, and delegation of the Supply Administrator step to Supply Personnel.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Request.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Item.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'NotificationHelper.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'Workflow.php';

class ApprovalController extends BaseController
{
    public function index(): void
    {
        $user = AuthMiddleware::requireLogin();
        $role = (string)($user['role_name'] ?? '');
        $rows = Request::approveQueueFor($role);

        $title = match ($role) {
            'admin'             => 'Pending Supply Administrator Approval',
            'supply_personnel'  => 'Delegated to Supply Personnel',
            'budget_head'       => 'Pending Budget Head Approval',
            'procurement_head'  => 'Pending Procurement Head Approval',
            'vp_finance'        => 'Pending VP Approval',
            default             => 'Approvals',
        };

        if ($role === 'requestor') {
            $this->render('approvals/panel', ['mode' => 'none', 'title' => $title, 'requests' => [], 'columns' => []]);
            return;
        }

        $this->render('approvals/panel', [
            'mode'     => 'approve',
            'title'    => $title,
            'requests' => $rows,
            'columns'  => ['Request#', 'Type', 'Office', 'Requestor', 'Items', 'Queue Status', 'Actions'],
        ]);
    }

    public function show(int $id): void
    {
        $user = AuthMiddleware::requireLogin();
        $request = Request::find($id);
        if (!$request) {
            Security::abort(404, 'Request not found.');
        }

        $steps  = Request::steps($id);
        $action = $this->actionFor($user, $request, $steps);

        $this->render('approvals/view', [
            'request'         => $request,
            'items'           => Request::items($id),
            'steps'           => $steps,
            'history'         => Request::history($id),
            'meta'            => Workflow::requestStatusMeta($request, $steps),
            'action'          => $action,
            'canDelegate'     => Workflow::typeUsesStep((string)$request['type'], 'supply_admin'),
            'supplyPersonnel' => User::findByRole('supply_personnel'),
        ]);
    }

    /**
     * Approve (with signature) or reject (return to requester) the pending step.
     * Remarks/comments are mandatory for every action.
     */
    public function act(int $id): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('approvals', 'danger', 'Invalid security token.');
        }
        $user = AuthMiddleware::requireLogin();
        $request = Request::find($id);
        if (!$request) {
            $this->redirect('approvals', 'danger', 'Request not found.');
        }

        $steps = Request::steps($id);
        $step  = Workflow::currentPendingStep($steps);
        if (!$step || ($request['status'] ?? '') !== 'in_review') {
            $this->redirect('approvals', 'danger', 'This request is not at your approval stage.');
        }
        if (!$this->roleMatchesStep($user, $step)) {
            $this->redirect('approvals', 'danger', 'This request is not assigned to you.');
        }

        $d = Security::clean($_POST);
        $decision = (string)($d['decision'] ?? '');
        $remarks  = trim((string)($d['remarks'] ?? ''));
        if ($remarks === '') {
            $this->redirect('approvals/view/' . $id, 'warning', 'Comments/remarks are required on every approval or rejection.');
        }

        if ($decision === 'approve') {
            $signature = (string)($d['signature'] ?? '');
            if ($signature === '' || !str_starts_with($signature, 'data:image')) {
                $this->redirect('approvals/view/' . $id, 'warning', 'Your signature is required as approval proof.');
            }
            $done = Request::approvePendingStep($request, $user, $signature, $remarks);
            AuthMiddleware::logAudit((int)$user['id'], 'approve_request_' . $step['role_code']);

            if ($done) {
                NotificationHelper::notifyRequestor(
                    (int)$request['requestor_id'],
                    "Your request {$request['request_number']} is now Approved / Done.",
                    'requests/view/' . $id
                );
                NotificationHelper::checkLowStock();
                $this->redirect('approvals', 'success', 'Request approved. All signatures complete - the request is Approved / Done.');
            }
            $this->notifyNextStep($request, $step);
            $this->redirect('approvals', 'success', 'Approved. The request moved to the next approver.');
            return;
        }

        if ($decision === 'reject') {
            Request::rejectPendingStep($request, $user, $remarks);
            AuthMiddleware::logAudit((int)$user['id'], 'reject_' . $step['role_code']);
            NotificationHelper::notifyRequestor(
                (int)$request['requestor_id'],
                "Your request {$request['request_number']} was rejected by " . $step['role_label'] . " and returned to you. Remarks: {$remarks}",
                'requests/view/' . $id
            );
            $this->redirect('approvals', 'danger', 'Request rejected and returned to the requester.');
        }

        $this->redirect('approvals/view/' . $id, 'warning', 'Invalid decision.');
    }

    /**
     * Supply Administrator manually delegates their pending approval step to a
     * Supply Personnel (busy/absent). Logged in the status history.
     */
    public function delegate(int $id): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('approvals', 'danger', 'Invalid security token.');
        }
        $user = AuthMiddleware::requireLogin();
        AuthMiddleware::requireRole(['admin']); // the Supply Administrator

        $request = Request::find($id);
        if (!$request) {
            $this->redirect('approvals', 'danger', 'Request not found.');
        }

        $d = Security::clean($_POST);
        $personnelId = !empty($d['supply_personnel_id']) ? (int)$d['supply_personnel_id'] : null;

        if (!Request::delegateSupplyStep($request, $user, $personnelId)) {
            $this->redirect('approvals/view/' . $id, 'warning', 'This request is not at a delegatable supply step.');
        }
        $this->notifyDelegation($request, $personnelId);
        AuthMiddleware::logAudit((int)$user['id'], 'delegate_request_' . $id);
        $this->redirect('approvals/view/' . $id, 'success', 'Approval delegated to Supply Personnel. This delegation is logged in the request history.');
    }

    // ---- private helpers ---------------------------------------------------

    /** Which action (if any) the current user can take on this request. */
    private function actionFor(array $user, array $request, array $steps): string
    {
        if (($request['status'] ?? '') !== 'in_review') {
            return 'none';
        }
        $step = Workflow::currentPendingStep($steps);
        if (!$step) {
            return 'none';
        }
        return $this->roleMatchesStep($user, $step) ? 'approve' : 'none';
    }

    /** Can this user act on the given pending step? */
    private function roleMatchesStep(array $user, array $step): bool
    {
        $role = (string)($user['role_name'] ?? '');
        $delegated = ($step['delegation_status'] ?? 'none') !== 'none';

        if ($step['role_code'] === 'supply_admin') {
            if ($delegated) {
                return $role === 'supply_personnel';
            }
            return $role === 'admin';
        }
        return $role === Workflow::userRoleForStep($step['role_code']);
    }

    /** Notify the role responsible for the next step after an approval. */
    private function notifyNextStep(array $request, array $step): void
    {
        $roles    = Workflow::typeSteps((string)$request['type']);
        $nextRole = $roles[$step['step_index'] + 1] ?? null;
        if ($nextRole === null) {
            return;
        }
        NotificationHelper::notifyRole(
            [Workflow::userRoleForStep($nextRole)],
            "Request {$request['request_number']} approved by " . $step['role_label'] . ' - awaiting your approval.',
            'approvals'
        );
    }

    /** Notify parties involved in a delegation. */
    private function notifyDelegation(array $request, ?int $personnelId): void
    {
        $targetId = $personnelId ?: Workflow::firstSupplyPersonnelId();
        if ($targetId) {
            NotificationHelper::notify($targetId,
                "You were delegated the approval of request {$request['request_number']} by the Supply Administrator.",
                'approvals');
        } else {
            NotificationHelper::notifyRole(['supply_personnel'],
                "Request {$request['request_number']} was delegated to the Supply Office for approval.",
                'approvals');
        }
        NotificationHelper::notifyRequestor(
            (int)$request['requestor_id'],
            "The Supply Administrator delegated their approval of {$request['request_number']} to Supply Personnel.",
            'requests/view/' . $request['id']
        );
    }
}