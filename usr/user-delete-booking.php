<?php
global $mysqli;
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = intval($_POST['booking_id']);


    // Step 1: Get the booking ID and vehicle details
    $getBooking = $mysqli->prepare("SELECT booking_id, vehicle_id, status FROM tms_booking WHERE booking_id = ?");
    $getBooking->bind_param('i', $booking_id);

    $getBooking->execute();
    $getResult = $getBooking->get_result();

    if ($getResult->num_rows > 0) {
        $userBooking = $getResult->fetch_assoc();
        $bookingId = $userBooking['booking_id'];
        $vehicleId = $userBooking['vehicle_id'];
        $bookingStatus = $userBooking['status'];

        // Step 2: Cancel the booking by updating the status to 'Cancelled'
        $updateBooking = $mysqli->prepare("UPDATE tms_booking SET status = 'Cancelled' WHERE booking_id = ?");
        $updateBooking->bind_param('i', $bookingId);

        if ($updateBooking->execute()) {
            // Step 3: If the booking was approved, update the vehicle status back to 'Available'
            if ($bookingStatus === 'Approved' && !empty($vehicleId)) {
                $updateVehicle = $mysqli->prepare("UPDATE tms_vehicle SET v_status = 'Available' WHERE v_id = ?");
                $updateVehicle->bind_param('i', $vehicleId);
                $updateVehicle->execute();
            }

            http_response_code(200);
            echo "Booking Cancelled Successfully";
        } else {
            http_response_code(500);
            echo "Failed to cancel booking";
        }
    } else {
        http_response_code(404);
        echo "Booking not found or not in a cancellable state";
    }
}
