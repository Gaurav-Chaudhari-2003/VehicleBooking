<?php
session_start();
require_once 'vendor/inc/config.php';
require_once 'vendor/inc/checklogin.php';
check_login();

header('Content-Type: application/json');

// Utility function to count rows
function count_items($table, $where = null, $param = null) {
    global $mysqli;
    $query = "SELECT COUNT(*) FROM $table" . ($where ? " WHERE $where = ?" : "");
    $stmt = $mysqli->prepare($query);
    if ($where) $stmt->bind_param("s", $param);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

$user_count = count_items('tms_user', 'u_category', 'User');
$driver_count = count_items('tms_user', 'u_category', 'Driver');
$vehicle_count = count_items('tms_vehicle');

// Fetch bookings
$bookings = [];
if ($stmt = $mysqli->prepare("
    SELECT b.booking_id, b.book_from_date, b.book_to_date, b.status, b.created_at,
           v.v_name, v.v_reg_no, u.u_fname, u.u_lname, u.u_phone
    FROM tms_booking b
    JOIN tms_vehicle v ON b.vehicle_id = v.v_id
    JOIN tms_user u ON b.user_id = u.u_id
    ORDER BY b.book_from_date DESC
")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
}

echo json_encode([
    'user_count' => $user_count,
    'driver_count' => $driver_count,
    'vehicle_count' => $vehicle_count,
    'bookings' => $bookings,
    'last_updated' => date("h:i A")
]);
?>
