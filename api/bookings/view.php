<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();
$id = intval($_GET['id'] ?? 0);

if (!$id) apiResponse(false, "Booking ID required");

global $mysqli;
$q = $mysqli->prepare("
SELECT
 b.*,
 v.name vehicle,
 u.first_name,
 u.last_name,
 u.email,
 d.id driver_id
FROM bookings b
JOIN vehicles v ON b.vehicle_id = v.id
JOIN users u ON b.user_id = u.id
LEFT JOIN drivers d ON b.driver_id = d.id
WHERE b.id = ?
");

$q->bind_param('i', $id);
$q->execute();
$data = $q->get_result()->fetch_assoc();

if (!$data) apiResponse(false, "Booking not found", null, 404);

/* User access control */
if ($user['role'] === 'EMPLOYEE' && $data['user_id'] !== $user['id']) {
    apiResponse(false, "Unauthorized", null, 403);
}

apiResponse(true, "Booking details", $data);
