<?php
require_once '../DATABASE FILE/config.php';

$v_id = intval($_GET['v_id']);

global $mysqli;
$stmt = $mysqli->prepare("
    SELECT 
        DATE(from_datetime) AS book_from_date,
        DATE(to_datetime)   AS book_to_date
    FROM bookings
    WHERE vehicle_id = ?
    AND status = 'APPROVED'
");

$stmt->bind_param("i", $v_id);
$stmt->execute();

echo json_encode(
    $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
);
