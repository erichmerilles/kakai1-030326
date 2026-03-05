<?php
// backend/logs/get_logs.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // 1. Get Date Filters (Default to Last 30 Days)
    $startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end'] ?? date('Y-m-d');

    // 2. Build Query
    $sql = "
        SELECT 
            sm.movement_id,
            p.name AS product_name,
            l_from.location_name AS from_location,
            l_to.location_name AS to_location,
            sm.quantity,
            sm.movement_type,
            u.username,
            sm.movement_date
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.product_id
        LEFT JOIN locations l_from ON sm.from_location_id = l_from.location_id
        LEFT JOIN locations l_to ON sm.to_location_id = l_to.location_id
        JOIN users u ON sm.user_id = u.user_id
        WHERE DATE(sm.movement_date) BETWEEN :start AND :end
    ";

    // 3. RBAC: If not Admin, restrict to own logs
    if ($role !== 'admin') {
        $sql .= " AND sm.user_id = :user_id ";
    }

    $sql .= " ORDER BY sm.movement_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDate);

    if ($role !== 'admin') {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }

    $stmt->execute();
    $logs = $stmt->fetchAll();

    echo json_encode(["status" => "success", "data" => $logs, "role" => $role]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
