<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, "Invalid request method", null, 405);
}

$data = json_decode(file_get_contents("php://input"), true);
$booking_id = intval($data['booking_id'] ?? 0);

if (!$booking_id) {
    apiResponse(false, "Booking ID required");
}

global $mysqli;

// Fetch booking to check permissions and status
$stmt = $mysqli->prepare("SELECT user_id, status FROM bookings WHERE id = ?");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$res = $stmt->get_result();
$booking = $res->fetch_assoc();

if (!$booking) {
    apiResponse(false, "Booking not found", null, 404);
}

// Authorization check: Only the owner or Admin/Manager can cancel
if ($booking['user_id'] !== $user['id'] && !in_array($user['role'], ['ADMIN', 'MANAGER'])) {
    apiResponse(false, "Unauthorized", null, 403);
}

// Validation: Cannot cancel if already completed, rejected, or cancelled
if (in_array($booking['status'], ['COMPLETED', 'REJECTED', 'CANCELLED'])) {
    apiResponse(false, "Cannot cancel booking with status: " . $booking['status']);
}

// Update status to CANCELLED
$upd = $mysqli->prepare("UPDATE bookings SET status = 'CANCELLED' WHERE id = ?");
$upd->bind_param('i', $booking_id);

if ($upd->execute()) {
    apiResponse(true, "Booking cancelled successfully");
} else {
    apiResponse(false, "Failed to cancel booking", null, 500);
}
?>
