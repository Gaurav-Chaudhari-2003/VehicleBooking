<?php
global $mysqli;
include_once('vendor/inc/config.php');

if (isset($_GET['v_id'])) {
    $v_id = intval($_GET['v_id']);
    $stmt = $mysqli->prepare("
        SELECT book_from_date, book_to_date 
        FROM tms_booking 
        WHERE vehicle_id = ? AND status IN ('Approved')
    ");
    $stmt->bind_param("i", $v_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedDates = [];
    while ($row = $result->fetch_assoc()) {
        $bookedDates[] = $row;
    }

    echo json_encode($bookedDates);
}
