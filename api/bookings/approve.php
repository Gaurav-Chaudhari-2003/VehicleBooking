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
$booking_id = intval($data['booking_id'] ?? 0);
$driver_id  = intval($data['driver_id'] ?? 0);

if (!$booking_id || !$driver_id) {
    apiResponse(false, "Booking & driver required");
}

global $mysqli;

/* Fetch booking */
$q = $mysqli->prepare("
SELECT vehicle_id, from_datetime, to_datetime
FROM bookings WHERE id = ?
");
$q->bind_param('i', $booking_id);
$q->execute();
$b = $q->get_result()->fetch_assoc();

if (!$b) {
    apiResponse(false, "Booking not found", null, 404);
}

/* Vehicle conflict */
$c = $mysqli->prepare("
SELECT id FROM bookings
WHERE vehicle_id = ?
AND status = 'APPROVED'
AND id != ?
AND from_datetime <= ?
AND to_datetime >= ?
");
$c->bind_param('iiss',
    $b['vehicle_id'],
    $booking_id,
    $b['to_datetime'],
    $b['from_datetime']
);
$c->execute();

if ($c->get_result()->num_rows > 0) {
    apiResponse(false, "Vehicle conflict");
}

/* Driver conflict */
$d = $mysqli->prepare("
SELECT id FROM bookings
WHERE driver_id = ?
AND status = 'APPROVED'
AND id != ?
AND from_datetime <= ?
AND to_datetime >= ?
");
$d->bind_param('iiss',
    $driver_id,
    $booking_id,
    $b['to_datetime'],
    $b['from_datetime']
);
$d->execute();

if ($d->get_result()->num_rows > 0) {
    apiResponse(false, "Driver conflict");
}

/* Approve */
$u = $mysqli->prepare("
UPDATE bookings
SET status='APPROVED', driver_id=?
WHERE id=?
");
$u->bind_param('ii', $driver_id, $booking_id);

if ($u->execute()) {
    apiResponse(true, "Booking approved");
} else {
    apiResponse(false, "Failed to approve booking", null, 500);
}
?>
