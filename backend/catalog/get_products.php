<?php
// backend/catalog/get_products.php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
    $products = $stmt->fetchAll();
    echo json_encode(["status" => "success", "data" => $products]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
