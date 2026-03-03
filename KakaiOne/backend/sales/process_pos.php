<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$cart = $data['cart'] ?? [];
$user_id = $_SESSION['user_id'];

if (empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Cart is empty."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Generate a unique receipt number
    $receipt_no = 'REC-' . strtoupper(uniqid());
    $grand_total = 0;

    // 2. Insert main Sales record (we will update total_amount later)
    $stmtSale = $pdo->prepare("INSERT INTO sales (receipt_no, cashier_id, total_amount) VALUES (?, ?, 0)");
    $stmtSale->execute([$receipt_no, $user_id]);
    $sale_id = $pdo->lastInsertId();

    $stmtProduct = $pdo->prepare("SELECT current_cost_price, current_selling_price FROM products WHERE product_id = ?");
    $stmtDeductStock = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE batch_id = ? AND quantity >= ?");
    $stmtSaleItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_cost_at_sale, unit_price_at_sale, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtLog = $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, quantity, movement_type, user_id) VALUES (?, 2, ?, 'sale', ?)");

    foreach ($cart as $item) {
        // Fetch current pricing to lock in profit data
        $stmtProduct->execute([$item['product_id']]);
        $prodData = $stmtProduct->fetch();

        if (!$prodData) throw new Exception("Product ID {$item['product_id']} not found.");

        $cost = $prodData['current_cost_price'];
        $price = $prodData['current_selling_price'];
        $subtotal = $price * $item['qty'];
        $grand_total += $subtotal;

        // Deduct from inventory
        $stmtDeductStock->execute([$item['qty'], $item['batch_id'], $item['qty']]);
        if ($stmtDeductStock->rowCount() === 0) {
            throw new Exception("Not enough stock for {$item['name']}. Transaction aborted.");
        }

        // Record the sale item
        $stmtSaleItem->execute([$sale_id, $item['product_id'], $item['qty'], $cost, $price, $subtotal]);

        // Audit Trail
        $stmtLog->execute([$item['product_id'], $item['qty'], $user_id]);
    }

    // 3. Update the final total on the main sale record
    $stmtUpdateTotal = $pdo->prepare("UPDATE sales SET total_amount = ? WHERE sale_id = ?");
    $stmtUpdateTotal->execute([$grand_total, $sale_id]);

    $pdo->commit();
    echo json_encode(["status" => "success", "message" => "Sale completed! Receipt: $receipt_no"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
