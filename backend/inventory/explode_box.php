<?php
// backend/inventory/explode_box.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// RBAC: Only Admin and Stockman can perform this action
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'stockman'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied: Invalid Role."]);
    exit;
}

// Read the JSON payload sent by JavaScript
$data = json_decode(file_get_contents("php://input"), true);
$batch_id = $data['batch_id'] ?? null;
$product_id = $data['product_id'] ?? null;
$user_id = $_SESSION['user_id']; // The logged-in user doing the action

if (!$batch_id || !$product_id) {
    echo json_encode(["status" => "error", "message" => "Missing required item data."]);
    exit;
}

try {
    // START THE TRANSACTION
    $pdo->beginTransaction();

    // 1. Verify the wholesale box exists and has at least 1 in stock
    $stmtCheck = $pdo->prepare("SELECT quantity FROM inventory_batches WHERE batch_id = ? AND location_id = 1 FOR UPDATE");
    $stmtCheck->execute([$batch_id]);
    $wholesale_batch = $stmtCheck->fetch();

    if (!$wholesale_batch || $wholesale_batch['quantity'] < 1) {
        throw new Exception("Not enough wholesale boxes in stock to break down.");
    }

    // 2. Find out how many pieces are inside one box for this product
    $stmtProduct = $pdo->prepare("SELECT units_per_box FROM products WHERE product_id = ?");
    $stmtProduct->execute([$product_id]);
    $product = $stmtProduct->fetch();
    $pieces_to_add = $product['units_per_box'];

    // 3. Deduct exactly 1 box from the Wholesale batch
    $stmtDeduct = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity - 1 WHERE batch_id = ?");
    $stmtDeduct->execute([$batch_id]);

    // 4. Add the pieces to the Retail Warehouse (Location 2)
    // First, check if a retail batch already exists for this product
    $stmtCheckRetail = $pdo->prepare("SELECT batch_id FROM inventory_batches WHERE product_id = ? AND location_id = 2 LIMIT 1");
    $stmtCheckRetail->execute([$product_id]);
    $retail_batch = $stmtCheckRetail->fetch();

    if ($retail_batch) {
        // Update existing retail batch
        $stmtAdd = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity + ? WHERE batch_id = ?");
        $stmtAdd->execute([$pieces_to_add, $retail_batch['batch_id']]);
    } else {
        // If no retail batch exists yet, create one
        $stmtCreate = $pdo->prepare("INSERT INTO inventory_batches (product_id, location_id, quantity) VALUES (?, 2, ?)");
        $stmtCreate->execute([$product_id, $pieces_to_add]);
    }

    // 5. Log this action in the audit trail (stock_movements)
    $stmtLog = $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, to_location_id, quantity, movement_type, user_id) VALUES (?, 1, 2, ?, 'explode', ?)");
    $stmtLog->execute([$product_id, $pieces_to_add, $user_id]);

    // IF WE MADE IT HERE, COMMIT ALL CHANGES TO THE DATABASE
    $pdo->commit();

    echo json_encode(["status" => "success", "message" => "Successfully opened 1 box. Added $pieces_to_add pieces to retail."]);
} catch (Exception $e) {
    // IF ANYTHING FAILS, UNDO EVERYTHING
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
