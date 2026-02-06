<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

if (!in_array($user['role'], ['ADMIN','MANAGER'])) {
    apiResponse(false, "Unauthorized", null, 403);
}

$driver_id = intval($_GET['driver_id'] ?? 0);
$from      = $_GET['from'] ?? '';
$to        = $_GET['to'] ?? '';

if (!$driver_id || !$from || !$to) {
    apiResponse(false, "Missing parameters");
}

global $mysqli;
$q = $mysqli->prepare("
    SELECT id FROM bookings
    WHERE driver_id = ?
    AND status = 'APPROVED'
    AND from_datetime <= ?
    AND to_datetime >= ?
");

$q->bind_param('iss', $driver_id, $to, $from);
$q->execute();

$conflict = $q->get_result()->num_rows > 0;

apiResponse(true, "Driver availability checked", [
    "available" => !$conflict
]);
