<?php
// backend/inventory/get_shelf_status.php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied"]);
    exit;
}

try {
    // We utilize specific location IDs based on your database:
    // 1 = Wholesale
    // 2 = Retail Warehouse
    // 3 = Store Shelf
    $sql = "
        SELECT 
            p.product_id, 
            p.sku, 
            p.name, 
            COALESCE(SUM(CASE WHEN b.location_id = 1 THEN b.quantity ELSE 0 END), 0) as wholesale_qty,
            COALESCE(SUM(CASE WHEN b.location_id = 2 THEN b.quantity ELSE 0 END), 0) as retail_warehouse_qty,
            COALESCE(SUM(CASE WHEN b.location_id = 3 THEN b.quantity ELSE 0 END), 0) as shelf_qty
        FROM products p
        LEFT JOIN inventory_batches b ON p.product_id = b.product_id
        GROUP BY p.product_id, p.sku, p.name
        ORDER BY p.name ASC
    ";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
