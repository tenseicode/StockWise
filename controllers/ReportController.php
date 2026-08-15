<?php
/**
 * ReportController - inventory/stocks reports and CSV export.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'BaseController.php';
require_once BASE_PATH . 'models' . DIRECTORY_SEPARATOR . 'Item.php';

class ReportController extends BaseController
{
        public function index(): void
    {
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $_GET['to']   ?? date('Y-m-d');

        $items = Item::all();
        $low = Item::lowStock();

        // Stock movement totals per type.
        $movement = [];
        foreach (['stock_in', 'stock_out', 'adjustment', 'transfer'] as $type) {
            $stmt = db()->prepare('SELECT COALESCE(SUM(quantity),0) FROM transactions WHERE type = ?');
            $stmt->execute([$type]);
            $movement[$type] = (int)$stmt->fetchColumn();
        }

        // Daily movement for line chart (last 30 days or custom range).
        $stmt = db()->prepare(
            "SELECT DATE(created_at) as dt, type, SUM(quantity) as qty
             FROM transactions
             WHERE DATE(created_at) BETWEEN :from AND :to
             GROUP BY dt, type
             ORDER BY dt ASC"
        );
        $stmt->execute([':from' => $from, ':to' => $to]);
        $daily = $stmt->fetchAll();

        // Pivot daily data for Chart.js.
        $labels = [];
        $series = ['stock_in' => [], 'stock_out' => [], 'adjustment' => [], 'transfer' => []];
        $current = [];
        foreach ($daily as $row) {
            $d = $row['dt'];
            $labels[$d] = $d;
            $series[$row['type']][$d] = (int)$row['qty'];
        }
        ksort($labels);
        $dailyIn  = [];
        $dailyOut = [];
        foreach ($labels as $d) {
            $dailyIn[]  = $series['stock_in'][$d]  ?? 0;
            $dailyOut[] = $series['stock_out'][$d] ?? 0;
        }

        // Category breakdown for doughnut.
        $valueByCat = array_values(Item::valueByCategory());

        // Top movers (items with most transactions recently).
        $stmt = db()->prepare(
            "SELECT i.item_code, i.name, COUNT(t.id) as cnt, SUM(t.quantity) as total_qty
             FROM transactions t
             JOIN items i ON i.id = t.item_id
             WHERE DATE(t.created_at) BETWEEN :from AND :to
             GROUP BY t.item_id
             ORDER BY cnt DESC
             LIMIT 10"
        );
        $stmt->execute([':from' => $from, ':to' => $to]);
        $topMovers = $stmt->fetchAll();

        $this->render('reports/dashboard', [
            'items'       => $items,
            'low'         => $low,
            'value'       => Item::totalValue(),
            'valueByCat'  => $valueByCat,
            'movement'    => $movement,
            'recent'      => db()->query(
                "SELECT t.*, i.item_code, i.name AS item_name, u.full_name AS user_name
                 FROM transactions t
                 JOIN items i ON i.id = t.item_id
                 LEFT JOIN users u ON u.id = t.user_id
                 ORDER BY t.id DESC LIMIT 50"
            )->fetchAll(),
            'from'        => $from,
            'to'          => $to,
            'dailyLabels' => json_encode(array_values($labels), JSON_UNESCAPED_UNICODE),
            'dailyIn'     => json_encode($dailyIn, JSON_UNESCAPED_UNICODE),
            'dailyOut'    => json_encode($dailyOut, JSON_UNESCAPED_UNICODE),
            'topMovers'   => $topMovers,
        ]);
    }

    /**
     * Export inventory as CSV (downloads directly).
     */
    public function export(): void
    {
        $items = Item::all();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="stockwise-inventory-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Item Code', 'Name', 'Category', 'Location', 'Unit', 'Price', 'Qty', 'Reorder Point', 'Value']);
        foreach ($items as $i) {
            fputcsv($out, [
                $i['item_code'],
                $i['name'],
                $i['category_name'] ?? '',
                $i['location_name'] ?? '',
                $i['unit'] ?? '',
                $i['price'],
                $i['current_qty'],
                $i['reorder_point'],
                round($i['price'] * $i['current_qty'], 2),
            ]);
        }
        fclose($out);
        exit;
    }
}
