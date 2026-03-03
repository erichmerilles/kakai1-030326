<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
        exit;
    }

    // fetch user
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.password_hash, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.username = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // verify password
    if ($user && password_verify($password, $user['password_hash'])) {

        // rbac session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role_name'];

        echo json_encode([
            "status" => "success",
            "role" => $user['role_name'],
            "message" => "Login successful"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
