<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit;
}

try {
    // We join the 'categories' table to identify 'Snacks'
    // FIX: Get both Shelf (Loc 3) and Wholesale (Loc 1) quantities separately
    $stmt = $pdo->query("
        SELECT 
            p.product_id, 
            p.sku, 
            p.name, 
            c.category_name,
            p.current_selling_price AS base_pack_price, 
            p.units_per_box, 
            p.base_unit,
            COALESCE(SUM(CASE WHEN b.location_id = 3 THEN b.quantity ELSE 0 END), 0) AS shelf_qty,
            COALESCE(SUM(CASE WHEN b.location_id = 1 THEN b.quantity ELSE 0 END), 0) AS wholesale_qty
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN inventory_batches b ON p.product_id = b.product_id
        GROUP BY p.product_id, p.sku, p.name, c.category_name, p.current_selling_price, p.units_per_box, p.base_unit
        ORDER BY p.name ASC
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Apply the Business Logic
    foreach ($products as &$p) {
        $p['pack_price'] = (float) $p['base_pack_price'];

        // If it's a Chichirya/Snack, the Box Price is cheaper by ₱5 per pack
        if (strtolower($p['category_name']) === 'snacks') {
            $discounted_unit_price = $p['pack_price'] - 5;
            // Failsafe so price doesn't go below 0
            if ($discounted_unit_price < 0) $discounted_unit_price = 0;

            $p['box_price'] = $discounted_unit_price * (int)$p['units_per_box'];
        } else {
            // Normal products: Box Price is just Pack Price * Units
            $p['box_price'] = $p['pack_price'] * (int)$p['units_per_box'];
        }
    }

    echo json_encode(["status" => "success", "data" => $products]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
