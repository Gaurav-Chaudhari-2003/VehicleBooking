<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_booking'])) {
    $u_id = intval($_POST['u_id']);

    // Step 1: Get user's booked vehicle registration number
    $getVehicle = $mysqli->prepare("SELECT u_car_regno, u_car_book_status FROM tms_user WHERE u_id = ?");
    $getVehicle->bind_param('i', $u_id);
    $getVehicle->execute();
    $getResult = $getVehicle->get_result();

    if ($getResult->num_rows > 0) {
        $userBooking = $getResult->fetch_assoc();
        $carRegNo = $userBooking['u_car_regno'];
        $bookingStatus = $userBooking['u_car_book_status'];

        // Step 2: Cancel the user booking (set fields to NULL)
        $query = "UPDATE tms_user SET 
                  u_car_type = NULL, 
                  u_car_regno = NULL, 
                  u_car_bookdate = NULL, 
                  u_car_book_status = NULL 
                  WHERE u_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $u_id);

        if ($stmt->execute()) {
            // Step 3: If booking was approved, set vehicle status back to 'Available'
            if ($bookingStatus === 'Approved' && !empty($carRegNo)) {
                $updateVehicle = $mysqli->prepare("UPDATE tms_vehicle SET v_status = 'Available' WHERE v_reg_no = ?");
                $updateVehicle->bind_param('s', $carRegNo);
                $updateVehicle->execute();
            }

            http_response_code(200);
            echo "Booking Cancelled";
        } else {
            http_response_code(500);
            echo "Failed to cancel booking";
        }
    } else {
        http_response_code(404);
        echo "Booking not found";
    }
}

