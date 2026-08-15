<?php
/**
 * RequestController - creating, editing, submitting, resubmitting and viewing
 * requests (RIS / PPMP / PPE / ARE / BS). Only Requesters create requests;
 * the Supply Administrator and Supply Personnel can view every request but
 * cannot create them.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Request.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Item.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'NotificationHelper.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'Workflow.php';

class RequestController extends BaseController
{
    /** Page shown under /requests - requestors get their own list, others get the search/filter page. */
    public function index(): void
    {
        $user = AuthMiddleware::requireLogin();

        if (($user['role_name'] ?? '') === 'requestor') {
            $this->render('requests/my_requests', [
                'requests' => Request::forUser((int)$user['id']),
            ]);
            return;
        }

        $filters = Security::clean($_GET);
        $this->render('requests/all', [
            'requests'    => Request::search($filters, $user),
            'requestors'  => User::findByRole('requestor'),
            'offices'     => User::allOffices(),
            'filters'     => $filters,
        ]);
    }

    public function createForm(string $type): void
    {
        $type = strtoupper($type);
        if (!Workflow::isRequestType($type)) {
            Security::abort(404, 'Invalid request type.');
        }
        $user = AuthMiddleware::requireLogin();
        AuthMiddleware::requireRole(['requestor']);

        $this->render('requests/' . strtolower($type) . '_form', [
            'type'  => $type,
            'items' => Item::all(),
        ]);
    }

    public function store(string $type): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('requests', 'danger', 'Invalid security token.');
        }
        $type = strtoupper($type);
        if (!Workflow::isRequestType($type)) {
            Security::abort(404, 'Invalid request type.');
        }
        $user = AuthMiddleware::requireRole(['requestor']);

        $d = Security::clean($_POST);
        $items = $this->collectItems();
        if (empty($items)) {
            $this->redirect('requests/new/' . strtolower($type), 'danger', 'Add at least one line item.');
        }

        $requestId = Request::create([
            'office_id'    => (int)$user['office_id'],
            'requestor_id' => (int)$user['id'],
            'type'         => $type,
            'purpose'      => $d['purpose'] ?? null,
            'needed_by'    => $this->parseNeededBy($d['needed_by'] ?? null),
            'status'       => 'draft',
            'items'        => $items,
        ]);

        Request::logHistory($requestId, $user, 'created', 'Draft', 'Request draft created.');
        AuthMiddleware::logAudit((int)$user['id'], 'request_create');
        $this->redirect('requests/view/' . $requestId, 'success',
            $type . ' created as a draft. Submit it with your signature when ready.');
    }

    public function show(int $id): void
    {
        $user = AuthMiddleware::requireLogin();
        $request = Request::find($id);
        if (!$request || !$this->canView($user, $request)) {
            Security::abort(404, 'Request not found.');
        }
        $this->render('requests/view', [
            'request'  => $request,
            'steps'    => Request::steps($id),
            'history'  => Request::history($id),
            'items'    => Request::items($id),
            'meta'     => Workflow::requestStatusMeta($request, Request::steps($id)),
            'canEdit'  => $this->canEdit($user, $request),
            'canSubmit'=> $this->canSubmit($user, $request),
        ]);
    }

    public function editForm(int $id): void
    {
        $user = AuthMiddleware::requireLogin();
        $request = Request::find($id);
        if (!$request || (int)$request['requestor_id'] !== (int)$user['id']) {
            Security::abort(404, 'Request not found.');
        }
        if (!in_array($request['status'], ['draft', 'returned'], true)) {
            $this->redirect('requests/view/' . $id, 'warning', 'Only drafts and returned requests can be edited.');
        }
        $this->render('requests/' . strtolower($request['type']) . '_form', [
            'type'     => (string)$request['type'],
            'items'    => Item::all(),
            'request'  => $request,
            'existing' => Request::items($id),
        ]);
    }

    public function update(int $id): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('requests/view/' . $id, 'danger', 'Invalid security token.');
        }
        $user = AuthMiddleware::requireLogin();
        $request = Request::find($id);
        if (!$request || (int)$request['requestor_id'] !== (int)$user['id']) {
            $this->redirect('requests', 'danger', 'Request not found.');
        }
        if (!in_array($request['status'], ['draft', 'returned'], true)) {
            $this->redirect('requests/view/' . $id, 'danger', 'Only drafts and returned requests can be edited.');
        }
        $d = Security::clean($_POST);
        $items = $this->collectItems();
        if (empty($items)) {
            $this->redirect('requests/edit/' . $id, 'danger', 'Add at least one line item.');
        }
        Request::update($id, [
            'purpose'   => $d['purpose'] ?? null,
            'needed_by' => $this->parseNeededBy($d['needed_by'] ?? null),
            'items'     => $items,
        ]);
        Request::logHistory($id, $user, 'edited', 'Edited by requester');
        $this->redirect('requests/view/' . $id, 'success', 'Changes saved. Sign and submit/resubmit when ready.');
    }

    /**
     * Submit a draft, or resubmit a returned request. The chain (re)starts from
     * the beginning and the requestor's signature is required as proof.
     */
    public function submit(int $id): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('requests/view/' . $id, 'danger', 'Invalid security token.');
        }
        $user    = AuthMiddleware::requireLogin();
        $request = Request::find($id);
        if (!$request || (int)$request['requestor_id'] !== (int)$user['id']) {
            $this->redirect('requests', 'danger', 'Request not found.');
        }
        if (!in_array($request['status'], ['draft', 'returned'], true)) {
            $this->redirect('requests/view/' . $id, 'danger', 'This request is already in the approval chain.');
        }

        $d = Security::clean($_POST);
        $signature = (string)($d['requestor_signature'] ?? '');
        if ($signature === '' || !str_starts_with($signature, 'data:image')) {
            $this->redirect('requests/view/' . $id, 'danger', 'Your signature is required on submission.');
        }

        $error = null;
        if ($request['status'] === 'draft') {
            if ($request['type'] === 'RIS') {
                $stmt = db()->prepare(
                    "SELECT COUNT(*) FROM requests
                     WHERE type = 'PPMP' AND office_id = :office_id
                       AND status = 'approved' AND YEAR(created_at) = :year"
                );
                $stmt->execute([
                    ':office_id' => (int)$request['office_id'],
                    ':year'      => date('Y'),
                ]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $error = 'RIS submission requires an approved PPMP for this office/year.';
                }
            }
            if ($error === null) {
                $items   = Request::items($id);
                $clamped = Request::applyOfficeLimits($id, (int)$request['office_id'], (int)date('Y'), $items);
                if (!empty($clamped)) {
                    $error = 'Some quantities were reduced due to office limits.';
                }
            }
        }

        if ($error !== null) {
            $this->redirect('requests/view/' . $id, 'warning', $error);
            return;
        }

        $resubmit = ($request['status'] === 'returned');
        Request::startFlow($id, (string)$request['type'], $user, $signature);
        AuthMiddleware::logAudit((int)$user['id'], $resubmit ? 'request_resubmit' : 'request_submit');

        $this->notifyApprovers((int)$id, (string)$request['request_number'], (string)$request['type']);
        $this->redirect('requests/view/' . $id, 'success',
            $resubmit ? 'Request resubmitted - the approval sequence restarted from the beginning.'
                      : 'Request submitted. Awaiting the Supply Administrator.');
    }

    public function destroy(int $id): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('requests', 'danger', 'Invalid security token.');
        }
        $user = AuthMiddleware::requireLogin();
        $request = Request::find($id);
        if (!$request || (int)$request['requestor_id'] !== (int)$user['id']) {
            $this->redirect('requests', 'danger', 'Request not found.');
        }
        if ($request['status'] !== 'draft') {
            $this->redirect('requests', 'danger', 'Only draft requests can be deleted.');
        }
        Request::delete($id);
        $this->redirect('requests', 'warning', 'Draft request deleted.');
    }

    /** Notify the first approver(s) when a request is submitted/resubmitted. */
    private function notifyApprovers(int $requestId, string $requestNumber, string $type): void
    {
        $queued = (array)Request::steps($requestId);
        $first  = $queued[0] ?? null;
        if (!$first) {
            return;
        }
        if (($first['delegation_status'] ?? 'none') !== 'none') {
            // Automatic/early delegation: Supply Personnel act on the supply step.
            NotificationHelper::notifyRole(
                ['supply_personnel'],
                "Request {$requestNumber} is at the Supply step and was delegated to Supply Personnel for action.",
                'approvals'
            );
            NotificationHelper::notifyRole(
                ['admin'],
                "Request {$requestNumber} was automatically delegated because the Supply Administrator is unavailable.",
                'approvals'
            );
        } else {
            NotificationHelper::notifyRole(
                [Workflow::userRoleForStep($first['role_code'])],
                "New $type request {$requestNumber} awaits your approval.",
                'approvals'
            );
        }
    }

    /** Parse submitted line items into the array structure expected by the model. */
    private function collectItems(): array
    {
        $rawItems = $_POST['item_id'] ?? [];
        $rawQtys  = $_POST['qty'] ?? [];
        $items = [];
        foreach ((array)$rawItems as $idx => $itemId) {
            $qty = (int)($rawQtys[$idx] ?? 0);
            if (!$itemId || $qty <= 0) {
                continue;
            }
            $item = Item::find((int)$itemId);
            $items[] = [
                'item_id'       => (int)$itemId,
                'requested_qty' => $qty,
                'unit'          => $item['unit'] ?? null,
            ];
        }
        return $items;
    }

    /** Convert a datetime-local input to a DB datetime, or null. */
    private function parseNeededBy(?string $value): ?string
    {
        if (empty($value) || $value === null) {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    /** Can the current user open this request? */
    private function canView(array $user, array $request): bool
    {
        $role = (string)($user['role_name'] ?? '');
        if ($role === 'requestor') {
            return (int)$request['requestor_id'] === (int)$user['id'];
        }
        if (in_array($role, ['admin', 'supply_personnel'], true)) {
            return true;
        }
        $stepCode = [
            'budget_head'      => 'budget_head',
            'procurement_head' => 'procurement_head',
            'vp_finance'       => 'vp',
        ][$role] ?? null;
        return $stepCode !== null && Workflow::typeUsesStep((string)$request['type'], $stepCode);
    }

    private function canEdit(array $user, array $request): bool
    {
        return (int)$request['requestor_id'] === (int)$user['id']
            && in_array($request['status'], ['draft', 'returned'], true);
    }

    private function canSubmit(array $user, array $request): bool
    {
        return (int)$request['requestor_id'] === (int)$user['id']
            && in_array($request['status'], ['draft', 'returned'], true);
    }
}
