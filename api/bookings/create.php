<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, "Invalid request method", null, 405);
}

$data = json_decode(file_get_contents("php://input"), true);

$vehicle_id = intval($data['vehicle_id'] ?? 0);
$from = $data['from_datetime'] ?? '';
$to   = $data['to_datetime'] ?? '';
$pickup = trim($data['pickup_location'] ?? '');
$drop   = trim($data['drop_location'] ?? '');
$purpose = trim($data['purpose'] ?? '');

if (!$vehicle_id || !$from || !$to || !$pickup || !$drop) {
    apiResponse(false, "Missing required fields");
}

if (strtotime($from) > strtotime($to)) {
    apiResponse(false, "Invalid date range");
}

global $mysqli;

/* Conflict check (APPROVED only) */
$conflict = $mysqli->prepare("
SELECT id FROM bookings
WHERE vehicle_id = ?
AND status = 'APPROVED'
AND from_datetime <= ?
AND to_datetime >= ?
");
$conflict->bind_param('iss', $vehicle_id, $to, $from);
$conflict->execute();

if ($conflict->get_result()->num_rows > 0) {
    apiResponse(false, "Vehicle already booked for selected dates");
}

/* Insert booking */
$ins = $mysqli->prepare("
INSERT INTO bookings
(user_id, vehicle_id, from_datetime, to_datetime,
 pickup_location, drop_location, purpose, status)
VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')
");

$ins->bind_param(
    'iisssss',
    $user['id'],
    $vehicle_id,
    $from,
    $to,
    $pickup,
    $drop,
    $purpose
);

if ($ins->execute()) {
    apiResponse(true, "Booking request submitted", [
        "booking_id" => $ins->insert_id
    ]);
} else {
    apiResponse(false, "Failed to create booking", null, 500);
}
?>
