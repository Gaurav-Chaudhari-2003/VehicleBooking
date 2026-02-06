<?php
require_once '../response.php';
require_once '../auth/middleware.php';
require_once '../../DATABASE FILE/config.php';

$user = requireAuth();

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    apiResponse(false, "Vehicle ID required");
}

global $mysqli;
$q = $mysqli->prepare("
SELECT
 v.*,
 ven.name AS vendor_name,
 ven.phone AS vendor_phone,
 vc.contract_start_date,
 vc.contract_end_date,
 vc.contract_status
FROM vehicles v
LEFT JOIN vehicle_contracts vc ON v.id = vc.vehicle_id
LEFT JOIN vendors ven ON vc.vendor_id = ven.id
WHERE v.id = ?
ORDER BY vc.created_at DESC
LIMIT 1
");

$q->bind_param('i', $id);
$q->execute();

$data = $q->get_result()->fetch_assoc();

if (!$data) {
    apiResponse(false, "Vehicle not found", null, 404);
}

apiResponse(true, "Vehicle details", [
    "id" => (int)$data['id'],
    "name" => $data['name'],
    "reg_no" => $data['reg_no'],
    "category" => $data['category'],
    "fuel_type" => $data['fuel_type'],
    "capacity" => (int)$data['capacity'],
    "ownership" => $data['ownership_type'],
    "status" => $data['status'],
    "image" => $data['image'],
    "vendor" => $data['vendor_name']
        ? [
            "name" => $data['vendor_name'],
            "phone" => $data['vendor_phone'],
            "contract_start" => $data['contract_start_date'],
            "contract_end" => $data['contract_end_date'],
            "contract_status" => $data['contract_status']
        ]
        : null
]);
