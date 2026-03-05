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
    // 1. Sales vs Profit (Last 7 Days)
    $stmtTrend = $pdo->query("
        SELECT 
            DATE(s.sale_date) as sale_date, 
            SUM(si.subtotal) as revenue, 
            SUM(si.subtotal - (si.quantity * si.unit_cost_at_sale)) as profit
        FROM sales s
        JOIN sale_items si ON s.sale_id = si.sale_id
        WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(s.sale_date)
        ORDER BY sale_date ASC
    ");
    $trends = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

    // 2. Profit by Category (For Pie Chart)
    $stmtCat = $pdo->query("
        SELECT 
            COALESCE(c.category_name, 'Uncategorized') as category_name, 
            SUM(si.subtotal - (si.quantity * si.unit_cost_at_sale)) as profit
        FROM sale_items si
        JOIN products p ON si.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        GROUP BY category_name
        ORDER BY profit DESC
    ");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // 3. Detailed Product Profitability Report (ROI & Margin)
    $stmtProd = $pdo->query("
        SELECT 
            p.sku,
            p.name, 
            SUM(si.quantity) as total_sold, 
            SUM(si.subtotal) as total_revenue, 
            SUM(si.quantity * si.unit_cost_at_sale) as total_cost, 
            SUM(si.subtotal - (si.quantity * si.unit_cost_at_sale)) as total_profit
        FROM sale_items si
        JOIN products p ON si.product_id = p.product_id
        GROUP BY p.product_id
        ORDER BY total_profit DESC
    ");
    $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "trends" => $trends,
        "categories" => $categories,
        "product_performance" => $products
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Analytics Error: " . $e->getMessage()]);
}
