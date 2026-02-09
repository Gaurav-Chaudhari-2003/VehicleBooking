<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../response.php';

try {
    if (!file_exists('../../DATABASE FILE/config.php')) {
        throw new Exception("Config file not found");
    }
    require_once '../../DATABASE FILE/config.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        apiResponse(false, "Invalid request method");
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    if (!$email || !$password) {
        apiResponse(false, "Email and password required");
    }

    global $mysqli;

    if (!$mysqli) {
        throw new Exception("Database connection object is null");
    }

    if ($mysqli->connect_error) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare("
        SELECT id, first_name, last_name, role, password
        FROM users
        WHERE email = ? AND is_active = 1
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        apiResponse(false, "Invalid credentials");
    }

    $user = $res->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        apiResponse(false, "Invalid credentials");
    }

    /* Generate token */
    try {
        $token = bin2hex(random_bytes(32));
    } catch (\Random\RandomException $e) {
        $token = bin2hex(openssl_random_pseudo_bytes(32));
    }
    $expiry = date('Y-m-d H:i:s', strtotime('+7 days'));

    $upd = $mysqli->prepare("
        UPDATE users
        SET api_token = ?, token_expiry = ?
        WHERE id = ?
    ");

    if (!$upd) {
        throw new Exception("Prepare update failed: " . $mysqli->error);
    }

    $upd->bind_param('ssi', $token, $expiry, $user['id']);
    $upd->execute();

    apiResponse(true, "Login successful", [
        "token" => $token,
        "user" => [
            "id" => $user['id'],
            "name" => $user['first_name'].' '.$user['last_name'],
            "role" => $user['role']
        ]
    ]);

} catch (Throwable $e) {
    apiResponse(false, "Server Error: " . $e->getMessage(), null, 500);
}
?>
