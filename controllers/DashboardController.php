<?php
/**
 * DashboardController - role-aware dashboards.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Item.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Request.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'NotificationHelper.php';

class DashboardController extends BaseController
{
    public function dashboard(): void
    {
        $user = AuthMiddleware::requireLogin();
        $role = $user['role_name'];

        // Common stats.
        $stats = [
            'totalItems'      => Item::count(),
            'lowStock'        => Item::lowStock(),
            'lowStockCount'   => count(Item::lowStock()),
            'pendingRequests' => Request::countInReview(),
            'delegatedCount'  => Request::countDelegatedToSupply(),
            'todayTx'         => (int)db()->query(
                "SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE()"
            )->fetchColumn(),
            'recentItems'     => array_slice(Item::all(), 0, 8),
            'totalValue'      => Item::totalValue(),
        ];

        // Role-specific data.
        switch ($role) {
            case 'requestor':
                $stats['myRequests'] = Request::forUser((int)$user['id']);
                break;
            case 'admin':
            case 'supply_personnel':
            case 'budget_head':
            case 'procurement_head':
            case 'vp_finance':
                $stats['myPending'] = Request::approveQueueFor($role);
                $stats['valueByCategory'] = array_values(Item::valueByCategory());
                break;
        }

        $viewMap = [
            'admin'             => 'dashboard/admin',
            'supply_personnel'  => 'dashboard/supply',
            'requestor'         => 'dashboard/requestor',
            'budget_head'       => 'dashboard/budget',
            'procurement_head'  => 'dashboard/procurement',
            'vp_finance'        => 'dashboard/vp',
        ];

        $view = $viewMap[$role] ?? 'dashboard/admin';
        $this->render($view, ['stats' => $stats]);
    }
}
