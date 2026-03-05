<?php
session_start();
require_once '../../config/database.php';

// Ensure no HTML errors mess up the JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        throw new Exception("Please fill in all fields.");
    }

    // Prepare SQL to fetch user
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.password_hash, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.username = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verify password
    if ($user && password_verify($password, $user['password_hash'])) {

        // Success: Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['username'] = $user['username'];

        echo json_encode([
            "status" => "success",
            "role" => $user['role_name'],
            "message" => "Login successful"
        ]);
    } else {
        throw new Exception("Invalid username or password.");
    }
} catch (Exception $e) {
    // Catch any error (including DB errors) and return it as JSON
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
