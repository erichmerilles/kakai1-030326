<?php
// backend/auth/logout.php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session completely
session_destroy();

// Tell the frontend it was successful
header('Content-Type: application/json');
echo json_encode(["status" => "success", "message" => "Logged out securely."]);
