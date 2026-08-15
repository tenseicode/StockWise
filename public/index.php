<?php
/** Front controller: routes the app to a [Controller, action]. */

// ---- 1. Bootstrap ----------------------------------------------------------
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'constants.php';
require_once BASE_PATH . 'config' . DIRECTORY_SEPARATOR . 'session.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'Security.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'NotificationHelper.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'BarcodeGenerator.php';
require_once BASE_PATH . 'middleware' . DIRECTORY_SEPARATOR . 'AuthMiddleware.php';

// Simple autoloader for controllers and models (no namespaces, PSR-0 style).
spl_autoload_register(function (string $class): void {
    $map = [
        BASE_PATH . 'controllers' . DIRECTORY_SEPARATOR . $class . '.php',
        BASE_PATH . 'models' . DIRECTORY_SEPARATOR . $class . '.php',
    ];
    foreach ($map as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// Apply configured timezone early so every date() call uses it.
if (class_exists('Setting')) {
    try {
        $tz = Setting::get('app_timezone', 'Asia/Manila');
        if (is_string($tz) && $tz !== '') {
            date_default_timezone_set($tz);
        }
    } catch (\Throwable $e) {
        // DB may be unavailable; fall back to PHP's default timezone.
    }
}

// ---- 2. Route table ---------------------------------------------------------
// "METHOD /path" => [Controller, action]
$routes = [
    // Auth
    'GET  /login'                     => ['AuthController', 'showLogin'],
    'POST /login'                     => ['AuthController', 'doLogin'],
    'GET  /logout'                    => ['AuthController', 'logout'],
    'GET  /register'                  => ['AuthController', 'showRegister'],
    'POST /register'                  => ['AuthController', 'doRegister'],
    'GET  /forgot'                    => ['AuthController', 'showForgot'],
    'POST /forgot'                    => ['AuthController', 'doForgot'],

    // Dashboard
    'GET  /'                          => ['DashboardController', 'dashboard'],
    'GET  /dashboard'                 => ['DashboardController', 'dashboard'],

    // Items
    'GET  /items'                     => ['ItemController', 'index'],
    'GET  /items/view/{id}'           => ['ItemController', 'detail'],
    'GET  /items/add'                 => ['ItemController', 'createForm'],
    'POST /items/add'                 => ['ItemController', 'store'],
    'GET  /items/edit/{id}'           => ['ItemController', 'editForm'],
    'POST /items/edit/{id}'           => ['ItemController', 'update'],
    'POST /items/delete/{id}'         => ['ItemController', 'destroy'],
    'GET  /items/barcode/{id}'        => ['ItemController', 'barcodeImage'],
    'GET  /items/print'               => ['ItemController', 'printLabels'],

    // Transactions (supply personnel + admin)
    'GET  /transactions'              => ['TransactionController', 'history'],
    'GET  /transactions/stock-in'     => ['TransactionController', 'stockInForm'],
    'POST /transactions/stock-in'     => ['TransactionController', 'stockIn'],
    'GET  /transactions/stock-out'    => ['TransactionController', 'stockOutForm'],
    'POST /transactions/stock-out'    => ['TransactionController', 'stockOut'],
    'GET  /transactions/adjust'       => ['TransactionController', 'adjustmentForm'],
    'POST /transactions/adjust'       => ['TransactionController', 'adjustment'],
    'GET  /transactions/transfer'     => ['TransactionController', 'transferForm'],
    'POST /transactions/transfer'     => ['TransactionController', 'transfer'],

    // Requests (requestor submits; others search/filter)
    'GET  /requests'                  => ['RequestController', 'index'],
    'GET  /requests/new/{type}'       => ['RequestController', 'createForm'],
    'POST /requests/new/{type}'       => ['RequestController', 'store'],
    'GET  /requests/view/{id}'        => ['RequestController', 'show'],
    'GET  /requests/edit/{id}'        => ['RequestController', 'editForm'],
    'POST /requests/edit/{id}'        => ['RequestController', 'update'],
    'POST /requests/submit/{id}'      => ['RequestController', 'submit'],
    'POST /requests/delete/{id}'      => ['RequestController', 'destroy'],

    // Approvals + delegation
    'GET  /approvals'                 => ['ApprovalController', 'index'],
    'GET  /approvals/view/{id}'       => ['ApprovalController', 'show'],
    'POST /approvals/act/{id}'        => ['ApprovalController', 'act'],
    'POST /approvals/delegate/{id}'   => ['ApprovalController', 'delegate'],

    // Reports
    'GET  /reports'                   => ['ReportController', 'index'],
    'GET  /reports/export'            => ['ReportController', 'export'],

    // Notifications
    'GET  /notifications'             => ['NotificationController', 'index'],
    'POST /notifications/read/{id}'   => ['NotificationController', 'markRead'],

    // Admin management
    'GET  /admin/users'               => ['AdminController', 'users'],
    'GET  /admin/users/add'           => ['AdminController', 'addUserForm'],
    'POST /admin/users/add'           => ['AdminController', 'addUser'],
    'GET  /admin/users/edit/{id}'     => ['AdminController', 'editUserForm'],
    'POST /admin/users/edit/{id}'     => ['AdminController', 'editUser'],
    'POST /admin/users/toggle/{id}'   => ['AdminController', 'toggleUser'],
    'GET  /admin/categories'          => ['AdminController', 'categories'],
    'POST /admin/categories/add'      => ['AdminController', 'addCategory'],
    'POST /admin/categories/delete/{id}' => ['AdminController', 'deleteCategory'],
    'GET  /admin/locations'           => ['AdminController', 'locations'],
    'POST /admin/locations/add'       => ['AdminController', 'addLocation'],
    'POST /admin/locations/delete/{id}' => ['AdminController', 'deleteLocation'],
    'GET  /admin/limits'              => ['AdminController', 'limits'],
    'POST /admin/limits/set'          => ['AdminController', 'setLimit'],
        'POST /admin/limits/delete/{id}'  => ['AdminController', 'deleteLimit'],

    // Settings & Archive (admin)
    'GET  /settings'                  => ['SettingController', 'index'],
    'POST /settings/save'             => ['SettingController', 'update'],
    'GET  /admin/archived'            => ['ArchiveController', 'index'],
    'POST /admin/archived/delete/{id}' => ['ArchiveController', 'deletePermanently'],
    'POST /items/archive/{id}'        => ['ItemController', 'archive'],
    'POST /items/restore/{id}'        => ['ItemController', 'restore'],
];

// ---- 3. Parse the request ------------------------------------------------
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if (BASE_URL !== '/') {
    $base = rtrim(BASE_URL, '/');
    $uri = '/' . ltrim(preg_replace('#^' . preg_quote($base, '#') . '#', '', $uri), '/');
} else {
    $uri = '/' . ltrim($uri, '/');
}
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$controller = null;
$action     = null;
$params     = [];

foreach ($routes as $pattern => $target) {
    [$pMethod, $pPath] = array_map('trim', explode(' ', $pattern, 2));
    if ($pMethod !== $method) {
        continue;
    }
    // Quote each literal segment separately and join with {id}-style captures.
    // (preg_quote escapes { } so we cannot replace them after quoting.)
    $parts = preg_split('#\{[a-z]+\}#', $pPath);
    $regex = '#^' . implode('([^/]+)', array_map(static fn($seg) => preg_quote($seg, '#'), $parts)) . '$#';
    if (preg_match($regex, $uri, $m)) {
        $controller = $target[0];
        $action     = $target[1];
        array_shift($m);
        $params = $m;
        break;
    }
}

if (!$controller) {
    Security::abort(404, 'Page not found.');
}

// ---- 4. Dispatch ----------------------------------------------------------
$instance = new $controller();
if (!method_exists($instance, $action)) {
    Security::abort(500, 'Controller action not found.');
}
$instance->{$action}(...$params);
