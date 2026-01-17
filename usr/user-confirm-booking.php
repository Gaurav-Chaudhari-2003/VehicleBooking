<?php
session_start();

require_once '../DATABASE FILE/config.php';
require_once '../DATABASE FILE/checklogin.php';
check_login();

global $mysqli;

$redirect_url = 'user-dashboard.php';

if (!isset($_POST['book_vehicle'])) {
    header("Location: $redirect_url");
    exit;
}

$user_id   = $_SESSION['u_id'];
$vehicle_id = intval($_POST['v_id']);

$from = $_POST['book_from_date'] . " 00:00:00";
$to   = $_POST['book_to_date']   . " 23:59:59";

$pickup  = trim($_POST['pickup_location']);
$drop    = trim($_POST['drop_location']);
$purpose = trim($_POST['purpose'] ?? '');


// -------- VALIDATION --------

if ($from > $to) {
    dieAlert("Invalid date range");
}

if (!$pickup || !$drop) {
    dieAlert("Pickup and Drop locations required");
}


// -------- CONFLICT CHECK (ONLY APPROVED) --------

$sql = "
SELECT id FROM bookings
WHERE vehicle_id = ?
AND status = 'APPROVED'
AND from_datetime <= ?
AND to_datetime >= ?
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('iss', $vehicle_id, $to, $from);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    dieAlert("Vehicle already APPROVED for this period");
}


// -------- INSERT BOOKING --------

$insert = "
INSERT INTO bookings
(user_id, vehicle_id, from_datetime, to_datetime,
 pickup_location, drop_location, purpose, status)
VALUES (?,?,?,?,?,?,?, 'PENDING')
";

$stmt = $mysqli->prepare($insert);
$stmt->bind_param(
        'iisssss',
        $user_id,
        $vehicle_id,
        $from,
        $to,
        $pickup,
        $drop,
        $purpose
);

if ($stmt->execute()) {
    successAlert("Booking request submitted");
} else {
    dieAlert("Booking failed");
}



// -------- HELPERS --------

function dieAlert($msg) {
    echo "<script>
        alert('$msg');
        window.location='usr-book-vehicle.php';
    </script>";
    exit;
}

function successAlert($msg) {
    echo "<script>
        alert('$msg');
        window.location='user-dashboard.php';
    </script>";
    exit;
}
