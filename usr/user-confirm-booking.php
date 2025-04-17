<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

if (isset($_POST['book_vehicle'])) {
    $u_id = $_SESSION['u_id'];
    $u_car_type = $_POST['u_car_type'];
    $u_car_regno  = $_POST['u_car_regno'];
    $u_car_bookdate = $_POST['u_car_bookdate'];
    $u_car_book_status  = $_POST['u_car_book_status'];





    // STEP 1: Check for existing booking with status Pending or Approved
    // STEP 1: Check for existing booking with status Pending or Approved
    $statusStmt = $mysqli->prepare("SELECT u_car_book_status FROM tms_user WHERE u_id = ? AND (u_car_book_status = 'Pending' OR u_car_book_status = 'Approved')");
    $statusStmt->bind_param('i', $u_id);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();

    if ($statusResult->num_rows > 0) {
        $statusRow = $statusResult->fetch_assoc();
        $status = $statusRow['u_car_book_status'];

        if ($status === 'Pending') {
            $_SESSION['msg'] = "You already have a pending vehicle booking request. Please cancel it before making a new one.";
        } elseif ($status === 'Approved') {
            $_SESSION['msg'] = "You already have an approved vehicle booking. Please complete or cancel it before requesting another.";
        }

        $statusStmt->close();
        header("Location: user-dashboard.php");
        exit();
    }








    $query = "UPDATE tms_user SET u_car_type=?, u_car_bookdate=?, u_car_regno=?, u_car_book_status=? WHERE u_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssi', $u_car_type, $u_car_bookdate, $u_car_regno, $u_car_book_status, $u_id);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Booking submitted successfully!";
    } else {
        $_SESSION['msg'] = "Booking failed. Please try again.";
    }
}
header("Location: user-dashboard.php");
exit();




