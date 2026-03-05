<?php
// backend/catalog/get_categories.php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
    $categories = $stmt->fetchAll();
    echo json_encode(["status" => "success", "data" => $categories]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
