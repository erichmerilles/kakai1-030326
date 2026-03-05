<?php
// backend/inventory/receive_stock.php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'stockman'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$product_id = $data['product_id'];
$qty_boxes = $data['qty'];
$expiry = $data['expiry']; // Format: YYYY-MM-DD
$user_id = $_SESSION['user_id'];

if (empty($product_id) || $qty_boxes <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid Product or Quantity"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Create a new Batch in Wholesale (Location ID 1)
    $stmt = $pdo->prepare("INSERT INTO inventory_batches (product_id, location_id, quantity, expiry_date) VALUES (?, 1, ?, ?)");
    $stmt->execute([$product_id, $qty_boxes, $expiry]);

    // 2. Log Movement
    $stmtLog = $pdo->prepare("INSERT INTO stock_movements (product_id, to_location_id, quantity, movement_type, user_id) VALUES (?, 1, ?, 'receive', ?)");
    $stmtLog->execute([$product_id, $qty_boxes, $user_id]);

    $pdo->commit();
    echo json_encode(["status" => "success", "message" => "Stock Received Successfully!"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
