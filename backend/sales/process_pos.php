<?php
// backend/sales/process_pos.php
session_start();
require_once '../../config/database.php';

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

    // 3. Bulletproof Data Extraction & Total Calculation
    foreach ($cart as &$item) {
        $productId = $item['product_id'] ?? 0;

        // Handle both new mapped format ('cart_qty') and raw format ('qty')
        $qtyNeeded = $item['cart_qty'] ?? ($item['qty'] ?? 0);
        $type = $item['type'] ?? 'pack'; // Default to pack if missing

        // Handle missing price gracefully
        if (isset($item['price'])) {
            $sellingPrice = (float)$item['price'];
        } else {
            // SAFETY FALLBACK: If frontend didn't send price, fetch it from DB
            $stmtPrice = $pdo->prepare("SELECT current_selling_price FROM products WHERE product_id = ?");
            $stmtPrice->execute([$productId]);
            $sellingPrice = (float)$stmtPrice->fetchColumn();
        }

        // Save calculated values back into the array for the insertion step
        $item['final_qty'] = $qtyNeeded;
        $item['final_price'] = $sellingPrice;
        $item['final_type'] = $type;

        $totalAmount += ($sellingPrice * $qtyNeeded);
    }

    $receiptNo = 'REC-' . time() . '-' . rand(100, 999);

    // 4. Create Sale Header
    $stmtSale = $pdo->prepare("
        INSERT INTO sales (cashier_id, sale_date, total_amount, receipt_no) 
        VALUES (?, NOW(), ?, ?)
    ");
    $stmtSale->execute([$userId, $totalAmount, $receiptNo]);
    $saleId = $pdo->lastInsertId();

    // 5. Process Each Item (Deduct Stock & Record Item)
    foreach ($cart as $item) {
        $productId = $item['product_id'];
        $qtyNeeded = $item['final_qty'];
        $sellingPrice = $item['final_price'];
        $type = $item['final_type'];

        // ROUTING LOGIC: Box -> Wholesale(1), Pack -> Shelf(3)
        $locationId = ($type === 'box') ? 1 : 3;

        // A. Get Product Cost (for profit calculation)
        $stmtProd = $pdo->prepare("SELECT current_cost_price, units_per_box FROM products WHERE product_id = ?");
        $stmtProd->execute([$productId]);
        $productData = $stmtProd->fetch();

        $baseCost = $productData['current_cost_price'] ?? 0;

        // If selling a box, the cost to the store is (piece cost * pieces in box)
        $unitCostAtSale = ($type === 'box') ? ($baseCost * ($productData['units_per_box'] ?: 1)) : $baseCost;

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
            $unitCostAtSale,
            ($sellingPrice * $qtyNeeded)
        ]);

        // C. STOCK DEDUCTION (FIFO Logic - Dynamic Location)
        $stmtBatches = $pdo->prepare("
            SELECT batch_id, quantity 
            FROM inventory_batches 
            WHERE product_id = ? AND location_id = ? AND quantity > 0 
            ORDER BY expiry_date ASC
        ");
        $stmtBatches->execute([$productId, $locationId]);
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

                // Log movement (From designated location to Sale)
                $logStmt = $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, to_location_id, quantity, movement_type, user_id, movement_date) VALUES (?, ?, NULL, ?, 'sale', ?, NOW())");
                $logStmt->execute([$productId, $locationId, $remainingToDeduct, $userId]);

                $remainingToDeduct = 0;
            } else {
                // Take everything from this batch and move to next
                $updateBatch = $pdo->prepare("UPDATE inventory_batches SET quantity = 0 WHERE batch_id = ?");
                $updateBatch->execute([$batchId]);

                // Log movement 
                $logStmt = $pdo->prepare("INSERT INTO stock_movements (product_id, from_location_id, to_location_id, quantity, movement_type, user_id, movement_date) VALUES (?, ?, NULL, ?, 'sale', ?, NOW())");
                $logStmt->execute([$productId, $locationId, $currentQty, $userId]);

                $remainingToDeduct -= $currentQty;
            }
        }

        // Safety Check: Display the correct location name in the error message
        if ($remainingToDeduct > 0) {
            $locName = ($locationId == 1) ? "Wholesale Warehouse" : "Store Shelf";
            throw new Exception("Insufficient stock in $locName for Product ID: $productId.");
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
