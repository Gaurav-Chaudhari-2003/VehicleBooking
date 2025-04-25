<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

if (isset($_POST['approve_booking'])) {
    $booking_id = $_GET['booking_id'];
    $booking_status = $_POST['booking_status'];

    // Step 1: Update booking status
    $query = "UPDATE tms_booking SET status = ? WHERE booking_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('si', $booking_status, $booking_id);
    $stmt->execute();

    if ($stmt) {
        $succ = "Booking status updated successfully.";
    } else {
        $err = "Failed to update booking status.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>

<body id="page-top">
<?php include("vendor/inc/nav.php"); ?>
<div id="wrapper">
    <?php include("vendor/inc/sidebar.php"); ?>

    <div id="content-wrapper">
        <div class="container-fluid">

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

            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Bookings</a></li>
                <li class="breadcrumb-item active">Approve</li>
            </ol>

            <div class="card mb-3">
                <div class="card-header">Approve Booking</div>
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
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->u_fname; ?>">
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->u_lname; ?>">
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
                            <div class="form-group">
                                <label>Vehicle Name</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->v_name; ?>">
                            </div>
                            <div class="form-group">
                                <label>Vehicle Category</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->v_category; ?>">
                            </div>
                            <div class="form-group">
                                <label>Booking From Date</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->book_from_date; ?>">
                            </div>
                            <div class="form-group">
                                <label>Booking To Date</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->book_to_date; ?>">
                            </div>
                            <div class="form-group">
                                <label>Current Status</label>
                                <input type="text" readonly class="form-control" value="<?php echo $row->status; ?>">
                            </div>
                            <div class="form-group">
                                <label>Change Booking Status</label>
                                <select name="booking_status" class="form-control">
                                    <option value="Approved" <?php if ($row->status == 'Approved') echo 'selected'; ?>>Approved</option>
                                    <option value="Pending" <?php if ($row->status == 'Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Cancelled" <?php if ($row->status == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="approve_booking" class="btn btn-success">Update Booking</button>
                        </form>
                    <?php } ?>
                </div>
            </div>

        </div>

        <?php include("vendor/inc/footer.php"); ?>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<?php include("../usr/vendor/inc/logout-modal.php"); ?>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>
<script src="vendor/js/demo/datatables-demo.js"></script>
<script src="vendor/js/demo/chart-area-demo.js"></script>
<script src="vendor/js/swal.js"></script>

</body>
</html>
