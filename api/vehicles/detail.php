<?php
require_once '../response.php';
require_once '../../DATABASE FILE/config.php';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    apiResponse(false, "Vehicle ID required");
}

// Define base URL for images
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$projectFolder = '/vehicle_booking/'; 
$baseUrl = $protocol . "://" . $host . $projectFolder;

global $mysqli;
$q = $mysqli->prepare("
SELECT
    id,
    name,
    reg_no,
    category,
    fuel_type,
    capacity,
    image,
    status,
    ownership_type
FROM vehicles
WHERE id = ?
");

$q->bind_param('i', $id);
$q->execute();

$data = $q->get_result()->fetch_assoc();

if (!$data) {
    apiResponse(false, "Vehicle not found", null, 404);
}

$data['image'] = $data['image']
    ? $baseUrl . 'vendor/img/vehicles_img/' . $data['image']
    : $baseUrl . 'vendor/img/placeholder.png';

apiResponse(true, "Vehicle details", $data);
?>
