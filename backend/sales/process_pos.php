<?php
// backend/sales/process_pos.php
session_start();
require_once '../../config/database.php';

// Ensure we ALWAYS return JSON, even if PHP crashes
header('Content-Type: application/json');

// 1. Auth Check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit;
}

// 2. Get Input Data
$input = json_decode(file_get_contents("php://input"), true);
if (empty($input['cart'])) {
    echo json_encode(["status" => "error", "message" => "Cart is empty."]);
    exit;
}

$cart = $input['cart'];
$userId = $_SESSION['user_id'];
$totalAmount = 0;

try {
    $pdo->beginTransaction();

    // 3. Calculate Total
    foreach ($cart as $item) {
        $totalAmount += ($item['price'] * $item['qty']);
    }

    $receiptNo = 'REC-' . time() . '-' . rand(100, 999);

    // 4. Create Sale Header
    // Using 'cashier_id' to match your database schema
    $stmtSale = $pdo->prepare("
        INSERT INTO sales (cashier_id, sale_date, total_amount, receipt_no) 
        VALUES (?, NOW(), ?, ?)
    ");
    $stmtSale->execute([$userId, $totalAmount, $receiptNo]);
    $saleId = $pdo->lastInsertId();

    // 5. Process Each Item (Deduct Stock & Record Item)
    foreach ($cart as $item) {
        $productId = $item['product_id'];
        $qtyNeeded = $item['qty'];
        $sellingPrice = $item['price'];

        // A. Get Product Cost (for profit calculation)
        $stmtProd = $pdo->prepare("SELECT current_cost_price FROM products WHERE product_id = ?");
        $stmtProd->execute([$productId]);
        $productData = $stmtProd->fetch();
        $costPrice = $productData['current_cost_price'];

        // B. Insert Sale Item Record
        $stmtItem = $pdo->prepare("
            INSERT INTO sale_items (sale_id, product_id, quantity, unit_price_at_sale, unit_cost_at_sale, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtItem->execute([
            $saleId,
            $productId,
            $qtyNeeded,
            $sellingPrice,
            $costPrice,
            ($sellingPrice * $qtyNeeded)
        ]);

        // C. STOCK DEDUCTION (FIFO Logic)
        // FIX: Changed location_id from 2 (Warehouse) to 3 (Store Shelf)
        $stmtBatches = $pdo->prepare("
            SELECT batch_id, quantity 
            FROM inventory_batches 
            WHERE product_id = ? AND location_id = 3 AND quantity > 0 
            ORDER BY expiry_date ASC
        ");
        $stmtBatches->execute([$productId]);
        $batches = $stmtBatches->fetchAll();

        $remainingToDeduct = $qtyNeeded;

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            $batchId = $batch['batch_id'];
            $currentQty = $batch['quantity'];

            if ($currentQty >= $remainingToDeduct) {
                // This batch has enough to cover the rest
                $newQty = $currentQty - $remainingToDeduct;
                $updateBatch = $pdo->prepare("UPDATE inventory_batches SET quantity = ? WHERE batch_id = ?");
                $updateBatch->execute([$newQty, $batchId]);

                // Log movement (From Shelf to Sale)
                // FIX: Changed from_location_id to 3
                $logStmt = $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, to_location_id, quantity, movement_type, user_id, movement_date) VALUES (?, 3, NULL, ?, 'sale', ?, NOW())");
                $logStmt->execute([$productId, $remainingToDeduct, $userId]);

                $remainingToDeduct = 0;
            } else {
                // Take everything from this batch and move to next
                $updateBatch = $pdo->prepare("UPDATE inventory_batches SET quantity = 0 WHERE batch_id = ?");
                $updateBatch->execute([$batchId]);

                // Log movement (From Shelf to Sale)
                // FIX: Changed from_location_id to 3
                $logStmt = $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, to_location_id, quantity, movement_type, user_id, movement_date) VALUES (?, 3, NULL, ?, 'sale', ?, NOW())");
                $logStmt->execute([$productId, $currentQty, $userId]);

                $remainingToDeduct -= $currentQty;
            }
        }

        // Safety Check: If we couldn't find enough stock on the Shelf
        if ($remainingToDeduct > 0) {
            throw new Exception("Insufficient stock on Shelf for Product ID: $productId. Please restock from Warehouse.");
        }
    }

    $pdo->commit();
    echo json_encode([
        "status" => "success",
        "message" => "Transaction Complete",
        "receipt_no" => $receiptNo
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
