<?php
require_once '../response.php';
require_once '../../DATABASE FILE/config.php';
require_once 'middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, "Invalid request method", null, 405);
}

$user = requireAuth();

global $mysqli;
$stmt = $mysqli->prepare("UPDATE users SET api_token = NULL, token_expiry = NULL WHERE id = ?");
$stmt->bind_param('i', $user['id']);

if ($stmt->execute()) {
    apiResponse(true, "Logged out successfully");
} else {
    apiResponse(false, "Logout failed", null, 500);
}
?>
