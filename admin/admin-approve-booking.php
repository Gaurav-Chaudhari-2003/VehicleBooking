<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

if (isset($_POST['approve_booking'])) {
    $booking_id = $_GET['booking_id'];
    $booking_status = $_POST['approve_booking']; // takes value from clicked button
    $admin_remarks = $_POST['admin_remarks'];

    $query = "UPDATE tms_booking SET status = ?, admin_remarks = ? WHERE booking_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssi', $booking_status, $admin_remarks, $booking_id);
    $stmt->execute();

    if ($stmt) {
        // Optional: Set a flash message in session if needed
        $_SESSION['flash_success'] = "Booking has been " . strtolower($booking_status) . " successfully.";

        // Redirect to dashboard after short delay
        echo "<script>
        setTimeout(function() {
            window.location.href = 'admin-dashboard.php';
        }, 1500);
    </script>";
        exit(); // Kill the current page execution
    } else {
        $err = "Failed to update booking.";
    }

}


?>

<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>

<body id="page-top">
<div id="wrapper">

    <div id="content-wrapper">

        <div class="container-fluid">

            <!-- Success/Error Messages -->
            <?php if (isset($succ)) { ?>
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ; ?>", "success");
                    }, 100);
                </script>
            <?php } ?>

            <?php if (isset($err)) { ?>
                <script>
                    setTimeout(function () {
                        swal("Failed!", "<?php echo $err; ?>", "error");
                    }, 100);
                </script>
            <?php } ?>

            <!-- Approve Booking Card -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-check-circle"></i> Approve Booking
                </div>
                <div class="card-body">

                    <?php
                    $booking_id = $_GET['booking_id'];
                    $ret = "SELECT b.*, u.u_fname, u.u_lname, u.u_email, u.u_phone, u.u_addr, v.v_name, v.v_category 
                            FROM tms_booking b
                            JOIN tms_user u ON b.user_id = u.u_id
                            JOIN tms_vehicle v ON b.vehicle_id = v.v_id
                            WHERE b.booking_id = ?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $booking_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_object()) {
                        ?>
                        <form method="POST">
                            <!-- User Details -->
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>First Name</label>
                                    <input type="text" readonly class="form-control" value="<?php echo $row->u_fname; ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Last Name</label>
                                    <input type="text" readonly class="form-control" value="<?php echo $row->u_lname; ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" readonly class="form-control" value="<?php echo $row->u_email; ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->u_phone; ?>">
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->u_addr; ?>">
                            </div>

                            <!-- Vehicle Details -->
                            <div class="form-group">
                                <label>Vehicle Name</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->v_name; ?>">
                            </div>
                            <div class="form-group">
                                <label>Vehicle Category</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->v_category; ?>">
                            </div>

                            <!-- Booking Details -->
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Booking From Date</label>
                                    <input type="text" readonly class="form-control" value="<?php echo $row->book_from_date; ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Booking To Date</label>
                                    <input type="text" readonly class="form-control" value="<?php echo $row->book_to_date; ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Message from Booking Person</label>
                                <textarea readonly class="form-control" rows="3"><?php echo htmlspecialchars($row->remarks); ?></textarea>
                            </div>



                            <!-- Current Status & Status Change -->
                            <div class="form-group">
                                <label>Current Status</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->status; ?>">
                            </div>

                            <div class="form-group">
                                <label>Admin Remarks</label>
                                <textarea name="admin_remarks" class="form-control" rows="3" placeholder="Write your remarks here..."><?php echo htmlspecialchars($row->admin_remarks ?? ''); ?></textarea>
                            </div>



                            <div class="form-group text-center">
                                <button type="submit" name="approve_booking" value="Approved" class="btn btn-success">
                                    <i class="fas fa-check-circle"></i> Approve
                                </button>
                                <button type="submit" name="approve_booking" value="Cancelled" class="btn btn-danger ms-2">
                                    <i class="fas fa-times-circle"></i> Reject
                                </button>
                            </div>

                        </form>
                    <?php } ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/swal.js"></script>

</body>
</html>
