<?php
/**
 * SettingController - application settings page (admin only).
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Setting.php';

class SettingController extends BaseController
{
        public function index(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $this->render('settings/index', ['settings' => Setting::all()]);
    }

    public function update(): void
    {
        AuthMiddleware::requireRole(['admin']);
        if (!Security::verifyCsrf()) {
            $this->redirect('settings', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        Setting::updateMany([
            'app_name'              => $d['app_name'] ?? 'StockWise',
            'app_timezone'          => $d['app_timezone'] ?? 'Asia/Manila',
            'notify_low_stock'      => !empty($d['notify_low_stock']) ? '1' : '0',
            'notify_on_register'    => !empty($d['notify_on_register']) ? '1' : '0',
            'items_per_page'        => max(10, (int)($d['items_per_page'] ?? 50)),
            'default_reorder_point' => max(0, (int)($d['default_reorder_point'] ?? 0)),
            'supply_admin_delegation_enabled' => !empty($d['supply_admin_delegation_enabled']) ? '1' : '0',
        ]);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'settings_update');
        $this->redirect('settings', 'success', 'Settings saved.');
    }
}
