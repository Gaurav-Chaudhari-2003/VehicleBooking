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
$id = intval($data['booking_id'] ?? 0);
$remark = trim($data['remark'] ?? '');

if (!$id) {
    apiResponse(false, "Booking ID required");
}

global $mysqli;
$stmt = $mysqli->prepare("UPDATE bookings SET status='REJECTED' WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // Optionally log remark
    if ($remark) {
        $rem = $mysqli->prepare("INSERT INTO entity_remarks (entity_type, entity_id, user_id, remark) VALUES ('BOOKING', ?, ?, ?)");
        $rem->bind_param('iis', $id, $user['id'], $remark);
        $rem->execute();
    }
    apiResponse(true, "Booking rejected");
} else {
    apiResponse(false, "Failed to reject booking", null, 500);
}
?>
