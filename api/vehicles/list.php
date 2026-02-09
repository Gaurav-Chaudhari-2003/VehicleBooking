<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../response.php';

try {
    if (!file_exists('../../DATABASE FILE/config.php')) {
        throw new Exception("Config file not found");
    }
    require_once '../../DATABASE FILE/config.php';

    global $mysqli;

    if (!$mysqli) {
        throw new Exception("Database connection object is null");
    }

    if ($mysqli->connect_error) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }

    // Define base URL for images
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $projectFolder = '/vehicle_booking/';
    $baseUrl = $protocol . "://" . $host . $projectFolder;

    // Get filter dates from the query string.
    $startDate = $_GET['filter_from'] ?? null;
    $endDate = $_GET['filter_to'] ?? null;

    // Base query to get all vehicles.
    $sql = "SELECT * FROM vehicles";
    $params = [];
    $types = '';

    // If start and end dates are provided, modify the query to filter for available vehicles.
    if ($startDate && $endDate) {
        $sql = "
        SELECT *
        FROM vehicles v
        WHERE NOT EXISTS (
            SELECT 1
            FROM bookings b
            WHERE b.vehicle_id = v.id
              AND b.status NOT IN ('REJECTED', 'CANCELLED', 'COMPLETED')
              AND b.from_datetime < ?
              AND b.to_datetime > ?
        )";
        // Note the order: endDate first, then startDate
        $params = [$endDate, $startDate];
        $types = 'ss'; // Both are strings
    }

    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        // Bind parameters only if they exist.
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        $vehicles = [];
        while ($row = $res->fetch_assoc()) {
            // To fix the client-side error, ensure fields expected as strings are cast to strings.
            $row['id'] = (string)$row['id'];
            $row['capacity'] = (string)$row['capacity'];

            // Construct full image URL.
            $row['image'] = $row['image']
                ? $baseUrl . 'vendor/img/vehicles_img/' . $row['image']
                : $baseUrl . 'vendor/img/placeholder.png';

            $vehicles[] = $row;
        }

        $stmt->close();
        apiResponse(true, "Vehicle list", $vehicles);
    } else {
        // Handle SQL statement preparation error.
        throw new Exception("Error preparing SQL statement: " . $mysqli->error);
    }

} catch (Throwable $e) {
    apiResponse(false, "Server Error: " . $e->getMessage(), null, 500);
}
?>
