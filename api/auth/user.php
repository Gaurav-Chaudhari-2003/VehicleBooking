<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../response.php';
require_once 'middleware.php';

try {
    if (!file_exists('../../DATABASE FILE/config.php')) {
        throw new Exception("Config file not found");
    }
    require_once '../../DATABASE FILE/config.php';

    // Verify the token and get the user ID/Role from the middleware
    $authUser = requireAuth();

    global $mysqli;

    if (!$mysqli) {
        throw new Exception("Database connection object is null");
    }

    if ($mysqli->connect_error) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }

    // Fetch full user details
    $stmt = $mysqli->prepare("
        SELECT id, first_name, last_name, email, phone, role, address 
        FROM users 
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param('i', $authUser['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user) {
        apiResponse(true, "User profile fetched", [
            "id" => (int)$user['id'],
            "name" => $user['first_name'] . ' ' . $user['last_name'],
            "email" => $user['email'],
            "phone" => $user['phone'],
            "role" => $user['role'],
            "address" => $user['address']
        ]);
    } else {
        apiResponse(false, "User not found", null, 404);
    }

} catch (Throwable $e) {
    apiResponse(false, "Server Error: " . $e->getMessage(), null, 500);
}
?>
