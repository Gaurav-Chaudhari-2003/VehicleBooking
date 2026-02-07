<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

// Define base URL for images
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$projectFolder = '/vehicle_booking/';
$baseUrl = $protocol . "://" . $host . $projectFolder;

$sql = "
SELECT
 b.id,
 b.from_datetime,
 b.to_datetime,
 b.pickup_location,
 b.drop_location,
 b.status,
 v.name vehicle,
 v.image vehicle_image,
 u.first_name,
 u.last_name,
 d_user.first_name AS driver_first_name,
 d_user.last_name AS driver_last_name,
 d_user.phone AS driver_contact
FROM bookings b
JOIN vehicles v ON b.vehicle_id = v.id
JOIN users u ON b.user_id = u.id
LEFT JOIN drivers d ON b.driver_id = d.id
LEFT JOIN users d_user ON d.user_id = d_user.id
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
    $booking = [
        "id" => (int)$r['id'],
        "vehicle" => $r['vehicle'],
        "user" => trim($r['first_name'].' '.$r['last_name']),
        "from" => $r['from_datetime'],
        "to" => $r['to_datetime'],
        "pickup" => $r['pickup_location'],
        "drop" => $r['drop_location'],
        "status" => $r['status']
    ];

    if ($r['status'] === 'APPROVED') {
        $booking['vehicle_image'] = $r['vehicle_image']
            ? $baseUrl . 'vendor/img/vehicles_img/' . $r['vehicle_image']
            : $baseUrl . 'vendor/img/placeholder.png';
        
        $booking['driver_name'] = trim($r['driver_first_name'] . ' ' . $r['driver_last_name']);
        $booking['driver_contact'] = $r['driver_contact'];
    }

    $list[] = $booking;
}

apiResponse(true, "Bookings fetched", $list);
