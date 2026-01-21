<?php
global $mysqli;
session_start();
include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    $user_id = $_SESSION['u_id']; // Ensure user owns the booking

    // Step 1: Verify booking exists and belongs to user
    // Updated table name: bookings
    $getBooking = $mysqli->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
    $getBooking->bind_param('ii', $booking_id, $user_id);

    $getBooking->execute();
    $getResult = $getBooking->get_result();

    if ($getResult->num_rows > 0) {
        $userBooking = $getResult->fetch_assoc();
        $currentStatus = $userBooking['status'];

        // Only allow cancellation if status is PENDING (or maybe APPROVED depending on policy, but usually PENDING)
        // The frontend only shows the button for PENDING, so we enforce it here too for security.
        if ($currentStatus === 'PENDING') {
            
            // Step 2: Cancel the booking by updating the status to 'CANCELLED'
            // Updated table name: bookings
            $updateBooking = $mysqli->prepare("UPDATE bookings SET status = 'CANCELLED' WHERE id = ?");
            $updateBooking->bind_param('i', $booking_id);

            if ($updateBooking->execute()) {
                // Log the action
                $action = "User Cancelled Booking";
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt = $mysqli->prepare("INSERT INTO system_logs(user_id, action, ip) VALUES (?, ?, ?)");
                $log_stmt->bind_param("iss", $user_id, $action, $ip);
                $log_stmt->execute();

                http_response_code(200);
                echo "Booking Cancelled Successfully";
            } else {
                http_response_code(500);
                echo "Failed to update booking status";
            }
        } else {
            http_response_code(400); // Bad Request
            echo "Cannot cancel booking with status: " . $currentStatus;
        }
    } else {
        http_response_code(404);
        echo "Booking not found or access denied";
    }
}
