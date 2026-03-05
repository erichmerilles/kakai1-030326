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
    // 0. Get Date Filters (Default to current month if not provided)
    $startDate = $_GET['start'] ?? date('Y-m-01'); // First day of this month
    $endDate = $_GET['end'] ?? date('Y-m-t');     // Last day of this month

    // 1. Calculate Total Sales & Net Profit (FILTERED BY DATE)
    $stmtTotals = $pdo->prepare("
        SELECT 
            COALESCE(SUM(si.subtotal), 0) AS total_sales,
            COALESCE(SUM((si.unit_price_at_sale - si.unit_cost_at_sale) * si.quantity), 0) AS net_profit
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.sale_id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
    ");
    $stmtTotals->execute([$startDate, $endDate]);
    $financials = $stmtTotals->fetch();

    // 2. Calculate Expiring Items (Future Projection based on CURRENT stock)
    $stmtExpiring = $pdo->query("
        SELECT COUNT(batch_id) AS expiring_count 
        FROM inventory_batches 
        WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) AND quantity > 0
    ");
    $expiring = $stmtExpiring->fetch()['expiring_count'];

    // 3. Get Stock Overview & Critical Stocks (Snapshot of CURRENT inventory)
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

    // 4. Calculate Critical Stocks Logic
    $crit_count = 0;
    foreach ($stock_overview as $item) {
        $total_loose_pieces = $item['retail_pcs'] + $item['shelf_pcs'];
        if ($total_loose_pieces <= $item['critical_level']) {
            $crit_count++;
        }
    }

    // 5. Get Top Selling Product (FILTERED BY DATE)
    // FIX: Added p.name to GROUP BY to prevent SQL Strict Mode errors
    $stmtTop = $pdo->prepare("
        SELECT p.name, SUM(si.quantity) as total_sold
        FROM sale_items si
        JOIN products p ON si.product_id = p.product_id
        JOIN sales s ON si.sale_id = s.sale_id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        GROUP BY p.product_id, p.name
        ORDER BY total_sold DESC
        LIMIT 1
    ");
    $stmtTop->execute([$startDate, $endDate]);
    $top_product = $stmtTop->fetch();

    // 6. Get Daily Sales for Charting (Analytics Feature)
    // This allows you to build a Line Graph in the frontend later
    $stmtChart = $pdo->prepare("
        SELECT DATE(sale_date) as date, SUM(total_amount) as daily_total
        FROM sales
        WHERE DATE(sale_date) BETWEEN ? AND ?
        GROUP BY DATE(sale_date)
        ORDER BY date ASC
    ");
    $stmtChart->execute([$startDate, $endDate]);
    $chart_data = $stmtChart->fetchAll();

    echo json_encode([
        "status" => "success",
        "period" => [
            "start" => $startDate,
            "end" => $endDate
        ],
        "kpis" => [
            "total_sales" => $financials['total_sales'],
            "net_profit" => $financials['net_profit'],
            "crit_stocks" => $crit_count,
            "expiring_items" => $expiring
        ],
        "top_product" => $top_product ? $top_product['name'] . " (" . $top_product['total_sold'] . " sold)" : "No sales",
        "stock_overview" => $stock_overview,
        "chart_data" => $chart_data // New data for graphs
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
