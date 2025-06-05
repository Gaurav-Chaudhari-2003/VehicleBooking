<?php
global $mysqli;
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Handle Actions
if (isset($_GET['action']) && isset($_GET['booking_id'])) {
    $action = $_GET['action'];
    $booking_id = intval($_GET['booking_id']);
    $query = "";

    if ($action == 'approve') {
        $query = "UPDATE tms_booking SET status = 'Approved' WHERE booking_id = ?";
    } elseif ($action == 'reject') {
        $query = "UPDATE tms_booking SET status = 'Rejected' WHERE booking_id = ?";
    } elseif ($action == 'complete') {
        $query = "UPDATE tms_booking SET status = 'Completed' WHERE booking_id = ?";
    } elseif ($action == 'delete') {
        $query = "DELETE FROM tms_booking WHERE booking_id = ?";
    }

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();

    if ($stmt) {
        $succ = "Operation Successful!";
    } else {
        $err = "Operation Failed. Please Try Again.";
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

            <?php if (isset($succ)) { ?>
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ; ?>", "success").then(() => {
                            window.location.href = "admin-dashboard.php"; // Redirect back to bookings page
                        });
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

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Manage Booking</h5>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_GET['booking_id'])) {
                        $booking_id = intval($_GET['booking_id']);
                        $ret = "SELECT b.*, u.u_fname, u.u_lname, u.u_phone, u.u_addr, v.v_name, v.v_category, v.v_reg_no
                                FROM tms_booking b
                                JOIN tms_user u ON b.user_id = u.u_id
                                JOIN tms_vehicle v ON b.vehicle_id = v.v_id
                                WHERE b.booking_id = ?";
                        $stmt = $mysqli->prepare($ret);
                        $stmt->bind_param('i', $booking_id);
                        $stmt->execute();
                        $res = $stmt->get_result();

                        if($row = $res->fetch_object()) {
                            ?>
                            <form>
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" readonly value="<?php echo $row->u_fname; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" readonly value="<?php echo $row->u_lname; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Contact</label>
                                    <input type="text" readonly value="<?php echo $row->u_phone; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Vehicle Category</label>
                                    <input type="text" readonly value="<?php echo $row->v_category; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Vehicle Registration Number</label>
                                    <input type="text" readonly value="<?php echo $row->v_reg_no; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Booking Date</label>
                                    <input type="text" readonly value="<?php echo $row->created_at; ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Booking Status</label>
                                    <input type="text" readonly value="<?php echo $row->status; ?>" class="form-control">
                                </div>

                                <div class="form-group text-center">
                                    <?php if ($row->status == 'Pending') { ?>
                                        <button type="button" class="btn btn-success btn-lg" onclick="confirmAction('approve', <?php echo $booking_id; ?>)">Approve</button>
                                        <button type="button" class="btn btn-danger btn-lg" onclick="confirmAction('reject', <?php echo $booking_id; ?>)">Reject</button>
                                    <?php } elseif ($row->status == 'Approved') { ?>
                                        <button type="button" class="btn btn-primary btn-lg" onclick="confirmAction('complete', <?php echo $booking_id; ?>)">Mark as Completed</button>
                                    <?php } elseif (in_array($row->status, ['Cancelled', 'Rejected', 'Completed'])) { ?>
                                        <button type="button" class="btn btn-danger btn-lg" onclick="confirmAction('delete', <?php echo $booking_id; ?>)">Delete Entry</button>
                                    <?php } ?>
                                </div>
                            </form>
                            <?php
                        } else {
                            echo "<div class='text-danger'>Booking not found!</div>";
                        }
                    } else {
                        echo "<div class='text-danger'>Invalid Access!</div>";
                    }
                    ?>
                </div>
            </div>

            <hr>
        </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="vendor/js/sb-admin.min.js"></script>

<!-- Sweet alert JS -->
<script src="vendor/js/swal.js"></script>

<script>
    function confirmAction(action, booking_id) {
        let actionText = '';
        let actionUrl = '';

        switch(action) {
            case 'approve':
                actionText = "approve this booking";
                actionUrl = "admin-delete-booking.php?action=approve&booking_id=" + booking_id;
                break;
            case 'reject':
                actionText = "reject this booking";
                actionUrl = "admin-delete-booking.php?action=reject&booking_id=" + booking_id;
                break;
            case 'complete':
                actionText = "mark this booking as completed";
                actionUrl = "admin-delete-booking.php?action=complete&booking_id=" + booking_id;
                break;
            case 'delete':
                actionText = "delete this booking permanently";
                actionUrl = "admin-delete-booking.php?action=delete&booking_id=" + booking_id;
                break;
        }

        swal({
            title: "Are you sure?",
            text: "You are about to " + actionText + ". This action cannot be undone!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willAct) => {
            if (willAct) {
                window.location.href = actionUrl;
            }
        });
    }
</script>

</body>
</html>
