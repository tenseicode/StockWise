<?php
/** @var array $user @var string $base */
$role = $user['role_name'];

// --- Active page detection --------------------------------------------------
// Strip the base URL (works for both http://stockwise.local/ and
// http://localhost/StockwiseV2/public/ ...) then map the current path to a
// sidebar "section key" so the matching nav item is highlighted.
$__uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if (isset($base) && $base !== '/') {
    $__base = rtrim($base, '/');
    if ($__base !== '' && str_starts_with($__uri, $__base)) {
        $__uri = substr($__uri, strlen($__base));
    }
}
$__uri = strtolower('/' . ltrim($__uri, '/'));
$GLOBALS['__active'] = 'dashboard';
if (str_starts_with($__uri, '/items/add')) { $GLOBALS['__active'] = 'item_add'; }
elseif (str_starts_with($__uri, '/items/print')) { $GLOBALS['__active'] = 'item_print'; }
elseif (str_starts_with($__uri, '/items')) { $GLOBALS['__active'] = 'items'; }
elseif (str_starts_with($__uri, '/transactions/stock-in')) { $GLOBALS['__active'] = 'stock_in'; }
elseif (str_starts_with($__uri, '/transactions/stock-out')) { $GLOBALS['__active'] = 'stock_out'; }
elseif (str_starts_with($__uri, '/transactions/adjust')) { $GLOBALS['__active'] = 'adjust'; }
elseif (str_starts_with($__uri, '/transactions/transfer')) { $GLOBALS['__active'] = 'transfer'; }
elseif (str_starts_with($__uri, '/transactions')) { $GLOBALS['__active'] = 'transactions'; }
elseif (str_starts_with($__uri, '/requests/new/ris')) { $GLOBALS['__active'] = 'ris'; }
elseif (str_starts_with($__uri, '/requests/new/ppmp')) { $GLOBALS['__active'] = 'ppmp'; }
elseif (str_starts_with($__uri, '/requests/new/ppe')) { $GLOBALS['__active'] = 'ppe'; }
elseif (str_starts_with($__uri, '/requests/new/are')) { $GLOBALS['__active'] = 'are'; }
elseif (str_starts_with($__uri, '/requests/new/bs')) { $GLOBALS['__active'] = 'bs'; }
elseif (str_starts_with($__uri, '/requests')) { $GLOBALS['__active'] = 'requests'; }
elseif (str_starts_with($__uri, '/approvals')) { $GLOBALS['__active'] = 'approvals'; }
elseif (str_starts_with($__uri, '/reports')) { $GLOBALS['__active'] = 'reports'; }
elseif (str_starts_with($__uri, '/admin/users')) { $GLOBALS['__active'] = 'users'; }
elseif (str_starts_with($__uri, '/admin/categories')) { $GLOBALS['__active'] = 'categories'; }
elseif (str_starts_with($__uri, '/admin/locations')) { $GLOBALS['__active'] = 'locations'; }
elseif (str_starts_with($__uri, '/admin/limits')) { $GLOBALS['__active'] = 'limits'; }
elseif (str_starts_with($__uri, '/admin/archived')) { $GLOBALS['__active'] = 'archived'; }
elseif (str_starts_with($__uri, '/settings')) { $GLOBALS['__active'] = 'settings'; }
elseif (str_starts_with($__uri, '/notifications')) { $GLOBALS['__active'] = 'notifications'; }
function __isActive(string $key): string { return $GLOBALS['__active'] === $key ? ' active' : ''; }
?>
<div class="sidebar">
    <div class="sidebar-user mb-3">
    <div class="text-white fw-semibold"><?= Security::e($user['full_name']) ?></div>
    <div class="text-white-50 small"><?= Security::e(ucwords(str_replace('_', ' ', $role))) ?></div>
  </div>
  <!-- Search bar -->
  <div class="sidebar-search">
    <input type="text" id="swSidebarSearch" class="form-control form-control-sm w-100" placeholder="Search menu..." autocomplete="off">
  </div>
  <ul class="nav flex-column" id="swSidebarNav">
    <li class="nav-item">
      <a class="nav-link<?= __isActive('dashboard') ?>" href="<?= $base ?>dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
    </li>
    <li class="nav-item">
      <a class="nav-link<?= __isActive('notifications') ?>" href="<?= $base ?>notifications"><i class="bi bi-bell"></i> Notifications</a>
    </li>
        <?php if ($role === 'admin' || $role === 'supply_personnel'): ?>
      <li class="nav-item"><span class="nav-label">Inventory</span></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('items') ?>" href="<?= $base ?>items"><i class="bi bi-box-seam"></i> Items / Inventory</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('item_add') ?>" href="<?= $base ?>items/add"><i class="bi bi-plus-square"></i> Add Item</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('item_print') ?>" href="<?= $base ?>items/print"><i class="bi bi-upc-scan"></i> Print Barcodes</a></li>
      <li class="nav-item"><span class="nav-label">Stock Transactions</span></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('transactions') ?>" href="<?= $base ?>transactions"><i class="bi bi-arrow-left-right"></i> Stock History</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('stock_in') ?>" href="<?= $base ?>transactions/stock-in"><i class="bi bi-arrow-down-circle"></i> Stock In</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('stock_out') ?>" href="<?= $base ?>transactions/stock-out"><i class="bi bi-arrow-up-circle"></i> Stock Out</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('adjust') ?>" href="<?= $base ?>transactions/adjust"><i class="bi bi-journal-plus"></i> Adjust Stock</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('transfer') ?>" href="<?= $base ?>transactions/transfer"><i class="bi bi-truck"></i> Transfer Stock</a></li>
    <?php endif; ?>

    <?php if ($role === 'requestor'): ?>
      <li class="nav-item"><span class="nav-label">My Requests &amp; Slips</span></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('requests') ?>" href="<?= $base ?>requests"><i class="bi bi-list-check"></i> My Requests</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('ris') ?>" href="<?= $base ?>requests/new/RIS"><i class="bi bi-file-earmark-text"></i> New RIS</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('ppmp') ?>" href="<?= $base ?>requests/new/PPMP"><i class="bi bi-file-earmark-spreadsheet"></i> New PPMP</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('ppe') ?>" href="<?= $base ?>requests/new/PPE"><i class="bi bi-file-earmark-check"></i> New PPE</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('are') ?>" href="<?= $base ?>requests/new/ARE"><i class="bi bi-file-earmark-check"></i> New ARE</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('bs') ?>" href="<?= $base ?>requests/new/BS"><i class="bi bi-file-earmark-richtext"></i> New Borrower's Slip</a></li>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'supply_personnel','budget_head','procurement_head','vp_finance'], true)): ?>
      <li class="nav-item"><span class="nav-label">Requests &amp; Approvals</span></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('requests') ?>" href="<?= $base ?>requests"><i class="bi bi-list-check"></i> All Requests</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('approvals') ?>" href="<?= $base ?>approvals"><i class="bi bi-pencil-square"></i> Approvals / Queue</a></li>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'supply_personnel', 'vp_finance', 'budget_head', 'procurement_head'], true)): ?>
      <li class="nav-item"><a class="nav-link<?= __isActive('reports') ?>" href="<?= $base ?>reports"><i class="bi bi-bar-chart"></i> Reports</a></li>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
      <hr class="text-white-50">
      <li class="nav-item"><span class="nav-label">Admin</span></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('users') ?>" href="<?= $base ?>admin/users"><i class="bi bi-people"></i> Users</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('categories') ?>" href="<?= $base ?>admin/categories"><i class="bi bi-tags"></i> Categories</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('locations') ?>" href="<?= $base ?>admin/locations"><i class="bi bi-geo-alt"></i> Locations</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('limits') ?>" href="<?= $base ?>admin/limits"><i class="bi bi-sliders"></i> Office Limits</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('archived') ?>" href="<?= $base ?>admin/archived"><i class="bi bi-archive"></i> Archive</a></li>
      <li class="nav-item"><a class="nav-link<?= __isActive('settings') ?>" href="<?= $base ?>settings"><i class="bi bi-gear"></i> Settings</a></li>
    <?php endif; ?>
  </ul>
</div>
</div><!-- /sidebar-col -->
<div class="col-lg-9 col-md-9 content-col p-4">