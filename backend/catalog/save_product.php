<?php
// backend/catalog/save_product.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Access Denied"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Basic Validation
if (empty($data['sku']) || empty($data['name']) || empty($data['price'])) {
    echo json_encode(["status" => "error", "message" => "SKU, Name, and Selling Price are required."]);
    exit;
}

try {
    // Treat empty category as NULL to prevent database errors if FK is strict
    $categoryId = !empty($data['category_id']) ? $data['category_id'] : null;

    if (isset($data['product_id']) && !empty($data['product_id'])) {
        // UPDATE EXISTING
        $stmt = $pdo->prepare("
            UPDATE products SET 
                category_id = ?, 
                sku = ?, 
                name = ?, 
                current_cost_price = ?, 
                current_selling_price = ?, 
                units_per_box = ?, 
                critical_level = ?
            WHERE product_id = ?
        ");
        $stmt->execute([
            $categoryId,
            $data['sku'],
            $data['name'],
            $data['cost'],
            $data['price'],
            $data['units_per_box'],
            $data['critical'],
            $data['product_id']
        ]);
        $msg = "Product updated successfully.";
    } else {
        // INSERT NEW
        $stmt = $pdo->prepare("
            INSERT INTO products (category_id, sku, name, current_cost_price, current_selling_price, units_per_box, critical_level)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $categoryId,
            $data['sku'],
            $data['name'],
            $data['cost'],
            $data['price'],
            $data['units_per_box'],
            $data['critical']
        ]);
        $msg = "Product created successfully.";
    }

    echo json_encode(["status" => "success", "message" => $msg]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
