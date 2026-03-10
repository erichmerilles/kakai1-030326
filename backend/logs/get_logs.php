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

    // 2. Build Query - Use UNION ALL to combine Stock Movements and System Activity
    // We wrap it in a subquery so we only have to apply the DATE filter and ORDER BY once
    $sql = "
        SELECT * FROM (
            -- Query 1: Normal Stock Movements
            SELECT 
                sm.movement_id,
                p.name AS product_name,
                l_from.location_name AS from_location,
                l_to.location_name AS to_location,
                CAST(sm.quantity AS CHAR) AS quantity,
                sm.movement_type,
                u.username,
                sm.movement_date,
                sm.user_id
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.product_id
            LEFT JOIN locations l_from ON sm.from_location_id = l_from.location_id
            LEFT JOIN locations l_to ON sm.to_location_id = l_to.location_id
            JOIN users u ON sm.user_id = u.user_id
            
            UNION ALL
            
            -- Query 2: System Security & Audit Logs
            -- We alias these to match the exact columns the frontend expects
            SELECT 
                al.log_id AS movement_id,
                al.action_description AS product_name, -- Put the log message in the product column
                NULL AS from_location,
                NULL AS to_location,
                '-' AS quantity,
                'system' AS movement_type, -- This will trigger the default grey badge in logs.js
                COALESCE(u.username, 'System/Guest') AS username, -- Handles failed logins (user_id = 0)
                al.created_at AS movement_date,
                al.user_id
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
        ) AS combined_logs
        WHERE DATE(movement_date) BETWEEN :start AND :end
    ";

    // 3. RBAC: If not Admin, restrict to own logs
    if ($role !== 'admin') {
        $sql .= " AND user_id = :user_id ";
    }

    $sql .= " ORDER BY movement_date DESC";

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
