<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Add User
if (isset($_POST['add_user'])) {
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr = $_POST['u_addr'];
    $u_email = $_POST['u_email'];
    $u_pwd = $_POST['u_pwd'];
    $u_category = $_POST['u_category'];

    // Default values for fields that don't have a default value in DB
    $u_car_type = '';
    $u_car_regno = '';
    $u_car_bookdate = '';
    $u_car_book_status = '';

    $query = "INSERT INTO tms_user (u_fname, u_lname, u_phone, u_addr, u_category, u_email, u_pwd, u_car_type, u_car_regno, u_car_bookdate, u_car_book_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sssssssssss', $u_fname, $u_lname, $u_phone, $u_addr, $u_category, $u_email, $u_pwd, $u_car_type, $u_car_regno, $u_car_bookdate, $u_car_book_status);
    $stmt->execute();

    if ($stmt) {
        $succ = "User Added Successfully";
    } else {
        $err = "Something went wrong. Please try again.";
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

            <div class="card shadow-lg border-0 rounded-lg mt-4">
                <div class="card-header text-center">
                    <h4 class="font-weight-bold text-primary"><i class="fas fa-user-plus"></i> Add New User</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" required class="form-control" name="u_fname" placeholder="Enter First Name">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" required class="form-control" name="u_lname" placeholder="Enter Last Name">
                        </div>
                        <div class="form-group">
                            <label>Contact</label>
                            <input type="text" required class="form-control" name="u_phone" placeholder="Enter Contact Number">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" required class="form-control" name="u_addr" placeholder="Enter Address">
                        </div>
                        <input type="hidden" name="u_category" value="User">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" required class="form-control" name="u_email" placeholder="Enter Email Address">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" required class="form-control" name="u_pwd" placeholder="Enter Password">
                        </div>

                        <div class="text-center">
                            <button type="submit" name="add_user" class="btn btn-primary btn-block mt-3">
                                <i class="fas fa-user-plus"></i> Add User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Page level plugin JavaScript-->
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>

<!-- Custom scripts for all pages-->
<script src="vendor/js/sb-admin.min.js"></script>

<!-- SweetAlert -->
<script src="vendor/js/swal.js"></script>

</body>
</html>
