<?php
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

    $query = "UPDATE tms_user SET u_car_type=?, u_car_bookdate=?, u_car_regno=?, u_car_book_status=? WHERE u_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssi', $u_car_type, $u_car_bookdate, $u_car_regno, $u_car_book_status, $u_id);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Booking submitted successfully!";
    } else {
        $_SESSION['msg'] = "Booking failed. Please try again.";
    }
}
header("Location: user-view-booking.php");
exit();



?>
