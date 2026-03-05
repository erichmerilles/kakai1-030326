<?php
// backend/catalog/get_next_sku.php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['category_id'])) {
    echo json_encode(["status" => "error", "message" => "Category ID required"]);
    exit;
}

$categoryId = $_GET['category_id'];

try {
    // 1. Get Category Code from DB (Dynamic!)
    $stmt = $pdo->prepare("SELECT category_code FROM categories WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    if (!$category) {
        echo json_encode(["status" => "error", "message" => "Category not found"]);
        exit;
    }

    $prefix = strtoupper($category['category_code']); // e.g., "SNK"

    // 2. Find the highest existing SKU with this prefix
    $stmtSku = $pdo->prepare("
        SELECT sku FROM products 
        WHERE sku LIKE ? 
        ORDER BY LENGTH(sku) DESC, sku DESC 
        LIMIT 1
    ");
    $stmtSku->execute(["$prefix-%"]);
    $lastSku = $stmtSku->fetchColumn();

    // 3. Increment
    $nextNum = 1;
    if ($lastSku) {
        $parts = explode('-', $lastSku);
        if (isset($parts[1]) && is_numeric($parts[1])) {
            $nextNum = intval($parts[1]) + 1;
        }
    }

    // 4. Format: PREFIX-00X
    $newSku = $prefix . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

    echo json_encode(["status" => "success", "sku" => $newSku]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
