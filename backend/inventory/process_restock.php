<?php
// backend/inventory/process_restock.php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'stockman'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$productId = $input['product_id'];
$qtyToMove = intval($input['qty']);
$userId = $_SESSION['user_id'];

if ($qtyToMove <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid quantity."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. DEDUCT FROM: Retail Warehouse (Location ID 2)
    // We use FIFO (First-In, First-Out) to clear old stock first
    $stmt = $pdo->prepare("
        SELECT batch_id, quantity, expiry_date 
        FROM inventory_batches 
        WHERE product_id = ? AND location_id = 2 AND quantity > 0 
        ORDER BY expiry_date ASC
    ");
    $stmt->execute([$productId]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total available in Retail Warehouse
    $totalAvailable = 0;
    foreach ($batches as $b) $totalAvailable += $b['quantity'];

    if ($totalAvailable < $qtyToMove) {
        throw new Exception("Insufficient stock in Retail Warehouse (Loc 2). Available: $totalAvailable");
    }

    $remaining = $qtyToMove;

    foreach ($batches as $batch) {
        if ($remaining <= 0) break;

        $take = min($batch['quantity'], $remaining);

        // A. Deduct from Retail Warehouse Batch
        $newQty = $batch['quantity'] - $take;
        if ($newQty == 0) {
            $pdo->prepare("UPDATE inventory_batches SET quantity = 0 WHERE batch_id = ?")->execute([$batch['batch_id']]);
        } else {
            $pdo->prepare("UPDATE inventory_batches SET quantity = ? WHERE batch_id = ?")->execute([$newQty, $batch['batch_id']]);
        }

        // B. ADD TO: Store Shelf (Location ID 3)
        // Check if a batch with same expiry exists on shelf to merge, or create new
        $stmtShelf = $pdo->prepare("SELECT batch_id FROM inventory_batches WHERE product_id = ? AND location_id = 3 AND expiry_date = ? LIMIT 1");
        $stmtShelf->execute([$productId, $batch['expiry_date']]);
        $shelfBatch = $stmtShelf->fetch();

        if ($shelfBatch) {
            // Merge into existing shelf batch
            $pdo->prepare("UPDATE inventory_batches SET quantity = quantity + ? WHERE batch_id = ?")->execute([$take, $shelfBatch['batch_id']]);
        } else {
            // Create New Batch on Shelf
            $pdo->prepare("INSERT INTO inventory_batches (product_id, location_id, quantity, expiry_date, received_date) VALUES (?, 3, ?, ?, NOW())")
                ->execute([$productId, $take, $batch['expiry_date']]);
        }

        // C. Log Movement (Retail Warehouse -> Shelf)
        $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, to_location_id, quantity, movement_type, user_id, movement_date) VALUES (?, 2, 3, ?, 'transfer', ?, NOW())")
            ->execute([$productId, $take, $userId]);

        $remaining -= $take;
    }

    $pdo->commit();
    echo json_encode(["status" => "success", "message" => "Successfully moved $qtyToMove items to Shelf."]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
