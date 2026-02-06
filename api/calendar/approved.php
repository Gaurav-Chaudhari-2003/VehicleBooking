<?php
require_once '../response.php';
require_once '../../DATABASE FILE/config.php';

$vehicle_id = intval($_GET['vehicle_id'] ?? 0);

if (!$vehicle_id) {
    apiResponse(false, "Vehicle ID required");
}

global $mysqli;
$stmt = $mysqli->prepare("
SELECT
 from_datetime AS start,
 to_datetime   AS end
FROM bookings
WHERE vehicle_id = ?
AND status = 'APPROVED'
ORDER BY from_datetime
");

$stmt->bind_param('i', $vehicle_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($r = $res->fetch_assoc()) {
    $data[] = [
        "from" => $r['start'],
        "to"   => $r['end'],
        "type" => "approved"
    ];
}

apiResponse(true, "Approved dates fetched", $data);
