<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Delete Booking
if(isset($_POST['delete_booking'])) {
    $booking_id = $_GET['booking_id']; // Get the booking ID to delete

    // Prepare SQL to delete the booking from the tms_booking table
    $query = "DELETE FROM tms_booking WHERE booking_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();

    if($stmt) {
        $succ = "Booking Deleted Successfully";
    } else {
        $err = "Please Try Again Later";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php');?>

<body id="page-top">
<!--Start Navigation Bar-->
<?php include("vendor/inc/nav.php");?>
<!--Navigation Bar-->

<div id="wrapper">
    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php");?>
    <!--End Sidebar-->

    <div id="content-wrapper">
        <div class="container-fluid">
            <?php if(isset($succ)) {?>
                <script>
                    setTimeout(function ()
                    {
                        swal("Success!", "<?php echo $succ;?>", "success");
                    }, 100);
                </script>
            <?php } ?>

            <?php if(isset($err)) {?>
                <script>
                    setTimeout(function ()
                    {
                        swal("Failed!", "<?php echo $err;?>", "error");
                    }, 100);
                </script>
            <?php } ?>

            <!-- Breadcrumbs-->
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="#">Bookings</a>
                </li>
                <li class="breadcrumb-item active">Delete Booking</li>
            </ol>
            <hr>

            <div class="card">
                <div class="card-header">
                    Delete Booking
                </div>
                <div class="card-body">
                    <!-- Booking Details -->
                    <?php
                    $booking_id = $_GET['booking_id'];
                    $ret = "SELECT b.*, u.u_fname, u.u_lname, u.u_phone, u.u_addr, v.v_name, v.v_category,
                            v.v_category, v.v_reg_no
                            FROM tms_booking b
                            JOIN tms_user u ON b.user_id = u.u_id
                            JOIN tms_vehicle v ON b.vehicle_id = v.v_id
                            WHERE b.booking_id = ?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $booking_id);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    while($row = $res->fetch_object()) {
                        ?>
                        <form method="POST">
                            <div class="form-group">
                                <label for="u_fname">First Name</label>
                                <input type="text" readonly value="<?php echo $row->u_fname; ?>" class="form-control" name="u_fname">
                            </div>
                            <div class="form-group">
                                <label for="u_lname">Last Name</label>
                                <input type="text" readonly value="<?php echo $row->u_lname; ?>" class="form-control" name="u_lname">
                            </div>
                            <div class="form-group">
                                <label for="u_phone">Contact</label>
                                <input type="text" readonly value="<?php echo $row->u_phone; ?>" class="form-control" name="u_phone">
                            </div>
                            <div class="form-group">
                                <label for="u_car_type">Vehicle Category</label>
                                <input type="text" readonly value="<?php echo $row->v_category; ?>" class="form-control" name="u_car_type">
                            </div>
                            <div class="form-group">
                                <label for="u_car_regno">Vehicle Registration Number</label>
                                <input type="text" readonly value="<?php echo $row->v_reg_no; ?>" class="form-control" name="u_car_regno">
                            </div>
                            <div class="form-group">
                                <label for="u_car_bookdate">Booking Date</label>
                                <input type="text" readonly value="<?php echo $row->created_at; ?>" class="form-control" name="u_car_bookdate">
                            </div>
                            <div class="form-group">
                                <label for="u_car_book_status">Booking Status</label>
                                <input type="text" readonly value="<?php echo $row->status; ?>" class="form-control" name="u_car_book_status">
                            </div>

                            <button type="submit" name="delete_booking" class="btn btn-danger">Delete Booking</button>
                        </form>
                    <?php }?>
                </div>
            </div>

            <hr>
        </div>
        <!-- Sticky Footer -->
        <?php include("vendor/inc/footer.php");?>
    </div>
    <!-- /.content-wrapper -->

</div>
<!-- /#wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="vendor/js/sb-admin.min.js"></script>

<!-- Sweet alert JS -->
<script src="vendor/js/swal.js"></script>

</body>

</html>
