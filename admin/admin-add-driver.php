<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Add Driver
if (isset($_POST['add_driver'])) {
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
        $succ = "Driver Added Successfully";
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
                <!-- Success Toast -->
                <script>
                    setTimeout(function() {
                        toastr.success("<?php echo $succ; ?>");
                    }, 100);
                </script>
            <?php } ?>
            <?php if (isset($err)) { ?>
                <!-- Error Toast -->
                <script>
                    setTimeout(function() {
                        toastr.error("<?php echo $err; ?>");
                    }, 100);
                </script>
            <?php } ?>

            <div class="card">
                <div class="card-header">
                    <h4 class="text-center">Add New Driver</h4>
                </div>
                <div class="card-body">
                    <!-- Add Driver Form -->
                    <form method="POST" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label for="u_fname">First Name</label>
                            <input type="text" required class="form-control" id="u_fname" name="u_fname" placeholder="Enter First Name">
                        </div>
                        <div class="form-group">
                            <label for="u_lname">Last Name</label>
                            <input type="text" required class="form-control" id="u_lname" name="u_lname" placeholder="Enter Last Name">
                        </div>
                        <div class="form-group">
                            <label for="u_phone">Contact</label>
                            <input type="text" required class="form-control" id="u_phone" name="u_phone" placeholder="Enter Contact Number">
                        </div>
                        <div class="form-group">
                            <label for="u_addr">Address</label>
                            <input type="text" required class="form-control" id="u_addr" name="u_addr" placeholder="Enter Address">
                        </div>
                        <div class="form-group" style="display:none">
                            <label for="u_category">Category</label>
                            <input type="text" class="form-control" id="u_category" value="Driver" name="u_category">
                        </div>
                        <div class="form-group">
                            <label for="u_email">Email</label>
                            <input type="email" required class="form-control" id="u_email" name="u_email" placeholder="Enter Email Address">
                        </div>
                        <div class="form-group">
                            <label for="u_pwd">Password</label>
                            <input type="password" required class="form-control" id="u_pwd" name="u_pwd" placeholder="Enter Password">
                        </div>
                        <button type="submit" name="add_driver" class="btn btn-primary btn-block">Add Driver</button>
                    </form>
                    <!-- End Form-->
                </div>
            </div>

        </div>
        <!-- /.container-fluid -->

    </div>
    <!-- /.content-wrapper -->

</div>
<!-- /#wrapper -->


<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Toastr Notification JavaScript-->
<script src="vendor/toastr/toastr.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="vendor/js/sb-admin.min.js"></script>

<script>
    // Form validation
    function validateForm() {
        var email = document.getElementById("u_email").value;
        var phone = document.getElementById("u_phone").value;
        var password = document.getElementById("u_pwd").value;
        if (!validateEmail(email)) {
            toastr.error("Please enter a valid email address.");
            return false;
        }
        if (!validatePhone(phone)) {
            toastr.error("Please enter a valid contact number.");
            return false;
        }
        if (password.length < 6) {
            toastr.error("Password should be at least 6 characters.");
            return false;
        }
        return true;
    }

    // Validate Email
    function validateEmail(email) {
        var regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        return regex.test(email);
    }

    // Validate Phone
    function validatePhone(phone) {
        var regex = /^[0-9]{10}$/;
        return regex.test(phone);
    }
</script>

</body>

</html>
