<?php
require_once '../DATABASE FILE/config.php';

header('Content-Type: application/json');

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$exclude_id = intval($_GET['exclude_id'] ?? 0);

if (!$start || !$end) {
    echo json_encode([]);
    exit;
}

// Ensure dates are formatted for SQL
$start = date('Y-m-d H:i:s', strtotime($start));
$end = date('Y-m-d H:i:s', strtotime($end));

// Find drivers who have APPROVED bookings overlapping with the requested range
// Logic: (StartA <= EndB) and (EndA >= StartB)
$query = "SELECT DISTINCT driver_id 
          FROM bookings 
          WHERE status = 'APPROVED' 
            AND driver_id IS NOT NULL 
            AND id != ? 
            AND (from_datetime <= ? AND to_datetime >= ?)";

global $mysqli;
$stmt = $mysqli->prepare($query);
$stmt->bind_param('iss', $exclude_id, $end, $start);
$stmt->execute();
$result = $stmt->get_result();

$busy_drivers = [];
while ($row = $result->fetch_assoc()) {
    $busy_drivers[] = $row['driver_id'];
}

echo json_encode($busy_drivers);
?>
