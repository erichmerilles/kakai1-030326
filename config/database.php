<?php
// config/database.php
$host = 'localhost';
$db   = 'kakaidb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}

/**
 * Global Audit Logger
 * Records non-inventory system events into activity_logs table
 */
function logActivity($pdo, $userId, $action)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action_description, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $action, $ip]);
}
