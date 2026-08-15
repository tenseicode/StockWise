<?php
/**
 * TransactionController - stock in / stock out and transaction history.
 * Supports webcam barcode scanning (html5-qrcode) in the views.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Item.php';

class TransactionController extends BaseController
{
    public function history(): void
    {
                $items = db()->query(
            "SELECT t.*, i.item_code, i.name AS item_name, u.full_name AS user_name, ur.role_name,
                   fl.name AS from_loc, tl.name AS to_loc
            FROM transactions t
            JOIN items i ON i.id = t.item_id
            LEFT JOIN users u ON u.id = t.user_id
            LEFT JOIN roles ur ON ur.id = u.role_id
            LEFT JOIN locations fl ON fl.id = t.from_location_id
            LEFT JOIN locations tl ON tl.id = t.to_location_id
            ORDER BY t.id DESC LIMIT 300"
        )->fetchAll();
        $this->render('transactions/history', ['transactions' => $items]);
    }

    public function stockInForm(): void
    {
        $this->render('transactions/stock_in', [
            'items' => Item::all(),
        ]);
    }

    public function stockIn(): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('transactions/stock-in', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        $itemId = (int)($d['item_id'] ?? 0);
        $qty = (int)($d['quantity'] ?? 0);

        if (!$itemId || $qty <= 0) {
            $this->redirect('transactions/stock-in', 'danger', 'Select an item and a positive quantity.');
        }
        $item = Item::find($itemId);
        if (!$item) {
            $this->redirect('transactions/stock-in', 'danger', 'Item not found.');
        }

        Item::adjustQuantity($itemId, $qty);
        $this->logTransaction($itemId, 'stock_in', $qty, $d['remarks'] ?? null);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'stock_in');
        $this->redirect('transactions', 'success', $qty . ' ' . Security::e($item['name']) . ' stock-in recorded. New qty: ' . ($item['current_qty'] + $qty));
    }

    public function stockOutForm(): void
    {
        $this->render('transactions/stock_out', [
            'items' => Item::all(),
        ]);
    }

    public function stockOut(): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('transactions/stock-out', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        $itemId = (int)($d['item_id'] ?? 0);
        $qty = (int)($d['quantity'] ?? 0);

        if (!$itemId || $qty <= 0) {
            $this->redirect('transactions/stock-out', 'danger', 'Select an item and a positive quantity.');
        }
        $item = Item::find($itemId);
        if (!$item) {
            $this->redirect('transactions/stock-out', 'danger', 'Item not found.');
        }
        if ($item['current_qty'] < $qty) {
            $this->redirect('transactions/stock-out', 'danger', 'Insufficient stock (available: ' . $item['current_qty'] . ').');
        }

        Item::adjustQuantity($itemId, -$qty);
        $this->logTransaction($itemId, 'stock_out', $qty, $d['remarks'] ?? null);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'stock_out');
        $this->redirect('transactions', 'success', $qty . ' ' . Security::e($item['name']) . ' stock-out recorded.');
    }

    public function adjustmentForm(): void
    {
        $this->render('transactions/stock_adjust', [
            'items' => Item::all(),
        ]);
    }

    /**
     * Stock adjustment: a +/- quantity change with a mandatory reason.
     */
    public function adjustment(): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('transactions/adjust', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        $itemId = (int)($d['item_id'] ?? 0);
        $qty = (int)($d['quantity'] ?? 0);
        $reason = trim((string)($d['reason'] ?? ''));

        if (!$itemId || $qty === 0) {
            $this->redirect('transactions/adjust', 'danger', 'Select an item and a non-zero quantity.');
        }
        if ($reason === '') {
            $this->redirect('transactions/adjust', 'danger', 'A reason is required for adjustments.');
        }
        $item = Item::find($itemId);
        if (!$item) {
            $this->redirect('transactions/adjust', 'danger', 'Item not found.');
        }
        if ($qty < 0 && $item['current_qty'] < abs($qty)) {
            $this->redirect('transactions/adjust', 'danger', 'Adjustment exceeds available stock (' . $item['current_qty'] . ').');
        }

        Item::adjustQuantity($itemId, $qty);
        $this->logTransaction($itemId, 'adjustment', $qty, 'ADJ #' . $this->nextReference('ADJ') . ' - ' . $reason);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'adjustment');
        $this->redirect('transactions', 'success', 'Adjustment recorded (' . ($qty > 0 ? '+' : '') . $qty . ').');
    }

    public function transferForm(): void
    {
        $this->render('transactions/transfer', [
            'items'     => Item::all(),
            'locations' => Item::allLocations(),
        ]);
    }

    /**
     * Transfer stock of an item between two locations.
     * Logs the movement and updates the item's net location.
     */
    public function transfer(): void
    {
        if (!Security::verifyCsrf()) {
            $this->redirect('transactions/transfer', 'danger', 'Invalid security token.');
        }
        $d = Security::clean($_POST);
        $itemId = (int)($d['item_id'] ?? 0);
        $toLoc  = (int)($d['to_location_id'] ?? 0);
        $qty    = (int)($d['quantity'] ?? 0);

        if (!$itemId || !$toLoc || $qty <= 0) {
            $this->redirect('transactions/transfer', 'danger', 'Select an item, a destination location, and a positive quantity.');
        }
        $item = Item::find($itemId);
        if (!$item) {
            $this->redirect('transactions/transfer', 'danger', 'Item not found.');
        }
        $fromLoc = (int)($item['location_id'] ?? 0);
        if ($fromLoc === $toLoc) {
            $this->redirect('transactions/transfer', 'danger', 'Source and destination locations are the same.');
        }
        if ($item['current_qty'] < $qty) {
            $this->redirect('transactions/transfer', 'danger', 'Cannot transfer more than available stock (' . $item['current_qty'] . ').');
        }

        // Update the item's net location to the destination.
        db()->prepare('UPDATE items SET location_id = ? WHERE id = ?')->execute([$toLoc, $itemId]);

        $ref = $this->nextReference('TRF');
        $stmt = db()->prepare(
            'INSERT INTO transactions (item_id, user_id, type, reference, from_location_id, to_location_id, quantity, remarks)
             VALUES (:i, :u, :t, :ref, :from, :to, :q, :remarks)'
        );
        $stmt->execute([
            ':i' => $itemId, ':u' => (int)$_SESSION['user_id'], ':t' => 'transfer', ':ref' => $ref,
            ':from' => $fromLoc, ':to' => $toLoc, ':q' => $qty,
            ':remarks' => $d['remarks'] ?? null,
        ]);
        AuthMiddleware::logAudit((int)$_SESSION['user_id'], 'transfer');
        $this->redirect('transactions', 'success', $qty . ' ' . Security::e($item['name']) . ' transferred to the destination location.');
    }

    private function logTransaction(int $itemId, string $type, int $qty, ?string $remarks): void
    {
        $stmt = db()->prepare(
            'INSERT INTO transactions (item_id, user_id, type, quantity, remarks)
             VALUES (:item_id, :user_id, :type, :quantity, :remarks)'
        );
        $stmt->execute([
            ':item_id'  => $itemId,
            ':user_id'  => (int)$_SESSION['user_id'],
            ':type'     => $type,
            ':quantity' => $qty,
            ':remarks'  => $remarks,
        ]);
    }

    /** Zero-padded sequence reference, e.g. TRF-2026-0001. */
    private function nextReference(string $prefix): string
    {
        $year = date('Y');
        $like = $prefix . '-' . $year . '-%';
        $stmt = db()->prepare("SELECT COUNT(*) FROM transactions WHERE remarks LIKE ? OR reference LIKE ?");
        $stmt->execute([$like, $like]);
        $count = (int)$stmt->fetchColumn() + 1;
        return $prefix . '-' . $year . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }
}
