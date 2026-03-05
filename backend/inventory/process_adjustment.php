<?php
// backend/inventory/process_adjustment.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'stockman'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$action = $input['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    if ($action === 'receive') {
        $productId = $input['product_id'];
        $qtyBoxes = $input['qty'];
        $expiryDate = $input['expiry_date'];

        // 1. Insert into Inventory Batches (Location 1 = Wholesale Warehouse)
        $stmtBatch = $pdo->prepare("
            INSERT INTO inventory_batches (product_id, location_id, quantity, expiry_date, received_date) 
            VALUES (?, 1, ?, ?, NOW())
        ");
        $stmtBatch->execute([$productId, $qtyBoxes, $expiryDate]);

        // 2. Log Movement
        $stmtLog = $pdo->prepare("
            INSERT INTO stock_movements (product_id, to_location_id, quantity, movement_type, user_id, movement_date) 
            VALUES (?, 1, ?, 'receive', ?, NOW())
        ");
        $stmtLog->execute([$productId, $qtyBoxes, $userId]);

        $message = "Successfully received $qtyBoxes boxes.";
    } elseif ($action === 'damage') {
        $productId = $input['product_id'];
        $locationId = $input['location_id']; // 1 = Wholesale (Boxes), 3 = Shelf (Pcs)
        $qtyToDeduct = $input['qty'];
        $reason = $input['reason'] ?? 'damage'; // Optional: catch 'expired'

        // FIFO Deduction Logic
        $stmtBatches = $pdo->prepare("
            SELECT batch_id, quantity 
            FROM inventory_batches 
            WHERE product_id = ? AND location_id = ? AND quantity > 0 
            ORDER BY expiry_date ASC
        ");
        $stmtBatches->execute([$productId, $locationId]);
        $batches = $stmtBatches->fetchAll();

        $remainingToDeduct = $qtyToDeduct;

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            $batchId = $batch['batch_id'];
            $currentQty = $batch['quantity'];

            if ($currentQty >= $remainingToDeduct) {
                $newQty = $currentQty - $remainingToDeduct;
                $pdo->prepare("UPDATE inventory_batches SET quantity = ? WHERE batch_id = ?")->execute([$newQty, $batchId]);

                // Log Damage
                $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, quantity, movement_type, user_id, movement_date) VALUES (?, ?, ?, 'damage', ?, NOW())")
                    ->execute([$productId, $locationId, $remainingToDeduct, $userId]);

                $remainingToDeduct = 0;
            } else {
                $pdo->prepare("UPDATE inventory_batches SET quantity = 0 WHERE batch_id = ?")->execute([$batchId]);

                $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, quantity, movement_type, user_id, movement_date) VALUES (?, ?, ?, 'damage', ?, NOW())")
                    ->execute([$productId, $locationId, $currentQty, $userId]);

                $remainingToDeduct -= $currentQty;
            }
        }

        if ($remainingToDeduct > 0) {
            throw new Exception("Not enough stock in the selected location to mark as damaged.");
        }

        $message = "Successfully deducted $qtyToDeduct damaged items.";
    } else {
        throw new Exception("Invalid action.");
    }

    $pdo->commit();
    echo json_encode(["status" => "success", "message" => $message]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
