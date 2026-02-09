<?php
require_once 'response.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    if (!file_exists('../DATABASE FILE/config.php')) {
        throw new Exception("Config file not found at ../DATABASE FILE/config.php");
    }
    
    require_once '../DATABASE FILE/config.php';

    global $mysqli;

    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }

    apiResponse(true, "Database connection successful", [
        "host_info" => $mysqli->host_info
    ]);

} catch (Exception $e) {
    apiResponse(false, "Database Error: " . $e->getMessage());
}
?>
