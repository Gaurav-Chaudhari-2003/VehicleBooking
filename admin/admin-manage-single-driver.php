<?php
session_start();
include('vendor/inc/config.php');  // Make sure this file includes the DB connection setup.
include('vendor/inc/checklogin.php');
check_login();

// Check if the form is submitted
if (isset($_POST['update_user'])) {
    // Get the data from the form
    $u_id = $_GET['u_id'];
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr = $_POST['u_addr'];
    $u_email = $_POST['u_email'];
    $u_pwd = $_POST['u_pwd'];
    $u_category = $_POST['u_category']; // If hidden field is required

    // Prepare and execute the update query
    $query = "UPDATE tms_user SET u_fname=?, u_lname=?, u_phone=?, u_addr=?, u_category=?, u_email=?, u_pwd=? WHERE u_id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sssssssi', $u_fname, $u_lname, $u_phone, $u_addr, $u_category, $u_email, $u_pwd, $u_id);

    if ($stmt->execute()) {
        $succ = "Driver Updated Successfully!";
    } else {
        $err = "Error updating the driver. Please try again later.";
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

            <!-- Success or Error Toasts -->
            <?php if(isset($succ)) { ?>
                <script>
                    setTimeout(function () {
                        toastr.success("<?php echo $succ;?>");
                    }, 100);
                </script>
            <?php } ?>

            <?php if(isset($err)) { ?>
                <script>
                    setTimeout(function () {
                        toastr.error("<?php echo $err;?>");
                    }, 100);
                </script>
            <?php } ?>

            <div class="card shadow-lg">
                <div class="card-header text-center bg-primary text-white">
                    <h4>Update Driver Details</h4>
                </div>
                <div class="card-body">

                    <!-- Add User Form -->
                    <?php
                    $aid = $_GET['u_id'];
                    $ret = "SELECT * FROM tms_user WHERE u_id=?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                        ?>
                        <form method="POST">
                            <div class="form-group">
                                <label for="u_fname">First Name</label>
                                <input type="text" class="form-control" id="u_fname" name="u_fname" value="<?php echo $row->u_fname;?>" required>
                            </div>

                            <div class="form-group">
                                <label for="u_lname">Last Name</label>
                                <input type="text" class="form-control" id="u_lname" name="u_lname" value="<?php echo $row->u_lname;?>" required>
                            </div>

                            <div class="form-group">
                                <label for="u_phone">Contact Number</label>
                                <input type="text" class="form-control" id="u_phone" name="u_phone" value="<?php echo $row->u_phone;?>" required>
                            </div>

                            <div class="form-group">
                                <label for="u_addr">Address</label>
                                <input type="text" class="form-control" id="u_addr" name="u_addr" value="<?php echo $row->u_addr;?>" required>
                            </div>

                            <div class="form-group" style="display:none;">
                                <label for="u_category">Category</label>
                                <input type="text" class="form-control" id="u_category" name="u_category" value="Driver">
                            </div>

                            <div class="form-group">
                                <label for="u_email">Email Address</label>
                                <input type="email" class="form-control" id="u_email" name="u_email" value="<?php echo $row->u_email;?>" required>
                            </div>

                            <div class="form-group">
                                <label for="u_pwd">Password</label>
                                <input type="password" class="form-control" id="u_pwd" name="u_pwd" value="<?php echo $row->u_pwd;?>" required>
                            </div>

                            <button type="submit" name="update_user" class="btn btn-success btn-block shadow-sm">Update Driver</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
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

<!-- Sweet Alert JS -->
<script src="vendor/js/swal.js"></script>

<!-- Toastr JS for Toast notifications -->
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">

</body>

</html>
