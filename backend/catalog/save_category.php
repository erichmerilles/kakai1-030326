<?php
// backend/catalog/save_category.php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Access Denied"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$code = strtoupper(trim($data['code'] ?? ''));

if (empty($name) || empty($code)) {
    echo json_encode(["status" => "error", "message" => "Category Name and Code are required."]);
    exit;
}

// Validation: Code should be short (e.g., 3-5 chars)
if (strlen($code) < 2 || strlen($code) > 10) {
    echo json_encode(["status" => "error", "message" => "Code must be between 2-10 characters."]);
    exit;
}

try {
    // Check if exists
    $stmtCheck = $pdo->prepare("SELECT count(*) FROM categories WHERE category_name = ? OR category_code = ?");
    $stmtCheck->execute([$name, $code]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(["status" => "error", "message" => "Category Name or Code already exists."]);
        exit;
    }

    // Insert
    $stmt = $pdo->prepare("INSERT INTO categories (category_name, category_code) VALUES (?, ?)");
    $stmt->execute([$name, $code]);

    echo json_encode(["status" => "success", "message" => "Category added successfully!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
}
