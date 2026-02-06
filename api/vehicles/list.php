<?php
require_once '../response.php';
require_once '../../DATABASE FILE/config.php';

// Define base URL for images
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Assuming the project is in /vehicle_booking/
$projectFolder = '/vehicle_booking/'; 
$baseUrl = $protocol . "://" . $host . $projectFolder;

$sql = "
SELECT
    id,
    name,
    reg_no,
    category,
    fuel_type,
    capacity,
    image
FROM vehicles
WHERE status = 'AVAILABLE'
ORDER BY name
";

global $mysqli;
$res = $mysqli->query($sql);

$vehicles = [];
while ($row = $res->fetch_assoc()) {
    $row['image'] = $row['image']
        ? $baseUrl . 'vendor/img/vehicles_img/' . $row['image']
        : $baseUrl . 'vendor/img/placeholder.png';
    $vehicles[] = $row;
}

apiResponse(true, "Vehicle list", $vehicles);
?>
