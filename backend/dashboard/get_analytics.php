<?php
// backend/dashboard/get_analytics.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit;
}

try {
    // NEW: Get Date Filters from GET request (Defaults to last 7 days)
    $startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
    $endDate = $_GET['end'] ?? date('Y-m-d');

    // 1. Sales vs Profit Trend (Filtered by Date)
    $stmtTrend = $pdo->prepare("
        SELECT 
            DATE(s.sale_date) as sale_date, 
            SUM(si.subtotal) as revenue, 
            SUM(si.subtotal - (si.quantity * si.unit_cost_at_sale)) as profit
        FROM sales s
        JOIN sale_items si ON s.sale_id = si.sale_id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        GROUP BY DATE(s.sale_date)
        ORDER BY sale_date ASC
    ");
    $stmtTrend->execute([$startDate, $endDate]);
    $trends = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

    // 2. Profit by Category (Filtered by Date for ROI Heatmap)
    $stmtCat = $pdo->prepare("
        SELECT 
            COALESCE(c.category_name, 'Uncategorized') as category_name, 
            SUM(si.subtotal) as total_revenue,
            SUM(si.quantity * si.unit_cost_at_sale) as total_cost,
            SUM(si.subtotal - (si.quantity * si.unit_cost_at_sale)) as profit
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.sale_id
        JOIN products p ON si.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        GROUP BY category_name
        ORDER BY profit DESC
    ");
    $stmtCat->execute([$startDate, $endDate]);
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // 3. Detailed Product Profitability Report (Filtered by Date)
    $stmtProd = $pdo->prepare("
        SELECT 
            p.sku,
            p.name, 
            SUM(si.quantity) as total_sold, 
            SUM(si.subtotal) as total_revenue, 
            SUM(si.quantity * si.unit_cost_at_sale) as total_cost, 
            SUM(si.subtotal - (si.quantity * si.unit_cost_at_sale)) as total_profit
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.sale_id
        JOIN products p ON si.product_id = p.product_id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        GROUP BY p.product_id
        ORDER BY total_profit DESC
    ");
    $stmtProd->execute([$startDate, $endDate]);
    $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    // 4. Low-Stock Forecasting (Predictive AI Analytics)
    // NOTE: This remains on a fixed 30-day interval to ensure velocity calculations remain accurate
    // regardless of the dates selected for the charts above.
    $stmtForecast = $pdo->query("
        SELECT 
            p.name,
            (
                COALESCE((SELECT SUM(quantity) FROM inventory_batches WHERE product_id = p.product_id AND location_id IN (2,3)), 0) + 
                COALESCE((SELECT SUM(quantity) * p.units_per_box FROM inventory_batches WHERE product_id = p.product_id AND location_id = 1), 0)
            ) as current_stock,
            COALESCE((
                SELECT SUM(si.quantity) 
                FROM sale_items si 
                JOIN sales s ON si.sale_id = s.sale_id 
                WHERE si.product_id = p.product_id 
                AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ), 0) as sold_last_30_days
        FROM products p
        HAVING sold_last_30_days > 0
    ");
    $forecasts = $stmtForecast->fetchAll(PDO::FETCH_ASSOC);

    $predictive = [];
    foreach ($forecasts as $f) {
        // Velocity: Average sold per day
        $dailyVelocity = $f['sold_last_30_days'] / 30;

        // Days Left: Current Stock / Velocity
        $daysLeft = $dailyVelocity > 0 ? floor($f['current_stock'] / $dailyVelocity) : 999;

        if ($daysLeft <= 14) { // Only forecast items running out within 2 weeks
            $predictive[] = [
                "name" => $f['name'],
                "current_stock" => (int)$f['current_stock'],
                "velocity" => round($dailyVelocity, 1),
                "days_left" => $daysLeft
            ];
        }
    }

    // Sort so items running out first are at the top
    usort($predictive, function ($a, $b) {
        return $a['days_left'] <=> $b['days_left'];
    });

    echo json_encode([
        "status" => "success",
        "trends" => $trends,
        "categories" => $categories,
        "product_performance" => $products,
        "forecast" => array_slice($predictive, 0, 5) // Return Top 5 most urgent
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Analytics Error: " . $e->getMessage()]);
}
