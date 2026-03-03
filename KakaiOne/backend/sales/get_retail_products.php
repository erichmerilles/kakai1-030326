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
    // Fetch products that have stock in the Retail location (Location ID 2)
    // You can expand this to include Store Shelf (Location ID 3) later
    $stmt = $pdo->query("
        SELECT p.product_id, p.sku, p.name, p.current_selling_price AS price, b.batch_id, b.quantity AS stock
        FROM inventory_batches b
        JOIN products p ON b.product_id = p.product_id
        WHERE b.location_id = 2 AND b.quantity > 0
    ");
    $products = $stmt->fetchAll();

    echo json_encode(["status" => "success", "data" => $products]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
