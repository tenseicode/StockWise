<?php
/**
 * ItemController - CRUD, barcode generation, barcode label printing.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Item.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Archive.php';
require_once BASE_PATH . 'helpers' . DIRECTORY_SEPARATOR . 'BarcodeGenerator.php';

class ItemController extends BaseController
{
    public function index(): void
    {
        $this->render('items/list', ['items' => Item::all()]);
    }

    /**
     * Item detail page: full item info + transaction history + a date-filtered
     * line chart of stock movement (in / out / adjustment / transfer).
     */
    public function detail(int $id): void
    {
        $item = Item::find($id);
        if (!$item) {
            Security::abort(404, 'Item not found.');
        }

        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $_GET['to']   ?? date('Y-m-d');
        $type = (string)($_GET['type'] ?? '');

        // History for this item within the range (optionally filtered by type).
        $params = [':item' => $id, ':from' => $from, ':to' => $to];
        $sql = "SELECT t.*, u.full_name AS user_name,
                       fl.name AS from_loc, tl.name AS to_loc
                FROM transactions t
                LEFT JOIN users u ON u.id = t.user_id
                LEFT JOIN locations fl ON fl.id = t.from_location_id
                LEFT JOIN locations tl ON tl.id = t.to_location_id
                WHERE t.item_id = :item AND DATE(t.created_at) BETWEEN :from AND :to";
        if ($type !== '') {
            $sql .= " AND t.type = :type";
            $params[':type'] = $type;
        }
        $sql .= " ORDER BY t.id DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $txs = $stmt->fetchAll();

        // Daily movement for the line chart (current date range).
        $ds = db()->prepare(
            "SELECT DATE(created_at) AS dt, type, SUM(quantity) AS qty
             FROM transactions
             WHERE item_id = :item AND DATE(created_at) BETWEEN :from AND :to
             GROUP BY dt, type ORDER BY dt ASC"
        );
        $ds->execute([':item' => $id, ':from' => $from, ':to' => $to]);
        $daily = $ds->fetchAll();

        $labels = [];
        $series = ['stock_in' => [], 'stock_out' => [], 'adjustment' => [], 'transfer' => []];
        foreach ($daily as $row) {
            $labels[$row['dt']] = $row['dt'];
            $series[$row['type']][$row['dt']] = (int)$row['qty'];
        }
        ksort($labels);
        $dailyIn  = [];
        $dailyOut = [];
        foreach ($labels as $d) {
            $dailyIn[]  = $series['stock_in'][$d]  ?? 0;
            $dailyOut[] = $series['stock_out'][$d] ?? 0;
        }

        $this->render('items/detail', [
            'item'          => $item,
            'transactions'  => $txs,
            'from'          => $from,
            'to'            => $to,
            'type'          => $type,
            'dailyLabels'   => json_encode(array_values($labels), JSON_UNESCAPED_UNICODE),
            'dailyIn'       => json_encode($dailyIn, JSON_UNESCAPED_UNICODE),
            'dailyOut'      => json_encode($dailyOut, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function createForm(): void
    {
        $this->render('items/add', [
            'categories' => Item::allCategories(),
            'locations'  => Item::allLocations(),
            'nextCode'   => Item::nextCode(),
        ]);
    }

    public function store(): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('items', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);

        // Generate a unique code (use provided or auto).
        $code = (!empty($d['item_code']) && !Item::codeExists($d['item_code']))
            ? $d['item_code']
            : Item::nextCode();

        // Generate barcode PNG (Windows-safe path).
        $barcodeFile = null;
        try {
            $barcodeFile = BarcodeGenerator::generate($code, UPLOAD_BARCODE_DIR);
        } catch (Exception $e) {
            $barcodeFile = null; // barcode optional; item still saved
        }

        $id = Item::create([
            'category_id'   => $d['category_id'],
            'location_id'   => $d['location_id'],
            'item_code'     => $code,
            'name'          => $d['name'],
            'unit'          => $d['unit'],
            'price'         => $d['price'] ?? 0,
            'reorder_point' => $d['reorder_point'] ?? 0,
            'current_qty'   => $d['current_qty'] ?? 0,
            'barcode_image' => $barcodeFile,
        ]);

        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'item_create');
        $this->redirect('items', 'success', 'Item "' . Security::e($d['name']) . '" added with barcode.');
    }

    public function editForm(int $id): void
    {
        $item = Item::find($id);
        if (!$item) {
            Security::abort(404, 'Item not found.');
        }
        $this->render('items/edit', [
            'item'       => $item,
            'categories' => Item::allCategories(),
            'locations'  => Item::allLocations(),
        ]);
    }

    public function update(int $id): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('items', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        $item = Item::find($id);
        if (!$item) {
            Security::abort(404, 'Item not found.');
        }

        $code = (!empty($d['item_code']) && !Item::codeExists($d['item_code'], $id))
            ? $d['item_code']
            : $item['item_code'];

        // Regenerate barcode if the code changed.
        if ($code !== $item['item_code']) {
            try {
                $d['barcode_image'] = BarcodeGenerator::generate($code, UPLOAD_BARCODE_DIR);
            } catch (Exception $e) {
                $d['barcode_image'] = $item['barcode_image'];
            }
        } else {
            $d['barcode_image'] = $item['barcode_image'];
        }
        $d['item_code'] = $code;

        Item::update($id, $d);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'item_update');
        $this->redirect('items', 'success', 'Item updated.');
    }

        public function destroy(int $id): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('items', 'danger', 'Invalid security token.');
        }
        if (!Item::delete($id)) {
            $this->redirect('items', 'warning', 'Cannot delete: this item has stock movement or request history. Archive it instead.');
        }
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'item_delete');
        $this->redirect('items', 'success', 'Item deleted.');
    }

    /**
     * Soft-archive an item instead of hard-deleting it.
     */
    public function archive(int $id): void
    {
        $this->requireManaged();
        if (!Security::verifyCsrf()) {
            $this->redirect('items', 'danger', 'Invalid security token.');
        }
        $item = Item::find($id);
        if (!$item) {
            Security::abort(404, 'Item not found.');
        }
        Item::archive($id);
        Archive::record('item', $id, ['code' => $item['item_code'], 'name' => $item['name']]);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'item_archive');
        $this->redirect('admin/archived', 'success', 'Item "' . Security::e($item['name']) . '" archived.');
    }

    /**
     * Restore an archived item back to the active inventory.
     */
    public function restore(int $id): void
    {
        $this->requireManaged();
        if (!Security::verifyCsrf()) {
            $this->redirect('admin/archived', 'danger', 'Invalid security token.');
        }
        $item = Item::find($id);
        if (!$item) {
            Security::abort(404, 'Item not found.');
        }
        Item::restore($id);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'item_restore');
        $this->redirect('admin/archived', 'success', 'Item "' . Security::e($item['name']) . '" restored.');
    }

        private function requireManaged(): void
    {
        AuthMiddleware::requireRole(['admin']);
    }

    /**
     * Stream the barcode PNG directly for inline <img> display.
     */
    public function barcodeImage(int $id): void
    {
        $item = Item::find($id);
        if (!$item) {
            Security::abort(404, 'Item not found.');
        }
        if (!empty($item['barcode_image']) && is_file(UPLOAD_BARCODE_DIR . $item['barcode_image'])) {
            header('Content-Type: image/png');
            readfile(UPLOAD_BARCODE_DIR . $item['barcode_image']);
            exit;
        }
        // Fallback: render on the fly.
        try {
            header('Content-Type: image/png');
            echo (new BarcodeGenerator())->render($item['item_code']);
        } catch (Exception $e) {
            Security::abort(500, 'Barcode generation failed.');
        }
        exit;
    }

    /**
     * Print a page of all barcode labels (standalone printable HTML page).
     */
        public function printLabels(): void
    {
        AuthMiddleware::requireLogin();
        $user = AuthMiddleware::user();
        $data = [
            'items' => Item::all(),
            'base'  => BASE_URL,
            'user'  => $user,
        ];
        extract($data, EXTR_SKIP);
        require BASE_PATH . 'views' . DIRECTORY_SEPARATOR . 'items' . DIRECTORY_SEPARATOR . 'barcode_print.php';
    }
}
