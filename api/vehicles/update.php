<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

if (!in_array($user['role'], ['ADMIN','MANAGER'])) {
    apiResponse(false, "Unauthorized", null, 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, "Invalid request method", null, 405);
}

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!$id || !$status) {
    apiResponse(false, "ID and status required");
}

global $mysqli;
$stmt = $mysqli->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $id);

if ($stmt->execute()) {
    apiResponse(true, "Vehicle status updated");
} else {
    apiResponse(false, "Update failed", null, 500);
}
?>
