<?php
require_once '../response.php';
require_once '../../DATABASE FILE/config.php';

function requireAuth(): false|array|null
{
    global $mysqli;

    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';

    // Handle "Bearer <token>" format
    if (preg_match('/Bearer\s(\S+)/', $token, $matches)) {
        $token = $matches[1];
    }

    if (!$token) {
        apiResponse(false, "Token missing", null, 401);
    }

    $stmt = $mysqli->prepare("
        SELECT id, role
        FROM users
        WHERE api_token = ?
        AND token_expiry > NOW()
    ");
    $stmt->bind_param('s', $token);
    $stmt->execute();

    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        apiResponse(false, "Invalid or expired token", null, 401);
    }

    return $res->fetch_assoc(); // user info
}
