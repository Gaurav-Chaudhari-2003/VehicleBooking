<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

$sql = "
SELECT
 b.id,
 b.from_datetime,
 b.to_datetime,
 b.pickup_location,
 b.drop_location,
 b.status,
 v.name vehicle,
 u.first_name,
 u.last_name
FROM bookings b
JOIN vehicles v ON b.vehicle_id = v.id
JOIN users u ON b.user_id = u.id
";

if ($user['role'] === 'EMPLOYEE') {
    $sql .= " WHERE b.user_id = ?";
}

$sql .= " ORDER BY b.created_at DESC";

global $mysqli;
$stmt = $mysqli->prepare($sql);

if ($user['role'] === 'EMPLOYEE') {
    $stmt->bind_param('i', $user['id']);
}

$stmt->execute();
$res = $stmt->get_result();

$list = [];

while ($r = $res->fetch_assoc()) {
    $list[] = [
        "id" => (int)$r['id'],
        "vehicle" => $r['vehicle'],
        "user" => trim($r['first_name'].' '.$r['last_name']),
        "from" => $r['from_datetime'],
        "to" => $r['to_datetime'],
        "pickup" => $r['pickup_location'],
        "drop" => $r['drop_location'],
        "status" => $r['status']
    ];
}

apiResponse(true, "Bookings fetched", $list);
