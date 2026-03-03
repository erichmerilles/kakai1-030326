<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// RBAC: Only Admin can access the BI Dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit;
}

try {
    // 1. Calculate Total Sales & Net Profit
    // FIX: Removed the buggy, redundant $stmtFinancials query here.
    $stmtTotals = $pdo->query("
        SELECT 
            COALESCE(SUM(subtotal), 0) AS total_sales,
            COALESCE(SUM((unit_price_at_sale - unit_cost_at_sale) * quantity), 0) AS net_profit
        FROM sale_items
    ");
    $financials = $stmtTotals->fetch();

    // 2. Calculate Expiring Items (within 30 days)
    $stmtExpiring = $pdo->query("
        SELECT COUNT(batch_id) AS expiring_count 
        FROM inventory_batches 
        WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) AND quantity > 0
    ");
    $expiring = $stmtExpiring->fetch()['expiring_count'];

    // 3. Get Stock Overview & Critical Stocks
    $stmtStock = $pdo->query("
        SELECT 
            p.product_id, 
            p.name, 
            p.critical_level,
            COALESCE(SUM(CASE WHEN l.location_name = 'Wholesale' THEN b.quantity ELSE 0 END), 0) AS wholesale_boxes,
            COALESCE(SUM(CASE WHEN l.location_name = 'Retail Warehouse' THEN b.quantity ELSE 0 END), 0) AS retail_pcs,
            COALESCE(SUM(CASE WHEN l.location_name = 'Store Shelf' THEN b.quantity ELSE 0 END), 0) AS shelf_pcs
        FROM products p
        LEFT JOIN inventory_batches b ON p.product_id = b.product_id
        LEFT JOIN locations l ON b.location_id = l.location_id
        GROUP BY p.product_id, p.name, p.critical_level
    ");
    $stock_overview = $stmtStock->fetchAll();

    // 4. Calculate Critical Stocks
    $crit_count = 0;
    foreach ($stock_overview as $item) {
        $total_loose_pieces = $item['retail_pcs'] + $item['shelf_pcs'];
        if ($total_loose_pieces <= $item['critical_level']) {
            $crit_count++;
        }
    }

    // 5. Get Top Selling Product
    $stmtTop = $pdo->query("
        SELECT p.name, SUM(si.quantity) as total_sold
        FROM sale_items si
        JOIN products p ON si.product_id = p.product_id
        GROUP BY p.product_id
        ORDER BY total_sold DESC
        LIMIT 1
    ");
    $top_product = $stmtTop->fetch();

    echo json_encode([
        "status" => "success",
        "kpis" => [
            "total_sales" => $financials['total_sales'],
            "net_profit" => $financials['net_profit'],
            "crit_stocks" => $crit_count,
            "expiring_items" => $expiring
        ],
        "top_product" => $top_product ? $top_product['name'] . " (" . $top_product['total_sold'] . " sold)" : "No sales yet",
        "stock_overview" => $stock_overview
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
