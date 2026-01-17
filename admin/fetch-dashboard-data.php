<?php
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();

header('Content-Type: application/json');


// Utility function to count rows
function count_items($table, $where = null, $param = null) {
    $query = "SELECT COUNT(*) FROM $table" . ($where ? " WHERE $where = ?" : "");
    global $mysqli;
    $stmt = $mysqli->prepare($query);
    if ($where) $stmt->bind_param("s", $param);
    $stmt->execute();
    global $count;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

$employee_count = count_items('users', 'role', 'EMPLOYEE');
$driver_count = count_items('users', 'role', 'DRIVER');
$manager_count = count_items('users', 'role', 'MANAGER');
$admin_count = count_items('users', 'role', 'ADMIN');
$vehicle_count = count_items('vehicles');

// Fetch bookings
$bookings = [];
global $mysqli;
// Updated query to match the new schema: bookings table
// Columns: id, from_datetime, to_datetime, status, created_at
// Joins: vehicles (id, name, reg_no), users (id, first_name, last_name, phone)
if ($stmt = $mysqli->prepare("
    SELECT b.id as booking_id, b.from_datetime, b.to_datetime, b.status, b.created_at,
           v.name as v_name, v.reg_no as v_reg_no, u.first_name as u_fname, u.last_name as u_lname, u.phone as u_phone
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.from_datetime DESC
")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
}

echo json_encode([
    'employee_count' => $employee_count,
    'driver_count' => $driver_count,
    'manager_count' => $manager_count,
    'admin_count' => $admin_count,
    'vehicle_count' => $vehicle_count,
    'bookings' => $bookings,
    'last_updated' => date("h:i A")
]);
