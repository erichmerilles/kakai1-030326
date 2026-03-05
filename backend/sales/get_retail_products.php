<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// RBAC: Admin and Cashier can use POS
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit;
}

try {
    // FIX 1: Changed location_id from 2 to 3 (Store Shelf)
    // FIX 2: Used LEFT JOIN so products with 0 stock still appear (optional, but good for visibility)
    // FIX 3: Used COALESCE to return 0 instead of NULL if no stock exists

    $stmt = $pdo->query("
        SELECT 
            p.product_id, 
            p.sku, 
            p.name, 
            p.current_selling_price AS price, 
            COALESCE(SUM(b.quantity), 0) AS qty
        FROM products p
        LEFT JOIN inventory_batches b ON p.product_id = b.product_id AND b.location_id = 3
        GROUP BY p.product_id, p.sku, p.name, p.current_selling_price
        ORDER BY p.name ASC
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $products]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
