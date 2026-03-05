<?php
// backend/dashboard/get_notifications.php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

try {
    $notifications = [];
    $unreadCount = 0;

    // 1. Check for Critical Stocks (Total Loose Pieces < Critical Level)
    $stmtStock = $pdo->query("
        SELECT 
            p.name, 
            p.critical_level,
            COALESCE(SUM(CASE WHEN l.location_name IN ('Retail Warehouse', 'Store Shelf') THEN b.quantity ELSE 0 END), 0) AS total_loose
        FROM products p
        LEFT JOIN inventory_batches b ON p.product_id = b.product_id
        LEFT JOIN locations l ON b.location_id = l.location_id
        GROUP BY p.product_id, p.name, p.critical_level
        HAVING total_loose <= p.critical_level
    ");

    $criticalItems = $stmtStock->fetchAll();
    foreach ($criticalItems as $item) {
        $notifications[] = [
            "type" => "warning",
            "icon" => "bi-exclamation-triangle",
            "title" => "Low Stock Alert",
            "message" => "{$item['name']} is at critical level ({$item['total_loose']} left)."
        ];
        $unreadCount++;
    }

    // 2. Check for Expiring Items (Within 30 Days)
    $stmtExpiry = $pdo->query("
        SELECT p.name, b.quantity, b.expiry_date, DATEDIFF(b.expiry_date, CURRENT_DATE) as days_left
        FROM inventory_batches b
        JOIN products p ON b.product_id = p.product_id
        WHERE b.expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) AND b.quantity > 0
        ORDER BY b.expiry_date ASC
    ");

    $expiringItems = $stmtExpiry->fetchAll();
    foreach ($expiringItems as $item) {
        $notifications[] = [
            "type" => "danger",
            "icon" => "bi-calendar-x",
            "title" => "Expiring Soon",
            "message" => "{$item['quantity']} units of {$item['name']} expire in {$item['days_left']} days."
        ];
        $unreadCount++;
    }

    echo json_encode([
        "status" => "success",
        "count" => $unreadCount,
        "data" => $notifications
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
