<?php
// backend/inventory/get_inventory.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// RBAC: Only Admin and Stockman can view the logistics page
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'stockman'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied: Invalid Role."]);
    exit;
}

try {
    // Fetch Wholesale (Assuming location_name = 'Wholesale')
    $stmtWholesale = $pdo->query("
        SELECT p.product_id, p.sku, p.name, b.batch_id, b.quantity AS boxes, p.units_per_box
        FROM inventory_batches b
        JOIN products p ON b.product_id = p.product_id
        JOIN locations l ON b.location_id = l.location_id
        WHERE l.location_name = 'Wholesale'
    ");
    $wholesale = $stmtWholesale->fetchAll();

    // Fetch Retail (Assuming location_name = 'Retail Warehouse')
    $stmtRetail = $pdo->query("
        SELECT p.product_id, p.sku, p.name, b.batch_id, b.quantity AS pieces
        FROM inventory_batches b
        JOIN products p ON b.product_id = p.product_id
        JOIN locations l ON b.location_id = l.location_id
        WHERE l.location_name = 'Retail Warehouse'
    ");
    $retail = $stmtRetail->fetchAll();

    // Send data to frontend
    echo json_encode([
        "status" => "success",
        "wholesale" => $wholesale,
        "retail" => $retail
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
